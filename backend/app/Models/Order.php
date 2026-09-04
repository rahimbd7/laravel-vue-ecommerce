<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Payment;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'user_id',
        'vendor_id',
        'order_number',
        'invoice_number',
        'status',
        'payment_status',
        'fulfillment_status',
        'subtotal',
        'discount_total',
        'coupon_id',
        'coupon_discount',
        'tax_total',
        'shipping_total',
        'grand_total',
        'payment_method',
        'payment_transaction_id',
        'paid_at',
        'shipping_method',
        'tracking_number',
        'carrier',
        'shipped_at',
        'delivered_at',
        'customer_name',
        'customer_email',
        'customer_phone',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'notes',
        'customer_notes',
        'metadata',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->uuid = Str::uuid();
            $order->order_number = static::generateOrderNumber();
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$date}-{$random}";
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'processing'])
               && !$this->isPaid();
    }

    public function canBeUpdated(): bool
    {
        return !in_array($this->status, ['delivered', 'completed', 'cancelled', 'refunded']);
    }
    public function hasCoupon(): bool
    {
        return !is_null($this->coupon_id);
    }

    public function getCouponDiscountAmount(): float
    {
        return $this->coupon_discount ?? 0;
    }

    public function getSubtotalAfterDiscount(): float
    {
        return $this->subtotal - $this->coupon_discount;
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function getCouponCodeAttribute()
    {
        return $this->coupon ? $this->coupon->code : null;
    }

    public function getCouponNameAttribute()
    {
        return $this->coupon ? $this->coupon->name : null;
    }
}
