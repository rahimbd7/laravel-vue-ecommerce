<?php

namespace App\Http\Controllers\Api\Wishlist;

use App\Http\Controllers\Controller;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WishlistController extends Controller
{
    protected WishlistService $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);
        $wishlist = $this->wishlistService->getWishlistWithPagination($user, $perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Wishlist retrieved successfully',
            'data' => $wishlist
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        try {
            $user = $request->user();
            $wishlist = $this->wishlistService->addToWishlist($user, $request->product_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to wishlist',
                'data' => $wishlist->load('product')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        try {
            $user = $request->user();
            $this->wishlistService->removeFromWishlist($user, $productId);

            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from wishlist'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        try {
            $user = $request->user();
            $result = $this->wishlistService->toggleWishlist($user, $request->product_id);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function check(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();
        $isInWishlist = $this->wishlistService->checkInWishlist($user, $productId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'product_id' => $productId,
                'in_wishlist' => $isInWishlist
            ]
        ]);
    }

    public function count(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->wishlistService->getWishlistCount($user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'count' => $count
            ]
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->wishlistService->clearWishlist($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Wishlist cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
