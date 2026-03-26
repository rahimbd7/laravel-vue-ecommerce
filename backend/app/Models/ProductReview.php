<?php
// app/Models/ProductReview.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_reviews';

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'pros',
        'cons',
        'is_verified_purchase',
        'is_approved',
        'is_featured',
        'helpful_votes',
        'unhelpful_votes',
        'images',
    ];

    protected $casts = [
        'rating' => 'integer',
        'pros' => 'array',
        'cons' => 'array',
        'images' => 'array',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'helpful_votes' => 'integer',
        'unhelpful_votes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'rating_stars',
        'helpful_percentage',
        'images_full_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            if ($review->is_approved) {
                $review->product->updateRating();
            }
        });

        static::updated(function ($review) {
            if ($review->isDirty('is_approved') || $review->isDirty('rating')) {
                $review->product->updateRating();
            }
        });

        static::deleted(function ($review) {
            $review->product->updateRating();
        });
    }

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function order()
    // {
    //     return $this->belongsTo(Order::class);
    // }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeWithRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeMinRating($query, $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    /**
     * Accessors
     */
    public function getRatingStarsAttribute()
    {
        $fullStars = floor($this->rating);
        $halfStar = ($this->rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

        return [
            'full' => $fullStars,
            'half' => $halfStar,
            'empty' => $emptyStars,
        ];
    }

    public function getHelpfulPercentageAttribute()
    {
        $total = $this->helpful_votes + $this->unhelpful_votes;
        if ($total === 0) {
            return 0;
        }
        return round(($this->helpful_votes / $total) * 100);
    }

    public function getImagesFullUrlAttribute()
    {
        if (!$this->images) {
            return [];
        }

        return array_map(function($image) {
            return asset('storage/' . $image);
        }, $this->images);
    }

    /**
     * Helper Methods
     */
    public function approve()
    {
        $this->update(['is_approved' => true]);
    }

    public function reject()
    {
        $this->update(['is_approved' => false]);
    }

    public function markAsFeatured()
    {
        $this->update(['is_featured' => true]);
    }

    public function markHelpful($userId)
    {
        // Track user vote to prevent double voting
        $this->increment('helpful_votes');
    }

    public function markUnhelpful($userId)
    {
        $this->increment('unhelpful_votes');
    }
}
