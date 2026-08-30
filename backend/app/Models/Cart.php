<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cart extends Model
{
    protected $table = 'carts';

    protected $fillable = [
        'uuid', 'user_id', 'session_id', 'guest_token', 'item_count', 'subtotal',
        'discount_total', 'tax_total', 'shipping_total', 'grand_total',
        'coupon_code', 'coupon_discount', 'shipping_method',
        'shipping_address', 'status', 'last_activity_at', 'expires_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'shipping_address' => 'array',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cart) {
            $cart->uuid = Str::uuid();
            $cart->last_activity_at = now();
            $cart->expires_at = now()->addDays(30);
        });

        static::updating(function ($cart) {
            $cart->last_activity_at = now();
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Add item to cart
     */
    public function addItem($productId, $variationId, $quantity)
    {
        // Ensure cart exists before adding items
        if (!$this->exists) {
            $this->save();
        }

        // Find product
        $product = Product::findOrFail($productId);
        $variation = null;

        // Validate variation if provided
        if ($variationId) {
            $variation = ProductVariation::where('id', $variationId)
                ->where('product_id', $productId)
                ->first();

            if (!$variation) {
                throw new \Exception("The selected variation is not available for '{$product->name}'.");
            }

            // Check variation stock
            if ($variation->stock_quantity < $quantity) {
                throw new \Exception("Only {$variation->stock_quantity} units available for '{$product->name} - {$variation->name}'.");
            }
        } else {
            // Check product stock (if no variation)
            if ($product->stock_quantity < $quantity) {
                throw new \Exception("Only {$product->stock_quantity} units available for '{$product->name}'.");
            }
        }

        // Calculate prices
        $unitPrice = $variation ? $variation->price : $product->price;
        $subtotal = $unitPrice * $quantity;
        $tax = ($subtotal * ($product->tax_rate ?? 0)) / 100;
        $total = $subtotal + $tax;

        // Check if item already exists in cart
        $cartItem = $this->items()
            ->where('product_id', $productId)
            ->where('product_variation_id', $variationId)
            ->first();

        if ($cartItem) {
            // Update existing item
            $newQuantity = $cartItem->quantity + $quantity;

            // Check stock for updated quantity
            $maxStock = $variation ? $variation->stock_quantity : $product->stock_quantity;
            if ($maxStock < $newQuantity) {
                throw new \Exception("Cannot add {$quantity} more. Only {$maxStock} units available in total.");
            }

            $cartItem->quantity = $newQuantity;
            $cartItem->subtotal = $cartItem->unit_price * $cartItem->quantity;
            $cartItem->tax = ($cartItem->subtotal * ($product->tax_rate ?? 0)) / 100;
            $cartItem->total = $cartItem->subtotal + $cartItem->tax;
            $cartItem->save();
        } else {
            // Create new item
            $this->items()->create([
                'uuid' => Str::uuid(),
                'product_id' => $productId,
                'product_variation_id' => $variationId,
                'product_name' => $product->name,
                'product_sku' => $variation ? $variation->sku : $product->sku,
                'product_variation_name' => $variation?->name,
                'product_attributes' => $variation?->attributes,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);
        }

        // Refresh cart totals
        $this->refreshTotals();

        return $this;
    }

    /**
     * Remove item from cart
     */
    public function removeItem($cartItemId)
    {
        $cartItem = $this->items()->findOrFail($cartItemId);
        $cartItem->delete();
        $this->refreshTotals();

        return $this;
    }
    public function addCoupon($couponCode, $discountAmount, $couponId = null)
    {
        $coupons = $this->applied_coupons ?? [];

        // Check if already applied
        foreach ($coupons as $existing) {
            if ($existing['code'] === $couponCode) {
                return false;
            }
        }

        $coupons[] = [
            'code' => $couponCode,
            'id' => $couponId,
            'discount' => $discountAmount,
            'applied_at' => now()->toISOString(),
        ];

        $this->applied_coupons = $coupons;
        $this->coupon_code = $couponCode;
        $this->coupon_discount = $this->getTotalDiscount();
        $this->refreshTotals();

        return true;
    }

    public function removeCoupon($couponCode)
    {
        $coupons = $this->applied_coupons ?? [];

        $removed = false;
        foreach ($coupons as $key => $coupon) {
            if ($coupon['code'] === $couponCode) {
                unset($coupons[$key]);
                $removed = true;
                break;
            }
        }

        if (!$removed) {
            return false;
        }

        $coupons = array_values($coupons);
        $this->applied_coupons = $coupons;

        if (!empty($coupons)) {
            $last = end($coupons);
            $this->coupon_code = $last['code'];
            $this->coupon_discount = $this->getTotalDiscount();
        } else {
            $this->coupon_code = null;
            $this->coupon_discount = 0;
        }

        $this->refreshTotals();
        return true;
    }

    public function getTotalDiscount()
    {
        $coupons = $this->applied_coupons ?? [];
        return array_sum(array_column($coupons, 'discount'));
    }

    public function clearCoupons()
    {
        $this->applied_coupons = [];
        $this->coupon_code = null;
        $this->coupon_discount = 0;
        $this->discount_total = 0;
        $this->refreshTotals();
    }

    // Refresh totals
    public function refreshTotals()
    {
        $this->item_count = $this->items()->sum('quantity');
        $this->subtotal = $this->items()->sum('subtotal');
        $this->tax_total = $this->items()->sum('tax');
        $this->discount_total = $this->getTotalDiscount();
        $this->grand_total = $this->subtotal + $this->tax_total + $this->shipping_total - $this->discount_total;
        $this->save();
    }

    public function isEmpty()
    {
        return $this->items()->count() === 0;
    }

    public function getTotalItems()
    {
        return $this->items()->sum('quantity');
    }

    public function getSubtotal()
    {
        return $this->items()->sum('subtotal');
    }
    /**
     * Update item quantity
     */
    public function updateQuantity($cartItemId, $quantity)
    {
        $cartItem = $this->items()->findOrFail($cartItemId);

        if ($quantity <= 0) {
            return $this->removeItem($cartItemId);
        }

        // Get product for stock check
        $product = Product::find($cartItem->product_id);
        $variation = null;

        if ($cartItem->product_variation_id) {
            $variation = ProductVariation::find($cartItem->product_variation_id);
        }

        // Check stock
        $maxStock = $variation ? $variation->stock_quantity : $product->stock_quantity;
        if ($maxStock < $quantity) {
            throw new \Exception("Only {$maxStock} units available for this product.");
        }

        $cartItem->quantity = $quantity;
        $cartItem->subtotal = $cartItem->unit_price * $quantity;
        $cartItem->tax = ($cartItem->subtotal * ($product->tax_rate ?? 0)) / 100;
        $cartItem->total = $cartItem->subtotal + $cartItem->tax;
        $cartItem->save();

        $this->refreshTotals();

        return $this;
    }
    /**
     * Clear all items from cart
     */
    public function clear()
    {
        $this->items()->delete();
        $this->item_count = 0;
        $this->subtotal = 0;
        $this->tax_total = 0;
        $this->discount_total = 0;
        $this->coupon_code = null;
        $this->coupon_discount = 0;
        $this->grand_total = 0;
        $this->save();

        return $this;
    }

    public function getAppliedCouponsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        return json_decode($value, true) ?? [];
    }
    public function setAppliedCouponsAttribute($value)
    {
        $this->attributes['applied_coupons'] = json_encode($value);
    }
}
