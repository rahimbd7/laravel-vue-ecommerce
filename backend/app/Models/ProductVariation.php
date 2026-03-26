<?php
// app/Models/ProductVariation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductVariation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_variations';

    protected $fillable = [
        'uuid',
        'product_id',
        'name',
        'sku',
        'barcode',
        'attributes',
        'price',
        'compare_price',
        'cost_per_item',
        'stock_quantity',
        'low_stock_threshold',
        'stock_status',
        'weight',
        'dimensions',
        'is_visible',
        'is_default',
        'position',
        'image_id',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_per_item' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'weight' => 'decimal:2',
        'is_visible' => 'boolean',
        'is_default' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'final_price',
        'discount_percentage',
        'is_on_sale',
        'stock_status_label',
        'formatted_price',
        'image_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($variation) {
            if (empty($variation->uuid)) {
                $variation->uuid = (string) Str::uuid();
            }
            if (empty($variation->low_stock_threshold)) {
                $variation->low_stock_threshold = 5;
            }
        });

        static::created(function ($variation) {
            if ($variation->is_default) {
                $variation->product->variations()
                    ->where('id', '!=', $variation->id)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($variation) {
            if ($variation->isDirty('is_default') && $variation->is_default) {
                $variation->product->variations()
                    ->where('id', '!=', $variation->id)
                    ->update(['is_default' => false]);
            }
        });

        static::updated(function ($variation) {
            if ($variation->isDirty('stock_quantity')) {
                $variation->updateStockStatus();
            }
        });
    }

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function image()
    {
        return $this->belongsTo(ProductImage::class, 'image_id');
    }

    /**
     * Scopes
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereIn('stock_status', ['in_stock', 'low_stock']);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Accessors
     */
    public function getFinalPriceAttribute()
    {
        return $this->price ?? $this->product->price;
    }

    public function getDiscountPercentageAttribute()
    {
        $comparePrice = $this->compare_price ?? $this->product->compare_price;
        $price = $this->price ?? $this->product->price;

        if ($comparePrice && $comparePrice > $price) {
            return round((($comparePrice - $price) / $comparePrice) * 100);
        }
        return 0;
    }

    public function getIsOnSaleAttribute()
    {
        $comparePrice = $this->compare_price ?? $this->product->compare_price;
        $price = $this->price ?? $this->product->price;

        return $comparePrice && $comparePrice > $price;
    }

    public function getStockStatusLabelAttribute()
    {
        return match($this->stock_status) {
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
            'backorder' => 'Available on Backorder',
            default => ucfirst(str_replace('_', ' ', $this->stock_status))
        };
    }

    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->final_price, 2);
    }

    public function getImageUrlAttribute()
    {
        return $this->image?->image_url ?? $this->product->image_url;
    }

    /**
     * Helper Methods
     */
    public function isInStock()
    {
        return in_array($this->stock_status, ['in_stock', 'low_stock']);
    }

    public function hasStock($quantity = 1)
    {
        return $this->stock_quantity >= $quantity;
    }

    public function updateStockStatus()
    {
        if ($this->stock_quantity <= 0) {
            $this->stock_status = 'out_of_stock';
        } elseif ($this->stock_quantity <= $this->low_stock_threshold) {
            $this->stock_status = 'low_stock';
        } else {
            $this->stock_status = 'in_stock';
        }
        $this->saveQuietly();
    }

    public function decrementStock($quantity = 1)
    {
        $this->decrement('stock_quantity', $quantity);
        $this->updateStockStatus();
    }

    public function incrementStock($quantity = 1)
    {
        $this->increment('stock_quantity', $quantity);
        $this->updateStockStatus();
    }
}
