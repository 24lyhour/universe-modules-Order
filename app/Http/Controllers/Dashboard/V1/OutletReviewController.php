<?php

namespace Modules\Order\Http\Controllers\Dashboard\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Order\Http\Requests\Dashboard\V1\OutletReview\ReplyOutletReviewRequest;
use Modules\Order\Http\Requests\Dashboard\V1\OutletReview\StoreOutletReviewRequest;
use Modules\Order\Http\Requests\Dashboard\V1\OutletReview\UpdateOutletReviewRequest;
use Modules\Order\Http\Resources\OutletReviewResource;
use Modules\Order\Models\OutletReview;
use Modules\Outlet\Models\Outlet;
use Momentum\Modal\Modal;

class OutletReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'rating', 'outlet_id', 'is_active', 'has_reply']);
        $perPage = $request->integer('per_page', 10);

        $query = OutletReview::with(['customer', 'outlet', 'order'])
            ->latest();

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('comment', 'like', "%{$filters['search']}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"))
                    ->orWhereHas('outlet', fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"));
            });
        }

        if (!empty($filters['rating'])) {
            $query->where('overall_rating', $filters['rating']);
        }

        if (!empty($filters['outlet_id'])) {
            $query->where('outlet_id', $filters['outlet_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['has_reply'])) {
            $hasReply = filter_var($filters['has_reply'], FILTER_VALIDATE_BOOLEAN);
            $query->when($hasReply, fn ($q) => $q->whereNotNull('reply'))
                ->when(!$hasReply, fn ($q) => $q->whereNull('reply'));
        }

        $reviews = $query->paginate($perPage);
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('order::Dashboard/V1/OutletReview/Index', [
            'reviewItems' => OutletReviewResource::collection($reviews)->response()->getData(true),
            'filters' => $filters,
            'outlets' => $outlets,
            'stats' => $this->getStats(),
            'availableTags' => OutletReview::TAGS,
        ]);
    }

    public function show(OutletReview $outletReview): Response
    {
        $outletReview->load(['customer', 'outlet', 'order']);

        return Inertia::render('order::Dashboard/V1/OutletReview/Show', [
            'review' => (new OutletReviewResource($outletReview))->resolve(),
            'availableTags' => OutletReview::TAGS,
        ]);
    }

    public function create(): Modal
    {
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();

        return Inertia::modal('order::Dashboard/V1/OutletReview/Create', [
            'outlets' => $outlets,
            'availableTags' => OutletReview::TAGS,
        ])->baseRoute('order.outlet-reviews.index');
    }

    public function store(StoreOutletReviewRequest $request): RedirectResponse
    {
        OutletReview::create($request->validated());

        return redirect()->route('order.outlet-reviews.index')
            ->with('success', 'Outlet review created successfully.');
    }

    public function edit(OutletReview $outletReview): Modal
    {
        $outletReview->load(['customer', 'outlet']);

        return Inertia::modal('order::Dashboard/V1/OutletReview/Edit', [
            'review' => (new OutletReviewResource($outletReview))->resolve(),
            'availableTags' => OutletReview::TAGS,
        ])->baseRoute('order.outlet-reviews.index');
    }

    public function update(UpdateOutletReviewRequest $request, OutletReview $outletReview): RedirectResponse
    {
        $outletReview->update($request->validated());

        return redirect()->route('order.outlet-reviews.index')
            ->with('success', 'Outlet review updated successfully.');
    }

    public function destroy(OutletReview $outletReview): RedirectResponse
    {
        $outletReview->delete();

        return redirect()->route('order.outlet-reviews.index')
            ->with('success', 'Outlet review deleted successfully.');
    }

    public function replyModal(OutletReview $outletReview): Modal
    {
        $outletReview->load(['customer', 'outlet', 'order']);

        return Inertia::modal('order::Dashboard/V1/OutletReview/Reply', [
            'review' => (new OutletReviewResource($outletReview))->resolve(),
            'availableTags' => OutletReview::TAGS,
        ])->baseRoute('order.outlet-reviews.index');
    }

    public function reply(ReplyOutletReviewRequest $request, OutletReview $outletReview): RedirectResponse
    {
        $outletReview->addReply($request->validated()['reply']);

        return redirect()->back()->with('success', 'Reply added successfully.');
    }

    public function toggleActive(OutletReview $outletReview): RedirectResponse
    {
        $outletReview->update(['is_active' => !$outletReview->is_active]);

        $status = $outletReview->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Review {$status} successfully.");
    }

    protected function getStats(): array
    {
        return [
            'total' => OutletReview::count(),
            'active' => OutletReview::where('is_active', true)->count(),
            'pending_reply' => OutletReview::whereNull('reply')->count(),
            'average_rating' => round(OutletReview::avg('overall_rating') ?? 0, 1),
            'average_food' => round(OutletReview::avg('food_rating') ?? 0, 1),
            'average_service' => round(OutletReview::avg('service_rating') ?? 0, 1),
            'average_delivery' => round(OutletReview::avg('delivery_rating') ?? 0, 1),
            'average_packaging' => round(OutletReview::avg('packaging_rating') ?? 0, 1),
            '5_star' => OutletReview::where('overall_rating', 5)->count(),
            '4_star' => OutletReview::where('overall_rating', 4)->count(),
            '3_star' => OutletReview::where('overall_rating', 3)->count(),
            '2_star' => OutletReview::where('overall_rating', 2)->count(),
            '1_star' => OutletReview::where('overall_rating', 1)->count(),
        ];
    }
}
