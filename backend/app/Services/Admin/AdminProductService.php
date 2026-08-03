<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminProductService
{
    /**
     * Get paginated products with filters
     */
    public function getProducts(array $filters = [], $perPage = 15)
    {
        $query = Product::with(['vendor', 'category', 'primaryImage'])
            ->withCount(['reviews as approved_reviews_count' => function ($q) {
                $q->where('is_approved', true);
            }])
            ->withTrashed();

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        // Filter by vendor
        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_visible', true)->whereNull('deleted_at');
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_visible', false)->whereNull('deleted_at');
            } elseif ($filters['status'] === 'trashed') {
                $query->onlyTrashed();
            }
        }

        // Filter by stock status
        if (!empty($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        // Filter by price range
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Filter by variations
        if (!empty($filters['has_variations'])) {
            $query->where('has_variations', true);
        }

        // Filter by featured
        if (!empty($filters['is_featured'])) {
            $query->where('is_featured', true);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $allowedSorts = [
            'id', 'name', 'price', 'stock_quantity', 'created_at', 'updated_at'
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get product statistics
     */
    public function getStats(): array
    {
        return [
            'total' => Product::withTrashed()->count(),
            'active' => Product::where('is_visible', true)->count(),
            'inactive' => Product::where('is_visible', false)->count(),
            'trashed' => Product::onlyTrashed()->count(),
            'out_of_stock' => Product::where('stock_status', 'out_of_stock')->count(),
            'low_stock' => Product::where('stock_status', 'low_stock')->count(),
            'in_stock' => Product::where('stock_status', 'in_stock')->count(),
            'featured' => Product::where('is_featured', true)->count(),
            'with_variations' => Product::where('has_variations', true)->count(),
            'total_value' => Product::sum(DB::raw('price * stock_quantity')) ?? 0,
            'average_price' => Product::avg('price') ?? 0,
            'total_vendors' => Product::distinct('vendor_id')->count(),
            'categories' => Product::distinct('category_id')->count(),
            'recent_added' => Product::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * Get single product with relations
     */
    public function getProduct($id)
    {
        return Product::withTrashed()
            ->with([
                'vendor',
                'category',
                'images',
                'variations',
                'reviews' => function ($q) {
                    $q->latest()->limit(10);
                }
            ])
            ->find($id);
    }

    /**
     * Update product
     */
    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    /**
     * Delete product (soft delete)
     */
    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Restore product
     */
    public function restoreProduct($id): ?Product
    {
        $product = Product::onlyTrashed()->find($id);
        if ($product) {
            $product->restore();
            return $product;
        }
        return null;
    }

    /**
     * Toggle product visibility
     */
    public function toggleVisibility(Product $product): bool
    {
        $product->is_visible = !$product->is_visible;
        return $product->save();
    }

    /**
     * Bulk action on products
     */
    public function bulkAction(array $productIds, string $action): int
    {
        switch ($action) {
            case 'delete':
                return Product::whereIn('id', $productIds)->delete();

            case 'restore':
                return Product::onlyTrashed()->whereIn('id', $productIds)->restore();

            case 'activate':
                return Product::whereIn('id', $productIds)->update(['is_visible' => true]);

            case 'deactivate':
                return Product::whereIn('id', $productIds)->update(['is_visible' => false]);

            case 'feature':
                return Product::whereIn('id', $productIds)->update(['is_featured' => true]);

            case 'unfeature':
                return Product::whereIn('id', $productIds)->update(['is_featured' => false]);

            default:
                throw new \Exception('Invalid action');
        }
    }

    /**
     * Get products by vendor
     */
    public function getVendorProducts($vendorId, array $filters = [], $perPage = 15)
    {
        $query = Product::withTrashed()
            ->where('vendor_id', $vendorId)
            ->with(['category', 'primaryImage']);

        if (!empty($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Export products data
     */
    public function getExportData(array $filters = [])
    {
        return Product::with(['vendor', 'category'])
            ->when(!empty($filters['search']), function ($query, $search) {
                return $query->where('name', 'LIKE', "%{$search}%");
            })
            ->when(!empty($filters['vendor_id']), function ($query, $vendorId) {
                return $query->where('vendor_id', $vendorId);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'stock' => $product->stock_quantity,
                    'status' => $product->is_visible ? 'Active' : 'Inactive',
                    'vendor' => $product->vendor->business_name ?? 'N/A',
                    'category' => $product->category->name ?? 'N/A',
                    'featured' => $product->is_featured ? 'Yes' : 'No',
                    'created_at' => $product->created_at->format('Y-m-d H:i'),
                ];
            });
    }
}
