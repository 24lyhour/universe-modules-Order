<?php

namespace Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Http\Requests\Api\V1\StoreOutletReviewRequest;
use Modules\Order\Http\Requests\Api\V1\UpdateOutletReviewRequest;
use Modules\Order\Http\Resources\OutletReviewResource;
use Modules\Order\Models\OutletReview;

class OutletReviewController extends Controller
{
    /**
     * Get current customer's outlet reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $customer = $request->user();
        $perPage = $request->integer('per_page', 10);

        $reviews = OutletReview::with(['customer', 'outlet', 'order'])
            ->where('customer_id', $customer->id)
            ->active()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => OutletReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Get reviews for a specific outlet.
     */
    public function outletReviews(Request $request, int $outletId): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);

        $reviews = OutletReview::with(['customer'])
            ->where('outlet_id', $outletId)
            ->active()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => OutletReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Get review summary/stats for an outlet.
     */
    public function outletSummary(int $outletId): JsonResponse
    {
        $reviews = OutletReview::where('outlet_id', $outletId)->active();

        return response()->json([
            'data' => [
                'average_rating' => round($reviews->avg('overall_rating') ?? 0, 1),
                'average_food' => round($reviews->avg('food_rating') ?? 0, 1),
                'average_service' => round($reviews->avg('service_rating') ?? 0, 1),
                'average_delivery' => round($reviews->avg('delivery_rating') ?? 0, 1),
                'average_packaging' => round($reviews->avg('packaging_rating') ?? 0, 1),
                'total_reviews' => $reviews->count(),
                'rating_5' => (clone $reviews)->where('overall_rating', 5)->count(),
                'rating_4' => (clone $reviews)->where('overall_rating', 4)->count(),
                'rating_3' => (clone $reviews)->where('overall_rating', 3)->count(),
                'rating_2' => (clone $reviews)->where('overall_rating', 2)->count(),
                'rating_1' => (clone $reviews)->where('overall_rating', 1)->count(),
                'available_tags' => OutletReview::TAGS,
            ],
        ]);
    }

    /**
     * Store a new outlet review.
     */
    public function store(StoreOutletReviewRequest $request): JsonResponse
    {
        $customer = $request->user();

        $review = OutletReview::create([
            ...$request->validated(),
            'customer_id' => $customer->id,
            'is_active' => true,
        ]);

        $review->load(['customer', 'outlet', 'order']);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => new OutletReviewResource($review),
        ], 201);
    }

    /**
     * Update an existing outlet review.
     */
    public function update(UpdateOutletReviewRequest $request, int $id): JsonResponse
    {
        $customer = $request->user();

        $review = OutletReview::where('customer_id', $customer->id)
            ->findOrFail($id);

        $review->update($request->validated());
        $review->load(['customer', 'outlet', 'order']);

        return response()->json([
            'message' => 'Review updated successfully.',
            'data' => new OutletReviewResource($review),
        ]);
    }

    /**
     * Delete an outlet review.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $customer = $request->user();

        $review = OutletReview::where('customer_id', $customer->id)
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
        $review = OutletReview::active()->findOrFail($id);
        $review->incrementHelpful();

        return response()->json([
            'message' => 'Marked as helpful.',
            'data' => ['helpful_count' => $review->fresh()->helpful_count],
        ]);
    }
}
