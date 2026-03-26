<?php
// app/Services/ProductService.php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected $imageService;
    protected $variationService;
    protected $reviewService;

    public function __construct(
        ProductImageService $imageService,
        ProductVariationService $variationService,
        ProductReviewService $reviewService
    ) {
        $this->imageService = $imageService;
        $this->variationService = $variationService;
        $this->reviewService = $reviewService;
    }

    /**
     * Create product with all relations
     */
    public function create(array $data, array $images = [], array $variations = [])
    {
        return DB::transaction(function () use ($data, $images, $variations) {
            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku($data['name']);
            }

            // Create product
            $product = Product::create($data);

            // Handle images
            if (!empty($images)) {
                $this->imageService->attachToProduct($product, $images);
            }

            // Handle variations
            if (!empty($variations) && ($data['has_variations'] ?? false)) {
                foreach ($variations as $variationData) {
                    $this->variationService->createForProduct($product, $variationData);
                }

                // Update product price based on variations
                $product->price = $product->getLowestPrice();
                $product->saveQuietly();
            }

            return $product->load(['images', 'variations', 'category', 'vendor']);
        });
    }

    /**
     * Update product
     */
    public function update(Product $product, array $data, array $images = [], array $variations = [])
    {
        return DB::transaction(function () use ($product, $data, $images, $variations) {
            // Update product
            $product->update($data);

            // Handle images
            if (!empty($images)) {
                $this->imageService->syncForProduct($product, $images);
            }

            // Handle variations
            if (isset($data['has_variations'])) {
                if ($data['has_variations'] && !empty($variations)) {
                    $this->variationService->bulkUpdate($product, $variations);
                } elseif (!$data['has_variations']) {
                    $product->variations()->delete();
                }
            }

            // Update product price if has variations
            if ($product->has_variations) {
                $product->price = $product->getLowestPrice();
                $product->saveQuietly();
            }

            return $product->fresh(['images', 'variations', 'category', 'vendor']);
        });
    }

    /**
     * Delete product
     */
    public function delete(Product $product, $force = false)
    {
        return DB::transaction(function () use ($product, $force) {
            // Delete images from storage
            foreach ($product->images as $image) {
                $this->imageService->delete($image);
            }

            if ($force) {
                return $product->forceDelete();
            }

            return $product->delete();
        });
    }

    /**
     * Search products with filters
     */
    public function search(array $filters = [], $perPage = 15)
    {
        $query = Product::query()
            ->with(['primaryImage', 'category', 'vendor'])
            ->withCount(['reviews as approved_reviews_count' => function ($q) {
                $q->where('is_approved', true);
            }]);

        // Apply filters
        if (isset($filters['visible'])) {
            $query->where('is_visible', $filters['visible']);
        } else {
            $query->visible();
        }

        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (!empty($filters['vendor_id'])) {
            $query->byVendor($filters['vendor_id']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['tags'])) {
            $query->whereJsonContains('tags', $filters['tags']);
        }

        if (!empty($filters['in_stock'])) {
            $query->inStock();
        }

        if (!empty($filters['featured'])) {
            $query->featured();
        }

        if (!empty($filters['has_variations'])) {
            $query->where('has_variations', true);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $allowedSorts = [
            'price' => 'price',
            'created_at' => 'created_at',
            'name' => 'name',
            'popularity' => 'sold_count',
            'rating' => 'average_rating',
            'newest' => 'created_at',
        ];

        if (isset($allowedSorts[$sortBy])) {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get related products
     */
    public function getRelatedProducts(Product $product, $limit = 10)
    {
        $tags = collect($product->tags ?? [])
            ->filter(fn($tag) => is_string($tag) && trim($tag) !== '')
            ->map(fn($tag) => trim($tag))
            ->values()
            ->all();

        $nameTokens = collect(preg_split('/\s+/', strtolower((string) $product->name)))
            ->filter(fn($token) => strlen($token) >= 3)
            ->unique()
            ->take(3)
            ->values()
            ->all();

        $query = Product::query()
            ->visible()
            ->where('id', '!=', $product->id)
            ->with(['primaryImage']);

        // Candidate pool for "related": category match, tag overlap, or textual similarity.
        $query->where(function ($q) use ($product, $tags, $nameTokens) {
            $q->where('category_id', $product->category_id);

            if (!empty($tags)) {
                $q->orWhere(function ($tagQuery) use ($tags) {
                    foreach ($tags as $tag) {
                        $tagQuery->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            if (!empty($nameTokens)) {
                $q->orWhere(function ($nameQuery) use ($nameTokens) {
                    foreach ($nameTokens as $token) {
                        $like = '%' . $token . '%';
                        $nameQuery->orWhere('name', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    }
                });
            }
        });

        // Weighted ranking for deterministic, high-quality related results.
        $scoreParts = ["(case when category_id = ? then 60 else 0 end)"];
        $scoreBindings = [$product->category_id];

        foreach ($tags as $tag) {
            $scoreParts[] = "(case when JSON_CONTAINS(tags, JSON_QUOTE(?)) then 20 else 0 end)";
            $scoreBindings[] = $tag;
        }

        foreach ($nameTokens as $token) {
            $scoreParts[] = "(case when name like ? or slug like ? then 10 else 0 end)";
            $scoreBindings[] = '%' . $token . '%';
            $scoreBindings[] = '%' . $token . '%';
        }

        $scoreParts[] = "(case when stock_status in ('in_stock', 'low_stock') then 8 else 0 end)";
        $scoreParts[] = "(case when is_featured = 1 then 5 else 0 end)";

        $scoreSql = implode(' + ', $scoreParts) . ' as relevance_score';

        return $query
            ->select('products.*')
            ->selectRaw($scoreSql, $scoreBindings)
            ->orderByDesc('relevance_score')
            ->orderByDesc('sold_count')
            ->orderByDesc('view_count')
            ->orderByDesc('average_rating')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate unique SKU
     */
    protected function generateSku($name)
    {
        $base = 'PRD-' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($name, 0, 10)));
        $sku = $base;
        $counter = 1;

        while (Product::where('sku', $sku)->exists()) {
            $sku = $base . '-' . $counter;
            $counter++;
        }

        return $sku;
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(array $productIds, array $data)
    {
        return DB::transaction(function () use ($productIds, $data) {
            return Product::whereIn('id', $productIds)->update($data);
        });
    }

    /**
     * Get product statistics
     */
    public function getStats($vendorId = null)
    {
        $query = Product::query();

        if ($vendorId) {
            $query->byVendor($vendorId);
        }

        return [
            'total' => $query->count(),
            'active' => (clone $query)->where('is_visible', true)->count(),
            'out_of_stock' => (clone $query)->where('stock_status', 'out_of_stock')->count(),
            'low_stock' => (clone $query)->where('stock_status', 'low_stock')->count(),
            'featured' => (clone $query)->where('is_featured', true)->count(),
            'with_variations' => (clone $query)->where('has_variations', true)->count(),
            'average_price' => (clone $query)->avg('price'),
            'total_value' => (clone $query)->sum(DB::raw('price * stock_quantity')),
            'recent_added' => (clone $query)->where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }
}
