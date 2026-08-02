<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Coupon extends Model {
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'applies_to',
        'eligible_items',
        'minimum_order_amount',
        'minimum_quantity',
        'usage_limit',
        'usage_limit_per_customer',
        'used_count',
        'starts_at',
        'expires_at',
        'is_first_time_only',
        'eligible_customer_types',
        'is_active',
        'is_public',
        'created_by',
        'created_by_id',
        'vendor_id',
        'metadata',
    ];

    protected $casts = [
        'eligible_items'           => 'array',
        'eligible_customer_types'  => 'array',
        'metadata'                 => 'array',
        'discount_value'           => 'decimal:2',
        'max_discount_amount'      => 'decimal:2',
        'minimum_order_amount'     => 'decimal:2',
        'minimum_quantity'         => 'integer',
        'usage_limit'              => 'integer',
        'usage_limit_per_customer' => 'integer',
        'used_count'               => 'integer',
        'is_active'                => 'boolean',
        'is_public'                => 'boolean',
        'is_first_time_only'       => 'boolean',
        'starts_at'                => 'datetime',
        'expires_at'               => 'datetime',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
        'deleted_at'               => 'datetime',
    ];

    // Relationships
    public function usage() {
        return $this->hasMany(CouponUsage::class);
    }
    public function usages() {
        return $this->hasMany(CouponUsage::class);
    }

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Scopes
    public function scopeActive($query) {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }
    public function scopeWithUsageCount($query) {
        return $query->withCount('usage');
    }

    public function scopeAvailable($query) {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhere('used_count', '<', 'usage_limit');
            });
    }

    public function scopePublic($query) {
        return $query->where('is_public', true);
    }

    public function scopeByVendor($query, $vendorId) {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByAdmin($query) {
        return $query->where('created_by', 'admin');
    }

    // Accessors
    public function getIsExpiredAttribute(): bool {
        if ($this->expires_at && now()->gt($this->expires_at)) {
            return true;
        }
        return false;
    }

    public function getIsAvailableAttribute(): bool {
        if (! $this->is_active) {
            return false;
        }

        if ($this->is_expired) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function getDiscountLabelAttribute(): string {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '%';
        }
        if ($this->discount_type === 'fixed') {
            return '$' . number_format($this->discount_value, 2);
        }
        if ($this->discount_type === 'free_shipping') {
            return 'Free Shipping';
        }
        if ($this->discount_type === 'bogo') {
            return 'Buy One Get One';
        }
        return '';
    }

    // Helper Methods
    public function incrementUsage(): void {
        $this->increment('used_count');
    }

    public function isEligibleForUser($userId): bool {
        if ($this->is_first_time_only) {
            $orderCount = Order::where('user_id', $userId)->count();
            if ($orderCount > 0) {
                return false;
            }

        }

        if ($this->eligible_customer_types) {
            $user = User::find($userId);
            if (! $user) {
                return false;
            }

            // Check if user type is in eligible types
            $userType = $user->is_vendor ? 'vendor' : ($user->is_admin ? 'admin' : 'customer');
            if (! in_array($userType, $this->eligible_customer_types)) {
                return false;
            }
        }

        // Check per-customer usage limit
        if ($this->usage_limit_per_customer > 0) {
            $usageCount = CouponUsage::where('coupon_id', $this->id)
                ->where('user_id', $userId)
                ->count();
            if ($usageCount >= $this->usage_limit_per_customer) {
                return false;
            }
        }

        return true;
    }

    public function isEligibleForCart($cart): bool {
        // Check minimum order amount
        if ($this->minimum_order_amount > 0 && $cart->subtotal < $this->minimum_order_amount) {
            return false;
        }

        // Check minimum quantity
        if ($this->minimum_quantity > 0 && $cart->total_quantity < $this->minimum_quantity) {
            return false;
        }

        // Check applies_to
        if ($this->applies_to === 'specific_products') {
            $eligibleIds = $this->eligible_items ?? [];
            $hasEligible = false;
            foreach ($cart->items as $item) {
                if (in_array($item->product_id, $eligibleIds)) {
                    $hasEligible = true;
                    break;
                }
            }
            if (! $hasEligible) {
                return false;
            }

        }

        if ($this->applies_to === 'specific_categories') {
            $eligibleIds = $this->eligible_items ?? [];
            $hasEligible = false;
            foreach ($cart->items as $item) {
                if (in_array($item->product->category_id, $eligibleIds)) {
                    $hasEligible = true;
                    break;
                }
            }
            if (! $hasEligible) {
                return false;
            }

        }

        return true;
    }

    public function calculateDiscount($cart): array {
        $eligibleSubtotal = $this->getEligibleSubtotal($cart);
        $discountAmount   = 0;

        switch ($this->discount_type) {
        case 'percentage':
            $discountAmount = ($eligibleSubtotal * $this->discount_value) / 100;
            if ($this->max_discount_amount && $discountAmount > $this->max_discount_amount) {
                $discountAmount = $this->max_discount_amount;
            }
            break;

        case 'fixed':
            $discountAmount = min($this->discount_value, $eligibleSubtotal);
            break;

        case 'free_shipping':
            $discountAmount = $cart->shipping_cost ?? 0;
            break;

        case 'bogo':
            $discountAmount = $this->calculateBogoDiscount($cart);
            break;
        }

        return [
            'discount_amount'   => round($discountAmount, 2),
            'eligible_subtotal' => round($eligibleSubtotal, 2),
            'discounted_total'  => round($cart->subtotal - $discountAmount, 2),
        ];
    }

    private function getEligibleSubtotal($cart): float {
        if ($this->applies_to === 'all') {
            return $cart->subtotal;
        }

        $eligibleIds = $this->eligible_items ?? [];
        $subtotal    = 0;

        foreach ($cart->items as $item) {
            $isEligible = false;
            if ($this->applies_to === 'specific_products') {
                $isEligible = in_array($item->product_id, $eligibleIds);
            } elseif ($this->applies_to === 'specific_categories') {
                $isEligible = in_array($item->product->category_id, $eligibleIds);
            }
            if ($isEligible) {
                $subtotal += $item->total;
            }
        }

        return $subtotal;
    }

    private function calculateBogoDiscount($cart): float {
        $metadata = $this->metadata ?? [];
        $discount = 0;

        // Default BOGO: Cheapest item free when buying 2
        $bogoType     = $metadata['bogo_type'] ?? 'cheapest_free';
        $bogoQuantity = $metadata['bogo_quantity'] ?? 2;
        $bogoFree     = $metadata['bogo_free'] ?? 1;

        // Group items by product for BOGO calculation
        $groupedItems = [];
        foreach ($cart->items as $item) {
            $productId = $item->product_id;
            if (! isset($groupedItems[$productId])) {
                $groupedItems[$productId] = [
                    'product'    => $item->product,
                    'quantity'   => 0,
                    'unit_price' => $item->unit_price,
                ];
            }
            $groupedItems[$productId]['quantity'] += $item->quantity;
        }

        foreach ($groupedItems as $item) {
            if ($item['quantity'] >= $bogoQuantity) {
                $freeItems = floor($item['quantity'] / $bogoQuantity) * $bogoFree;
                if ($bogoType === 'cheapest_free') {
                    $discount += $freeItems * $item['unit_price'];
                } else {
                    $discount += $freeItems * $item['unit_price'];
                }
            }
        }

        return $discount;
    }

    // Static Methods
    public static function generateUniqueCode(): string {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public static function getAnalytics($filters = []) {
        $query = self::withCount('usage');

        // Apply filters
        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Get all coupons
        $coupons = $query->get();

        // Calculate statistics using collection methods
        $totalCoupons  = $coupons->count();
        $activeCoupons = $coupons->filter(function ($coupon) {
            return $coupon->is_active &&
                (! $coupon->expires_at || $coupon->expires_at->isFuture()) &&
                (! $coupon->usage_limit || $coupon->usages_count < $coupon->usage_limit);
        })->count();

        $expiredCoupons = $coupons->filter(function ($coupon) {
            return $coupon->expires_at && $coupon->expires_at->isPast();
        })->count();

        $exhaustedCoupons = $coupons->filter(function ($coupon) {
            return $coupon->usage_limit && $coupon->usages_count >= $coupon->usage_limit;
        })->count();

        $totalUses    = $coupons->sum('usages_count');
        $averageUsage = $totalCoupons > 0 ? round($totalUses / $totalCoupons, 2) : 0;

        // Group by discount type
        $discountTypes = $coupons->groupBy('discount_type')->map(function ($group) {
            return [
                'count'          => $group->count(),
                'total_uses'     => $group->sum('usages_count'),
                'total_discount' => $group->sum(function ($coupon) {
                    return $coupon->discount_value * $coupon->usages_count;
                }),
            ];
        });

        // Top performing coupons
        $topCoupons = $coupons->sortByDesc('usages_count')->take(10)->values()->map(function ($coupon) {
            return [
                'id'                   => $coupon->id,
                'code'                 => $coupon->code,
                'name'                 => $coupon->name,
                'discount_type'        => $coupon->discount_type,
                'discount_value'       => $coupon->discount_value,
                'usages_count'         => $coupon->usages_count,
                'total_discount_given' => $coupon->usages_count * $coupon->discount_value,
            ];
        });

        return [
            'total_coupons'     => $totalCoupons,
            'active_coupons'    => $activeCoupons,
            'expired_coupons'   => $expiredCoupons,
            'exhausted_coupons' => $exhaustedCoupons,
            'total_uses'        => $totalUses,
            'average_usage'     => $averageUsage,
            'discount_types'    => $discountTypes,
            'top_coupons'       => $topCoupons,
            'coupons'           => $coupons, // Raw data for further processing
        ];
    }
}
