<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model {
    use HasFactory;

    protected $table = 'coupon_usage';

    public $timestamps = false;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'original_subtotal',
        'discount_amount',
        'discounted_total',
        'applied_items',
        'source',
        'is_reversed',
        'used_at',
        'reversed_at',
    ];

    protected $casts = [
        'applied_items'     => 'array',
        'original_subtotal' => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'discounted_total'  => 'decimal:2',
        'is_reversed'       => 'boolean',
        'used_at'           => 'datetime',
        'reversed_at'       => 'datetime',
    ];

    // Relationships
    public function coupon() {
        return $this->belongsTo(Coupon::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function order() {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeByUser($query, $userId) {
        return $query->where('user_id', $userId);
    }

    public function scopeByCoupon($query, $couponId) {
        return $query->where('coupon_id', $couponId);
    }

    public function scopeByDateRange($query, $from, $to) {
        if ($from) {
            $query->where('used_at', '>=', $from);
        }
        if ($to) {
            $query->where('used_at', '<=', $to);
        }
        return $query;
    }

    public function scopeNotReversed($query) {
        return $query->where('is_reversed', false);
    }

    // Accessors
    public function getFormattedDiscountAttribute(): string {
        return '$' . number_format($this->discount_amount, 2);
    }

    public function getDateAttribute(): string {
        return $this->used_at->format('Y-m-d H:i:s');
    }

    // Helper Methods
    public static function getUsageAnalytics($filters = []) {
        $query = self::notReversed();

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['coupon_id'])) {
            $query->where('coupon_id', $filters['coupon_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('used_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('used_at', '<=', $filters['date_to']);
        }

        // Get all usage records
        $usages = $query->with(['user', 'coupon'])->get();

        // Basic statistics
        $totalUses                 = $usages->count();
        $totalDiscount             = $usages->sum('discount_amount');
        $totalOriginalSubtotal     = $usages->sum('original_subtotal');
        $totalDiscountedTotal      = $usages->sum('discounted_total');
        $averageDiscount           = $totalUses > 0 ? round($totalDiscount / $totalUses, 2) : 0;
        $averageDiscountPercentage = $totalOriginalSubtotal > 0
            ? round(($totalDiscount / $totalOriginalSubtotal) * 100, 2)
            : 0;

        $byDate = $usages->groupBy(function ($usage) {
            return $usage->used_at->format('Y-m-d');
        })
            ->map(function ($group) {
                return [
                    'date'             => $group->first()->used_at->format('Y-m-d'),
                    'uses'             => $group->count(),
                    'total_discount'   => $group->sum('discount_amount'),
                    'average_discount' => round($group->avg('discount_amount'), 2),
                ];
            })
            ->sortByDesc('date')
            ->take(30)
            ->values();

        $byCoupon = $usages->groupBy('coupon_id')
            ->map(function ($group) {
                $coupon = $group->first()->coupon;
                return [
                    'coupon_id'        => $coupon->id ?? null,
                    'coupon_code'      => $coupon->code ?? 'N/A',
                    'coupon_name'      => $coupon->name ?? 'N/A',
                    'uses'             => $group->count(),
                    'total_discount'   => $group->sum('discount_amount'),
                    'average_discount' => round($group->avg('discount_amount'), 2),
                ];
            })
            ->sortByDesc('uses')
            ->take(10)
            ->values();

        $byUser = $usages->groupBy('user_id')
            ->map(function ($group) {
                $user = $group->first()->user;
                return [
                    'user_id'          => $user->id ?? null,
                    'user_name'        => $user->name ?? 'N/A',
                    'user_email'       => $user->email ?? 'N/A',
                    'uses'             => $group->count(),
                    'total_discount'   => $group->sum('discount_amount'),
                    'average_discount' => round($group->avg('discount_amount'), 2),
                ];
            })
            ->sortByDesc('uses')
            ->take(10)
            ->values();

        $bySource = $usages->groupBy('source')
            ->map(function ($group) {
                return [
                    'source'         => $group->first()->source,
                    'uses'           => $group->count(),
                    'total_discount' => $group->sum('discount_amount'),
                ];
            })
            ->values();

        return [
            'total_uses'                  => $totalUses,
            'total_discount'              => $totalDiscount,
            'total_original_subtotal'     => $totalOriginalSubtotal,
            'total_discounted_total'      => $totalDiscountedTotal,
            'average_discount'            => $averageDiscount,
            'average_discount_percentage' => $averageDiscountPercentage,
            'by_date'                     => $byDate,
            'by_coupon'                   => $byCoupon,
            'by_user'                     => $byUser,
            'by_source'                   => $bySource,
            'raw_data'                    => $usages, // For further processing if needed
        ];
    }
}
