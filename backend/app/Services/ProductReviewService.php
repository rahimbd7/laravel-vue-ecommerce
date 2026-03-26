<?php
// app/Services/ProductReviewService.php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProductReviewService
{
    /**
     * Create review for product
     */
    public function createForProduct(Product $product, User $user, array $data)
    {
        return DB::transaction(function () use ($product, $user, $data) {
            // Check if user already reviewed this product
            $existingReview = ProductReview::where('product_id', $product->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingReview) {
                throw new \Exception('User has already reviewed this product');
            }

            // Create review
            $review = ProductReview::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'is_verified_purchase' => $this->checkVerifiedPurchase($user, $product),
            ]);

            // Clear cache
            $this->clearReviewCache($product);

            return $review->load(['user']);
        });
    }

    /**
     * Update review
     */
    public function update(ProductReview $review, array $data)
    {
        return DB::transaction(function () use ($review, $data) {
            $review->update($data);

            // Clear cache
            $this->clearReviewCache($review->product);

            return $review->fresh(['user']);
        });
    }

    /**
     * Delete review
     */
    public function delete(ProductReview $review)
    {
        return DB::transaction(function () use ($review) {
            $product = $review->product;
            $result = $review->delete();

            // Clear cache
            $this->clearReviewCache($product);

            return $result;
        });
    }

    /**
     * Approve review
     */
    public function approve(ProductReview $review)
    {
        return DB::transaction(function () use ($review) {
            $review->update(['is_approved' => true]);

            // Clear cache
            $this->clearReviewCache($review->product);

            return $review;
        });
    }

    /**
     * Reject review
     */
    public function reject(ProductReview $review)
    {
        return DB::transaction(function () use ($review) {
            $review->update(['is_approved' => false]);

            // Clear cache
            $this->clearReviewCache($review->product);

            return $review;
        });
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful(ProductReview $review, User $user)
    {
        // Check if user already voted
        $voted = Cache::get("review_vote_{$review->id}_{$user->id}");

        if (!$voted) {
            $review->increment('helpful_votes');
            Cache::put("review_vote_{$review->id}_{$user->id}", 'helpful', now()->addDays(30));
        }

        return $review;
    }

    /**
     * Mark review as unhelpful
     */
    public function markUnhelpful(ProductReview $review, User $user)
    {
        // Check if user already voted
        $voted = Cache::get("review_vote_{$review->id}_{$user->id}");

        if (!$voted) {
            $review->increment('unhelpful_votes');
            Cache::put("review_vote_{$review->id}_{$user->id}", 'unhelpful', now()->addDays(30));
        }

        return $review;
    }

    /**
     * Get product reviews with filters
     */
    public function getProductReviews(Product $product, array $filters = [], $perPage = 15)
    {
        $query = $product->reviews()->with(['user']);

        if (!isset($filters['include_unapproved']) || !$filters['include_unapproved']) {
            $query->where('is_approved', true);
        }

        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (!empty($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        if (!empty($filters['verified_only'])) {
            $query->where('is_verified_purchase', true);
        }

        if (!empty($filters['with_images'])) {
            $query->whereNotNull('images');
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get review statistics for product
     */
    public function getReviewStats(Product $product)
    {
        $cacheKey = "product_review_stats_{$product->id}";

        return Cache::remember($cacheKey, 3600, function () use ($product) {
            $stats = [
                'total' => 0,
                'average' => 0,
                'verified_count' => 0,
                'with_images_count' => 0,
                'rating_breakdown' => [
                    5 => 0,
                    4 => 0,
                    3 => 0,
                    2 => 0,
                    1 => 0,
                ],
                'rating_percentages' => [],
            ];

            $reviews = $product->reviews()->where('is_approved', true)->get();

            if ($reviews->isEmpty()) {
                return $stats;
            }

            $stats['total'] = $reviews->count();
            $stats['average'] = round($reviews->avg('rating'), 1);
            $stats['verified_count'] = $reviews->where('is_verified_purchase', true)->count();
            $stats['with_images_count'] = $reviews->whereNotNull('images')->count();

            // Rating breakdown
            foreach ($reviews as $review) {
                $stats['rating_breakdown'][$review->rating]++;
            }

            // Calculate percentages
            foreach ($stats['rating_breakdown'] as $rating => $count) {
                $stats['rating_percentages'][$rating] = $stats['total'] > 0
                    ? round(($count / $stats['total']) * 100)
                    : 0;
            }

            return $stats;
        });
    }

    /**
     * Check if purchase is verified
     */
    protected function checkVerifiedPurchase(User $user, Product $product)
    {
        if (!method_exists($user, 'orders')) {
            return false;
        }

        // Check any completed order
        return $user->orders()
            ->where('status', 'completed')
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->exists();
    }

    /**
     * Clear review cache for product
     */
    protected function clearReviewCache(Product $product)
    {
        Cache::forget("product_review_stats_{$product->id}");
        Cache::forget("product_reviews_{$product->id}");
    }
}
