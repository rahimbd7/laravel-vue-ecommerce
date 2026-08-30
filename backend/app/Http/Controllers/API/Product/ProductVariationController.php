<?php

namespace App\Http\Controllers\API\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductVariationRequest;
use App\Http\Resources\Product\ProductVariationResource;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Services\ProductVariationService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


class ProductVariationController extends Controller
{
    use ApiResponseTrait;

    protected $variationService;

    private function ensureProductOwnership(Product $product): ?JsonResponse
    {
        $user = Auth::user();
        $vendorId = $user?->vendor_id ?? $user?->vendor?->id;

        if (! $vendorId || (int) $product->vendor_id !== (int) $vendorId) {
            return $this->forbiddenResponse('Unauthorized to manage this product');
        }

        return null;
    }

    public function __construct(ProductVariationService $variationService)
    {
        $this->variationService = $variationService;
    }

    /**
     * List product variations
     */
    public function index(Product $product): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        $variations = $product->variations()
            ->with(['image'])
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductVariationResource::collection($variations),
            'message' => 'Product variations retrieved successfully'
        ]);
    }

    /**
     * Create variation
     */
    public function store(ProductVariationRequest $request, Product $product): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        try {
            $variation = $this->variationService->createForProduct($product, $request->validated());

            return response()->json([
                'success' => true,
                'data' => new ProductVariationResource($variation->load('image')),
                'message' => 'Variation created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create variation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show variation
     */
    public function show(Product $product, ProductVariation $variation): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        if ($variation->product_id !== $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'Variation does not belong to this product'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductVariationResource($variation->load('image')),
            'message' => 'Variation retrieved successfully'
        ]);
    }

    /**
     * Update variation
     */
    public function update(ProductVariationRequest $request, Product $product, ProductVariation $variation): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        if ($variation->product_id !== $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'Variation does not belong to this product'
            ], 404);
        }

        try {
            $variation = $this->variationService->update($variation, $request->validated());

            return response()->json([
                'success' => true,
                'data' => new ProductVariationResource($variation->load('image')),
                'message' => 'Variation updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update variation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete variation
     */
    public function destroy(Product $product, ProductVariation $variation): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        if ($variation->product_id !== $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'Variation does not belong to this product'
            ], 404);
        }

        try {
            $this->variationService->delete($variation);

            return response()->json([
                'success' => true,
                'message' => 'Variation deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete variation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set default variation
     */
    public function setDefault(Product $product, ProductVariation $variation): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        try {
            $variation = $this->variationService->setDefault($product, $variation);

            return response()->json([
                'success' => true,
                'data' => new ProductVariationResource($variation),
                'message' => 'Default variation set successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default variation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder variations
     */
    public function reorder(Product $product, ProductVariationRequest $request): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        try {
            $order = $request->input('order', []);
            $this->variationService->reorder($product, $order);

            return response()->json([
                'success' => true,
                'message' => 'Variations reordered successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder variations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock
     */
    public function updateStock(ProductVariationRequest $request, Product $product, ProductVariation $variation): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        if ($variation->product_id !== $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'Variation does not belong to this product'
            ], 404);
        }

        try {
            $quantity = $request->input('quantity');
            $operation = $request->input('operation', 'set');

            $variation = $this->variationService->updateStock($variation, $quantity, $operation);

            return response()->json([
                'success' => true,
                'data' => new ProductVariationResource($variation),
                'message' => 'Stock updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update variations
     */
    public function bulkUpdate(ProductVariationRequest $request, Product $product): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        try {
            $variations = $request->input('variations', []);
            $updated = $this->variationService->bulkUpdate($product, $variations);

            return response()->json([
                'success' => true,
                'data' => ProductVariationResource::collection($updated),
                'message' => 'Variations updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update variations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
