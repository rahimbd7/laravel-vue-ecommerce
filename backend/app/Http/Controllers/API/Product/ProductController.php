<?php
namespace App\Http\Controllers\API\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Requests\Product\ProductSearchRequest;
use App\Http\Resources\Product\ProductCollectionResource;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Services\ProductReviewService;
use App\Services\ProductService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    use ApiResponseTrait;

    protected $productService;
    protected $reviewService;

    private function isOwnedByAuthenticatedVendor(Product $product): bool
    {
        $user = Auth::user();
        $vendor = $user?->vendor;
        $vendorId = $vendor?->id;

        return $vendorId && (int) $product->vendor_id === (int) $vendorId;
    }

    public function __construct(ProductService $productService, ProductReviewService $reviewService)
    {
        $this->productService = $productService;
        $this->reviewService = $reviewService;
    }

    public function index(ProductSearchRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = $request->get('per_page', 15);

        $products = $this->productService->search($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => new ProductCollectionResource($products),
            'message' => 'Products retrieved successfully',
        ]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user?->vendor;

            if (!$vendor) {
                return $this->errorResponse('Vendor not found', 404);
            }

            if (!$vendor->is_verified) {
                return $this->errorResponse('Your vendor account is not verified. Please wait for admin approval.', 403);
            }

            $data = $request->validated();
            $data['vendor_id'] = $vendor->id;

            $variations = $data['variations'] ?? [];

            // ✅ Get images from request input (Cloudinary URLs) NOT from files
            $images = $request->input('images', []);

            // ✅ Remove images from data to avoid validation issues
            unset($data['images']);

            $product = $this->productService->create($data, $images, $variations);

            return $this->successResponse(
                new ProductResource($product),
                'Product created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show(Product $product): JsonResponse
    {
        $product->load([
            'images' => fn($q) => $q->ordered(),
            'variations' => fn($q) => $q->visible()->ordered()->with('image'),
            'category',
            'vendor',
            'approvedReviews' => fn($q) => $q->latest()->limit(10)->with('user'),
        ]);

        $product->increment('review_count');

        $stats = $this->reviewService->getReviewStats($product);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
            'meta' => ['review_stats' => $stats],
            'message' => 'Product retrieved successfully',
        ]);
    }

    public function getBySlug(string $slug): JsonResponse
    {
        try {
            $product = $this->productService->getBySlug($slug);

            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $product->load([
                'approvedReviews' => fn($q) => $q->latest()->limit(10)->with('user'),
            ]);

            $stats = $this->reviewService->getReviewStats($product);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
                'meta' => ['review_stats' => $stats],
                'message' => 'Product retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        try {
            if (!$this->isOwnedByAuthenticatedVendor($product)) {
                return $this->forbiddenResponse('You are not authorized to update this product');
            }

            $data = $request->validated();
            $variations = $data['variations'] ?? [];

            // ✅ Get images from request input (Cloudinary URLs) NOT from files
            $images = $request->input('images', []);

            // ✅ Remove images from data to avoid validation issues
            unset($data['images']);

            $product = $this->productService->update($product, $data, $images, $variations);

            return $this->successResponse(
                new ProductResource($product),
                'Product updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            if (!$this->isOwnedByAuthenticatedVendor($product)) {
                return $this->forbiddenResponse('Unauthorized to delete this product');
            }

            $force = request()->boolean('force', false);
            $this->productService->delete($product, $force);

            return response()->json([
                'success' => true,
                'message' => $force ? 'Product permanently deleted' : 'Product moved to trash',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function related(Product $product): JsonResponse
    {
        $related = $this->productService->getRelatedProducts($product, request('limit', 10));

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($related),
            'message' => 'Related products retrieved successfully',
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = $user?->vendor;
        $vendorId = $vendor?->id;

        $stats = $this->productService->getStats($vendorId);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Product stats retrieved successfully',
        ]);
    }
}
