<?php
// app/Models/CartItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'uuid', 'cart_id', 'product_id', 'product_variation_id',
        'product_name', 'product_sku', 'product_variation_name',
        'product_attributes', 'quantity', 'unit_price', 'subtotal',
        'discount', 'tax', 'total', 'is_saved_for_later'
    ];

    protected $casts = [
        'product_attributes' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'is_saved_for_later' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cartItem) {
            $cartItem->uuid = Str::uuid();
        });
    }

    // Relationships
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    // Accessors for formatted prices
    public function getFormattedUnitPriceAttribute()
    {
        return number_format($this->unit_price, 2);
    }

    public function getFormattedSubtotalAttribute()
    {
        return number_format($this->subtotal, 2);
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2);
    }

    public function getFormattedTaxAttribute()
    {
        return number_format($this->tax, 2);
    }

    public function getFormattedDiscountAttribute()
    {
        return number_format($this->discount, 2);
    }

    // Helper method to recalculate item totals
    public function recalculate()
    {
        $this->subtotal = $this->unit_price * $this->quantity;

        $taxRate = $this->product ? $this->product->tax_rate : 0;
        $this->tax = ($this->subtotal * $taxRate) / 100;

        $this->total = $this->subtotal + $this->tax - $this->discount;

        $this->save();

        // Refresh cart totals
        if ($this->cart) {
            $this->cart->refreshTotals();
        }

        return $this;
    }

    // Get product display name (with variation if exists)
    public function getProductDisplayNameAttribute()
    {
        if ($this->product_variation_name) {
            return $this->product_name . ' - ' . $this->product_variation_name;
        }

        return $this->product_name;
    }

    // Check if item is saved for later
    public function isSavedForLater()
    {
        return $this->is_saved_for_later;
    }

    // Move to saved for later
    public function saveForLater()
    {
        $this->is_saved_for_later = true;
        $this->save();

        if ($this->cart) {
            $this->cart->refreshTotals();
        }

        return $this;
    }

    // Move back to cart
    public function moveToCart()
    {
        $this->is_saved_for_later = false;
        $this->save();

        if ($this->cart) {
            $this->cart->refreshTotals();
        }

        return $this;
    }
}
