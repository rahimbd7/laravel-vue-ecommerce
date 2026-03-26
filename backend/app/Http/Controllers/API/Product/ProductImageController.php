<?php
// app/Http/Controllers/ProductImageController.php

namespace App\Http\Controllers\API\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductImageRequest;
use App\Http\Resources\Product\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductImageController extends Controller
{
    use ApiResponseTrait;

    protected $imageService;

    private function ensureProductOwnership(Product $product): ?JsonResponse
    {
        $user = Auth::user();
        $vendorId = $user?->vendor_id ?? $user?->vendor?->id;

        if (! $vendorId || (int) $product->vendor_id !== (int) $vendorId) {
            return $this->forbiddenResponse('Unauthorized to manage this product');
        }

        return null;
    }

    public function __construct(ProductImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * List product images
     */
    public function index(Product $product): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        $images = $product->images()->ordered()->get();

        return $this->successResponse(
            ProductImageResource::collection($images),
            'Product images retrieved successfully'
        );
    }

    /**
     * Upload image for product
     */
    public function store(ProductImageRequest $request, Product $product): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        try {
            $images = $request->file('images');
            $primaryIndex = $request->input('primary_index');

            $uploadedImages = $this->imageService->attachToProduct($product, $images, $primaryIndex);

            return $this->createResponse(
                ProductImageResource::collection($uploadedImages),
                'Images uploaded successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload images', 500, $e->getMessage());
        }
    }

    /**
     * Show specific image
     */
    public function show(Product $product, ProductImage $image): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        if ($image->product_id !== $product->id) {
            return $this->notFoundResponse('Image does not belong to this product');
        }

        return $this->successResponse(
            new ProductImageResource($image),
            'Image retrieved successfully'
        );
    }

    /**
     * Update image
     */
    public function update(ProductImageRequest $request, Product $product, ProductImage $image): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        if ($image->product_id !== $product->id) {
            return $this->notFoundResponse('Image does not belong to this product');
        }

        try {
            $data = $request->validated();

            $replacementFile = $request->file('image');
            if (! $replacementFile && $request->hasFile('images')) {
                $images = $request->file('images', []);
                $replacementFile = is_array($images) ? ($images[0] ?? null) : $images;
            }

            if ($replacementFile) {
                $image = $this->imageService->replaceFile($product, $image, $replacementFile, $data);
            } else {
                $image->update($data);
                $image->refresh();
            }

            return $this->updatedResponse(
                new ProductImageResource($image),
                'Image updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update image', 500, $e->getMessage());
        }
    }

    /**
     * Delete image
     */
    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        if ($image->product_id !== $product->id) {
            return $this->notFoundResponse('Image does not belong to this product');
        }

        try {
            $this->imageService->delete($image);

            return $this->deletedResponse('Image deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete image', 500, $e->getMessage());
        }
    }

    /**
     * Set primary image
     */
    public function setPrimary(Product $product, ProductImage $image): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        if ($image->product_id !== $product->id) {
            return $this->notFoundResponse('Image does not belong to this product');
        }

        try {
            $this->imageService->setPrimary($product, $image);

            return $this->updatedResponse(
                new ProductImageResource($image),
                'Primary image set successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to set primary image', 500, $e->getMessage());
        }
    }

    /**
     * Reorder images
     */
    public function reorder(Product $product, ProductImageRequest $request): JsonResponse
    {
        if ($response = $this->ensureProductOwnership($product)) {
            return $response;
        }

        try {
            $order = $request->input('order', []);
            $this->imageService->reorder($product, $order);

            return $this->updatedResponse(null, 'Images reordered successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reorder images', 500, $e->getMessage());
        }
    }
}
