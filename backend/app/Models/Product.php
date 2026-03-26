<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'uuid',
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'stock_quantity',
        'stock_status',
        'low_stock_threshold',
        'price',
        'compare_price',
        'cost_per_item',
        'is_visible',
        'is_featured',
        'has_variations',
        'is_taxable',
        'tax_rate',
        'weight',
        'dimensions',
        'shipping_type',
        'free_shipping',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'attributes',
        'tags',
        'sold_count',
        'view_count',
        'average_rating',
        'review_count',
    ];

    protected $casts = [
        'uuid' => 'string',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_per_item' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'has_variations' => 'boolean',
        'is_taxable' => 'boolean',
        'free_shipping' => 'boolean',
        'meta_keywords' => 'array',
        'attributes' => 'array',
        'tags' => 'array',
        'average_rating' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'final_price',
        'discount_percentage',
        'is_on_sale',
        'thumbnail',
        'image_url',
        'stock_status_label',
        'formatted_price',
        'shipping_type_label',
        'dimensions_array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->low_stock_threshold)) {
                $product->low_stock_threshold = 5;
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && !$product->isDirty('slug')) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Relationships
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function defaultVariation()
    {
        return $this->hasOne(ProductVariation::class)->where('is_default', true);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true);
    }

    /**
     * Scopes
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereIn('stock_status', ['in_stock', 'low_stock']);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%")
              ->orWhere('sku', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Accessors
     */
    public function getFinalPriceAttribute()
    {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return $this->compare_price;
        }
        return $this->price;
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return round((($this->compare_price - $this->price) / $this->compare_price) * 100);
        }
        return 0;
    }

    public function getIsOnSaleAttribute()
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getThumbnailAttribute()
    {
        $primary = $this->primaryImage;
        if ($primary && $primary->thumbnail_url) {
            return $primary->thumbnail_url;
        }
        $firstImage = $this->images()->first();
        return $firstImage?->thumbnail_url ?? asset('images/no-image.jpg');
    }

    public function getImageUrlAttribute()
    {
        $primary = $this->primaryImage;
        if ($primary && $primary->image_url) {
            return $primary->image_url;
        }
        $firstImage = $this->images()->first();
        return $firstImage?->image_url ?? asset('images/no-image.jpg');
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
        return '$' . number_format($this->price, 2);
    }

    public function getShippingTypeLabelAttribute()
    {
        return match ($this->shipping_type) {
            'physical' => 'Physical Product',
            'digital' => 'Digital Product',
            'service' => 'Service',
            default => ucfirst((string) $this->shipping_type),
        };
    }

    public function getDimensionsArrayAttribute()
    {
        if (empty($this->dimensions) || !str_contains($this->dimensions, 'x')) {
            return null;
        }

        $parts = array_map('trim', explode('x', $this->dimensions));
        if (count($parts) !== 3) {
            return null;
        }

        return [
            'length' => (float) $parts[0],
            'width' => (float) $parts[1],
            'height' => (float) $parts[2],
            'unit' => 'cm',
        ];
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
        if ($this->has_variations) {
            return $this->variations()->sum('stock_quantity') >= $quantity;
        }
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

    public function updateRating()
    {
        $query = $this->reviews()->where('is_approved', true);
        $this->review_count = $query->count();
        $this->average_rating = $query->avg('rating') ?: 0;
        $this->saveQuietly();
    }

    public function getTotalStock()
    {
        if ($this->has_variations) {
            return (int) $this->variations()->sum('stock_quantity');
        }

        return (int) $this->stock_quantity;
    }

    public function syncVariationPrices()
    {
        if (!$this->has_variations) {
            return $this;
        }

        $visibleVariations = $this->variations()->where('is_visible', true);

        $lowest = $visibleVariations->min('price');
        $totalStock = (int) $visibleVariations->sum('stock_quantity');

        if (!is_null($lowest)) {
            $this->price = $lowest;
        }

        $this->stock_quantity = $totalStock;
        $this->updateStockStatus();
        $this->saveQuietly();

        return $this;
    }

    public function getLowestPrice()
    {
        if (!$this->has_variations) {
            return $this->price;
        }
        return $this->variations()->where('is_visible', true)->min('price') ?? $this->price;
    }

    public function getHighestPrice()
    {
        if (!$this->has_variations) {
            return $this->price;
        }
        return $this->variations()->where('is_visible', true)->max('price') ?? $this->price;
    }
}
