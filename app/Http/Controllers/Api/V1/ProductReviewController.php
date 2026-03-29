<?php

namespace Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Http\Requests\Api\V1\StoreProductReviewRequest;
use Modules\Order\Http\Requests\Api\V1\UpdateProductReviewRequest;
use Modules\Order\Http\Resources\ProductReviewResource;
use Modules\Order\Models\ProductReview;

class ProductReviewController extends Controller
{
    /**
     * Get current customer's product reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $customer = $request->user();
        $perPage = $request->integer('per_page', 10);

        $reviews = ProductReview::with(['customer', 'product', 'order'])
            ->where('customer_id', $customer->id)
            ->active()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => ProductReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Get reviews for a specific product.
     */
    public function productReviews(Request $request, int $productId): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);

        $reviews = ProductReview::with(['customer'])
            ->where('product_id', $productId)
            ->active()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => ProductReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Get review summary/stats for a product.
     */
    public function productSummary(int $productId): JsonResponse
    {
        $reviews = ProductReview::where('product_id', $productId)->active();

        return response()->json([
            'data' => [
                'average_rating' => round($reviews->avg('rating') ?? 0, 1),
                'total_reviews' => $reviews->count(),
                'rating_5' => (clone $reviews)->where('rating', 5)->count(),
                'rating_4' => (clone $reviews)->where('rating', 4)->count(),
                'rating_3' => (clone $reviews)->where('rating', 3)->count(),
                'rating_2' => (clone $reviews)->where('rating', 2)->count(),
                'rating_1' => (clone $reviews)->where('rating', 1)->count(),
            ],
        ]);
    }

    /**
     * Store a new product review.
     */
    public function store(StoreProductReviewRequest $request): JsonResponse
    {
        $customer = $request->user();

        $review = ProductReview::create([
            ...$request->validated(),
            'customer_id' => $customer->id,
            'is_active' => true,
        ]);

        $review->load(['customer', 'product', 'order']);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => new ProductReviewResource($review),
        ], 201);
    }

    /**
     * Update an existing product review.
     */
    public function update(UpdateProductReviewRequest $request, int $id): JsonResponse
    {
        $customer = $request->user();

        $review = ProductReview::where('customer_id', $customer->id)
            ->findOrFail($id);

        $review->update($request->validated());
        $review->load(['customer', 'product', 'order']);

        return response()->json([
            'message' => 'Review updated successfully.',
            'data' => new ProductReviewResource($review),
        ]);
    }

    /**
     * Delete a product review.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $customer = $request->user();

        $review = ProductReview::where('customer_id', $customer->id)
            ->findOrFail($id);

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully.',
        ]);
    }

    /**
     * Mark a review as helpful.
     */
    public function markHelpful(int $id): JsonResponse
    {
        $review = ProductReview::active()->findOrFail($id);
        $review->incrementHelpful();

        return response()->json([
            'message' => 'Marked as helpful.',
            'data' => ['helpful_count' => $review->fresh()->helpful_count],
        ]);
    }
}
