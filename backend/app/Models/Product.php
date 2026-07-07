<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model {
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'stock_quantity',
        'stock_status',
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
        'low_stock_threshold',
        'attributes',
        'tags',
        'sold_count',
        'average_rating',
        'review_count',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'compare_price'  => 'decimal:2',
        'cost_per_item'  => 'decimal:2',
        'tax_rate'       => 'decimal:2',
        'weight'         => 'decimal:2',
        'is_visible'     => 'boolean',
        'is_featured'    => 'boolean',
        'has_variations' => 'boolean',
        'is_taxable'     => 'boolean',
        'free_shipping'  => 'boolean',
        'meta_keywords'  => 'array',
        'attributes'     => 'array',
        'tags'           => 'array',
        'average_rating' => 'decimal:2',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
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
        'meta_title_fallback',
        'meta_description_fallback',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($product) {
            // 🟢 Auto-generate slug if not provided
            if (empty($product->slug)) {
                $product->slug = self::generateUniqueSlug($product->name);
            }

            // 🟢 Auto-generate SKU if not provided
            if (empty($product->sku)) {
                $product->sku = self::generateUniqueSku();
            }

            // 🟢 Auto-generate meta tags if not provided
            $product->generateMetaTags();
        });

        static::updating(function ($product) {
            // 🟢 Update slug if name changed and slug not manually set
            if ($product->isDirty('name') && !$product->isDirty('slug')) {
                $product->slug = self::generateUniqueSlug($product->name, $product->id);
            }

            // 🟢 Update meta if needed
            $product->handleMetaUpdate();
        });
    }

    // ===================== AUTO-GENERATION METHODS =====================

    /**
     * Generate unique slug
     */
    public static function generateUniqueSlug(string $name, $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        $query = self::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            $query = self::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    /**
     * Generate unique SKU
     */
    public static function generateUniqueSku(): string
    {
        $prefix = 'SKU';
        $random = strtoupper(Str::random(6));
        $sku = $prefix . '-' . $random;

        while (self::where('sku', $sku)->exists()) {
            $random = strtoupper(Str::random(6));
            $sku = $prefix . '-' . $random;
        }

        return $sku;
    }

    /**
     * Generate meta tags (called on creation)
     */
    public function generateMetaTags(): void
    {
        // Meta Title
        if (empty($this->meta_title)) {
            $this->meta_title = $this->name;
        }

        // Meta Description
        if (empty($this->meta_description)) {
            $description = strip_tags($this->description ?? '');
            $this->meta_description = Str::limit($description, 160);
        }

        // Meta Keywords
        if (empty($this->meta_keywords)) {
            $this->meta_keywords = self::generateKeywords($this->name);
        }
    }

    /**
     * Handle meta updates (called on update)
     */
    public function handleMetaUpdate(): void
    {
        $dirty = $this->getDirty();

        // If name changed and meta_title was NOT manually provided
        if (isset($dirty['name']) && !$this->wasManuallyFilled('meta_title')) {
            $this->meta_title = $this->name;
        }

        // If description changed and meta_description was NOT manually provided
        if (isset($dirty['description']) && !$this->wasManuallyFilled('meta_description')) {
            $description = strip_tags($this->description ?? '');
            $this->meta_description = Str::limit($description, 160);
        }

        // If name changed and meta_keywords was NOT manually provided
        if (isset($dirty['name']) && !$this->wasManuallyFilled('meta_keywords')) {
            $this->meta_keywords = self::generateKeywords($this->name);
        }
    }

    /**
     * Check if a field was manually set in the request
     */
    private function wasManuallyFilled(string $field): bool
    {
        return $this->$field !== null &&
               $this->$field !== $this->getOriginal($field);
    }

    /**
     * Generate keywords from product name
     */
    private static function generateKeywords($name): string
    {
        $words = explode(' ', $name);
        $keywords = array_slice($words, 0, 5);

        // Remove common words
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'for', 'nor', 'on', 'at', 'to', 'by'];
        $keywords = array_diff($keywords, $commonWords);

        return implode(', ', $keywords);
    }

    /**
     * Accessor for meta title with fallback
     */
    public function getMetaTitleFallbackAttribute(): string
    {
        return $this->meta_title ?? $this->name;
    }

    /**
     * Accessor for meta description with fallback
     */
    public function getMetaDescriptionFallbackAttribute(): string
    {
        return $this->meta_description ?? Str::limit(strip_tags($this->description ?? ''), 160);
    }

    // ===================== RELATIONSHIPS =====================
    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function images() {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function primaryImage() {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variations() {
        return $this->hasMany(ProductVariation::class);
    }

    public function defaultVariation() {
        return $this->hasOne(ProductVariation::class)->where('is_default', true);
    }

    public function reviews() {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews() {
        return $this->hasMany(ProductReview::class)->where('is_approved', true);
    }

    // ===================== SCOPES =====================
    public function scopeVisible($query) {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured($query) {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query) {
        return $query->whereIn('stock_status', ['in_stock', 'low_stock']);
    }

    public function scopeByVendor($query, $vendorId) {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByCategory($query, $categoryId) {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, $term) {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('description', 'LIKE', "%{$term}%")
                ->orWhere('sku', 'LIKE', "%{$term}%")
                ->orWhere('meta_title', 'LIKE', "%{$term}%")
                ->orWhere('meta_description', 'LIKE', "%{$term}%");
        });
    }

    // ===================== ACCESSORS =====================
    public function getFinalPriceAttribute() {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return $this->compare_price;
        }
        return $this->price;
    }

    public function getDiscountPercentageAttribute() {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return round((($this->compare_price - $this->price) / $this->compare_price) * 100);
        }
        return 0;
    }

    public function getIsOnSaleAttribute() {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getThumbnailAttribute() {
        $primary = $this->primaryImage;
        if ($primary && $primary->thumbnail_url) {
            return $primary->thumbnail_url;
        }
        $firstImage = $this->images()->first();
        return $firstImage?->thumbnail_url ?? asset('images/no-image.jpg');
    }

    public function getImageUrlAttribute() {
        $primary = $this->primaryImage;
        if ($primary && $primary->image_url) {
            return $primary->image_url;
        }
        $firstImage = $this->images()->first();
        return $firstImage?->image_url ?? asset('images/no-image.jpg');
    }

    public function getStockStatusLabelAttribute() {
        return match ($this->stock_status) {
            'in_stock'     => 'In Stock',
            'low_stock'    => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
            'backorder'    => 'Available on Backorder',
            default        => ucfirst(str_replace('_', ' ', $this->stock_status))
        };
    }

    public function getFormattedPriceAttribute() {
        return '$' . number_format($this->price, 2);
    }

    public function getShippingTypeLabelAttribute() {
        return match ($this->shipping_type) {
            'physical' => 'Physical Product',
            'digital'  => 'Digital Product',
            'service'  => 'Service',
            default    => ucfirst((string) $this->shipping_type),
        };
    }

    public function getDimensionsArrayAttribute() {
        if (empty($this->dimensions) || ! str_contains($this->dimensions, 'x')) {
            return null;
        }

        $parts = array_map('trim', explode('x', $this->dimensions));
        if (count($parts) !== 3) {
            return null;
        }

        return [
            'length' => (float) $parts[0],
            'width'  => (float) $parts[1],
            'height' => (float) $parts[2],
            'unit'   => 'cm',
        ];
    }

    // ===================== HELPER METHODS =====================
    public function isInStock() {
        return in_array($this->stock_status, ['in_stock', 'low_stock']);
    }

    public function hasStock($quantity = 1) {
        if ($this->has_variations) {
            return $this->variations()->sum('stock_quantity') >= $quantity;
        }
        return $this->stock_quantity >= $quantity;
    }

    public function updateStockStatus() {
        $threshold = $this->low_stock_threshold ?? 5;
        $stock = $this->stock_quantity ?? 0;

        if ($stock <= 0) {
            $this->stock_status = 'out_of_stock';
        } elseif ($stock <= $threshold) {
            $this->stock_status = 'low_stock';
        } else {
            $this->stock_status = 'in_stock';
        }
        $this->saveQuietly();
    }

    public function updateRating() {
        $query = $this->reviews()->where('is_approved', true);
        $this->review_count = $query->count();
        $this->average_rating = $query->avg('rating') ?: 0;
        $this->saveQuietly();
    }

    public function getTotalStock() {
        if ($this->has_variations) {
            return (int) $this->variations()->sum('stock_quantity');
        }
        return (int) $this->stock_quantity;
    }

    public function syncVariationPrices() {
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

    public function getLowestPrice() {
        if (!$this->has_variations) {
            return $this->price;
        }
        return $this->variations()->where('is_visible', true)->min('price') ?? $this->price;
    }

    public function getHighestPrice() {
        if (!$this->has_variations) {
            return $this->price;
        }
        return $this->variations()->where('is_visible', true)->max('price') ?? $this->price;
    }
}
