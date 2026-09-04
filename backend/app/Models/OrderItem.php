<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'uuid',
        'order_id',
        'product_id',
        'product_variation_id',
        'product_name',
        'product_sku',
        'product_variation_name',
        'product_attributes',
        'unit_price',
        'quantity',
        'subtotal',
        'discount',
        'tax',
        'total',
        'status',
        'tracking_number',
    ];

    protected $casts = [
        'product_attributes' => 'array',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->uuid = Str::uuid();
        });
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}
