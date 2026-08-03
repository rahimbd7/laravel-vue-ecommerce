<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Admin\AdminProductService;
use App\Trait\ApiResponseTrait;
use App\Trait\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminProductController extends Controller
{
    use ApiResponseTrait, LogsActivity;

    protected $adminProductService;

    public function __construct(AdminProductService $adminProductService)
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
        $this->adminProductService = $adminProductService;
    }

    /**
     * Get paginated list of products
     * GET /api/admin/products
     */
    public function index(Request $request)
    {
        try {
            $products = $this->adminProductService->getProducts($request->all(), $request->per_page ?? 15);
            return $this->paginationResponse($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get product statistics
     * GET /api/admin/products/stats
     */
    public function stats()
    {
        try {
            $stats = $this->adminProductService->getStats();
            return $this->successResponse($stats, 'Product statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get single product details
     * GET /api/admin/products/{id}
     */
    public function show($id)
    {
        try {
            $product = $this->adminProductService->getProduct($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            return $this->successResponse($product, 'Product details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Update product
     * PUT /api/admin/products/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::withTrashed()->find($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'compare_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'sku' => 'sometimes|string|unique:products,sku,' . $id,
                'stock_quantity' => 'sometimes|integer|min:0',
                'stock_status' => 'sometimes|in:in_stock,out_of_stock,low_stock',
                'category_id' => 'sometimes|exists:categories,id',
                'is_visible' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'has_variations' => 'sometimes|boolean',
            ]);

            $oldValues = $product->toArray();

            $updatedProduct = $this->adminProductService->updateProduct($product, $validated);

            // Log the activity
            $this->logActivity(
                action: 'product_updated',
                referenceType: 'product',
                referenceId: $product->id,
                amount: $product->price,
                status: $product->is_visible ? 'active' : 'inactive',
                description: "Admin updated product: {$product->name}",
                metadata: [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_values' => $oldValues,
                    'new_values' => $validated,
                    'updated_by' => Auth::user()?->name ?? 'System',
                ]
            );

            return $this->successResponse($updatedProduct, 'Product updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Delete product (soft delete)
     * DELETE /api/admin/products/{id}
     */
    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $productName = $product->name;

            $this->adminProductService->deleteProduct($product);

            // Log the activity
            $this->logActivity(
                action: 'product_deleted',
                referenceType: 'product',
                referenceId: $product->id,
                amount: $product->price,
                status: 'deleted',
                description: "Admin soft-deleted product: {$productName}",
                metadata: [
                    'product_id' => $product->id,
                    'product_name' => $productName,
                    'deleted_by' => Auth::user()?->name ?? 'System',
                ]
            );

            return $this->deletedResponse('Product moved to trash successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Restore soft-deleted product
     * POST /api/admin/products/{id}/restore
     */
    public function restore($id)
    {
        try {
            $product = $this->adminProductService->restoreProduct($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $productName = $product->name;

            // Log the activity
            $this->logActivity(
                action: 'product_restored',
                referenceType: 'product',
                referenceId: $product->id,
                amount: $product->price,
                status: 'active',
                description: "Admin restored product: {$productName}",
                metadata: [
                    'product_id' => $product->id,
                    'product_name' => $productName,
                    'restored_by' => Auth::user()?->name ?? 'System',
                ]
            );

            return $this->successResponse($product, 'Product restored successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Toggle product visibility
     * PUT /api/admin/products/{id}/toggle-visibility
     */
    public function toggleVisibility($id)
    {
        try {
            $product = Product::withTrashed()->find($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $productName = $product->name;
            $newVisibility = !$product->is_visible;

            $this->adminProductService->toggleVisibility($product);

            $status = $newVisibility ? 'visible' : 'hidden';

            // Log the activity
            $this->logActivity(
                action: 'product_visibility_toggled',
                referenceType: 'product',
                referenceId: $product->id,
                amount: $product->price,
                status: $status,
                description: "Admin toggled product visibility: {$productName} to {$status}",
                metadata: [
                    'product_id' => $product->id,
                    'product_name' => $productName,
                    'new_visibility' => $newVisibility,
                    'updated_by' => Auth::user()?->name ?? 'System',
                ]
            );

            return $this->successResponse(
                ['is_visible' => $newVisibility],
                "Product visibility updated to " . ($newVisibility ? 'visible' : 'hidden')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Bulk action on products
     * POST /api/admin/products/bulk-action
     */
    public function bulkAction(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'exists:products,id',
                'action' => 'required|in:delete,restore,activate,deactivate,feature,unfeature',
            ]);

            $count = $this->adminProductService->bulkAction(
                $validated['product_ids'],
                $validated['action']
            );

            $actionLabels = [
                'delete' => 'moved to trash',
                'restore' => 'restored',
                'activate' => 'activated',
                'deactivate' => 'deactivated',
                'feature' => 'featured',
                'unfeature' => 'unfeatured',
            ];

            // Log the activity
            $this->logActivity(
                action: 'products_bulk_' . $validated['action'],
                referenceType: 'product',
                referenceId: 0,
                amount: 0,
                status: 'success',
                description: "Admin performed bulk action: {$validated['action']} on {$count} products",
                metadata: [
                    'action' => $validated['action'],
                    'product_ids' => $validated['product_ids'],
                    'count' => $count,
                    'updated_by' => Auth::user()?->name ?? 'System',
                ]
            );

            return $this->successResponse(
                ['updated_count' => $count],
                "{$count} products {$actionLabels[$validated['action']]} successfully"
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get products by vendor
     * GET /api/admin/products/vendor/{vendorId}
     */
    public function vendorProducts($vendorId, Request $request)
    {
        try {
            $vendor = Vendor::find($vendorId);

            if (!$vendor) {
                return $this->notFoundResponse('Vendor not found');
            }

            $products = $this->adminProductService->getVendorProducts(
                $vendorId,
                $request->all(),
                $request->per_page ?? 15
            );

            return $this->paginationResponse($products, "Products for vendor: {$vendor->business_name}");
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Export products to CSV
     * GET /api/admin/products/export
     */
    public function export(Request $request)
    {
        try {
            $data = $this->adminProductService->getExportData($request->all());

            $headers = [
                'ID', 'Name', 'SKU', 'Price', 'Stock', 'Status', 'Vendor', 'Category', 'Featured', 'Created At'
            ];

            $filename = "products_export_" . now()->format('Y-m-d') . '.csv';

            return response()->streamDownload(function () use ($headers, $data) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($data as $row) {
                    fputcsv($handle, array_values($row));
                }
                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
