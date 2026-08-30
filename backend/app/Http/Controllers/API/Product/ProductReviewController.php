<?php

namespace App\Http\Controllers\API\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductReviewRequest;
use App\Http\Resources\Product\ProductReviewResource;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\ProductReviewService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller {
    use ApiResponseTrait;

    protected $reviewService;

    private function currentUserIsAdmin(): bool {
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    public function __construct(ProductReviewService $reviewService) {
        $this->reviewService = $reviewService;
    }

    /**
     * List product reviews
     */
    public function index(Product $product, ProductReviewRequest $request): JsonResponse {
        $filters = $request->validated();
        $perPage = $request->get('per_page', 15);

        $reviews = $this->reviewService->getProductReviews($product, $filters, $perPage);

        return $this->successResponse(
            ProductReviewResource::collection($reviews),
            'Reviews retrieved successfully'
        );
    }

    /**
     * Create review
     */
    public function store(ProductReviewRequest $request, Product $product): JsonResponse {
        try {
            $review = $this->reviewService->createForProduct(
                $product,
                Auth::user(),
                $request->validated()
            );

            return $this->createResponse(
                new ProductReviewResource($review),
                'Review submitted successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to submit review', 500, $e->getMessage());
        }
    }

    /**
     * Show review
     */
    public function show(Product $product, ProductReview $review): JsonResponse {
        if ($review->product_id !== $product->id) {
            return $this->notFoundResponse('Review does not belong to this product');
        }

        return $this->successResponse(
            new ProductReviewResource($review->load(['user'])),
            'Review retrieved successfully'
        );
    }

    /**
     * Update review
     */
    public function update(ProductReviewRequest $request, Product $product, ProductReview $review): JsonResponse {
        if ($review->product_id !== $product->id) {
            return $this->notFoundResponse('Review does not belong to this product');
        }

        // Check if user owns the review or is admin
        if ($review->user_id !== Auth::id() && ! $this->currentUserIsAdmin()) {
            return $this->forbiddenResponse('Unauthorized to update this review');
        }

        if ($review->is_approved && ! $this->currentUserIsAdmin()) {
            return $this->errorResponse(
                'This review has been approved and cannot be modified. Please contact support if you need to make changes.',
                422
            );
        }

        try {
            $review = $this->reviewService->update($review, $request->validated());

            return $this->updatedResponse(
                new ProductReviewResource($review),
                'Review updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update review', 500, $e->getMessage());
        }
    }

    /**
     * Delete review
     */
    public function destroy(Product $product, ProductReview $review): JsonResponse {
        if ($review->product_id !== $product->id) {
            return $this->notFoundResponse('Review does not belong to this product');
        }

        // Check if user owns the review or is admin
        if ($review->user_id !== Auth::id() && ! $this->currentUserIsAdmin()) {
            return $this->forbiddenResponse('Unauthorized to delete this review');
        }

        try {
            $this->reviewService->delete($review);

            return $this->deletedResponse('Review deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete review', 500, $e->getMessage());
        }
    }

    /**
     * Approve review (Admin only)
     */
    public function approve(Product $product, ProductReview $review): JsonResponse {

        if ($review->product_id !== $product->id) {
            return $this->notFoundResponse('Review does not belong to this product');
        }

        try {
            $review = $this->reviewService->approve($review);

            return $this->updatedResponse(
                new ProductReviewResource($review),
                'Review approved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve review', 500, $e->getMessage());
        }
    }

    /**
     * Reject review (Admin only)
     */
    public function reject(Product $product, ProductReview $review): JsonResponse {
        if ($review->product_id !== $product->id) {
            return $this->notFoundResponse('Review does not belong to this product');
        }

        try {
            $review = $this->reviewService->reject($review);

            return $this->updatedResponse(
                new ProductReviewResource($review),
                'Review rejected successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reject review', 500, $e->getMessage());
        }
    }

    /**
     * Mark review as helpful
     */
    public function helpful(Product $product, ProductReview $review): JsonResponse {
        if ($review->product_id !== $product->id) {
            return $this->notFoundResponse('Review does not belong to this product');
        }

        try {
            $review = $this->reviewService->markHelpful($review, Auth::user());

            return $this->successResponse(
                ['helpful_votes' => $review->helpful_votes],
                'Thank you for your feedback'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark review', 500, $e->getMessage());
        }
    }

    /**
     * Mark review as unhelpful
     */
    public function unhelpful(Product $product, ProductReview $review): JsonResponse {
        if ($review->product_id !== $product->id) {
            return $this->notFoundResponse('Review does not belong to this product');
        }

        try {
            $review = $this->reviewService->markUnhelpful($review, Auth::user());

            return $this->successResponse(
                ['unhelpful_votes' => $review->unhelpful_votes],
                'Thank you for your feedback'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark review', 500, $e->getMessage());
        }
    }

    /**
     * Get review statistics
     */
    public function stats(Product $product): JsonResponse {
        $stats = $this->reviewService->getReviewStats($product);

        return $this->successResponse($stats, 'Review statistics retrieved successfully');
    }
}
