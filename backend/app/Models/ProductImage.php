<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'image_url',
        'thumbnail_url',
        'medium_url',
        'large_url',
        'cloudinary_public_id',
        'cloudinary_asset_id',
        'cloudinary_version',
        'is_primary',
        'alt_text',
        'title',
        'caption',
        'order',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'full_image_url',
        'full_thumbnail_url',
        'full_medium_url',
        'full_large_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($image) {
            if ($image->is_primary) {
                $image->product->images()
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => false]);
            }
        });

        static::updating(function ($image) {
            if ($image->isDirty('is_primary') && $image->is_primary) {
                $image->product->images()
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => false]);
            }
        });

        static::deleted(function ($image) {
            if ($image->is_primary) {
                $newPrimary = $image->product->images()->first();
                if ($newPrimary) {
                    $newPrimary->update(['is_primary' => true]);
                }
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

    // ===================== ACCESSORS =====================

    public function getFullImageUrlAttribute()
    {
        return $this->image_url;
    }

    public function getFullThumbnailUrlAttribute()
    {
        return $this->thumbnail_url ?? $this->image_url;
    }

    public function getFullMediumUrlAttribute()
    {
        return $this->medium_url ?? $this->image_url;
    }

    public function getFullLargeUrlAttribute()
    {
        return $this->large_url ?? $this->image_url;
    }

    /**
     * Scopes
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
