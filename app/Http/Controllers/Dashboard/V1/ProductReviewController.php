<?php

namespace Modules\Order\Http\Controllers\Dashboard\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Order\Http\Requests\Dashboard\V1\ProductReview\ReplyProductReviewRequest;
use Modules\Order\Http\Requests\Dashboard\V1\ProductReview\StoreProductReviewRequest;
use Modules\Order\Http\Requests\Dashboard\V1\ProductReview\UpdateProductReviewRequest;
use Modules\Order\Http\Resources\ProductReviewResource;
use Modules\Order\Models\ProductReview;
use Modules\Product\Models\Product;
use Momentum\Modal\Modal;

class ProductReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'rating', 'product_id', 'is_active', 'has_reply']);
        $perPage = $request->integer('per_page', 10);

        $query = ProductReview::with(['customer', 'product', 'order'])
            ->latest();

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('comment', 'like', "%{$filters['search']}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"))
                    ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"));
            });
        }

        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
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
        $products = Product::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('order::Dashboard/V1/ProductReview/Index', [
            'reviewItems' => ProductReviewResource::collection($reviews)->response()->getData(true),
            'filters' => $filters,
            'products' => $products,
            'stats' => $this->getStats(),
        ]);
    }

    public function show(ProductReview $productReview): Response
    {
        $productReview->load(['customer', 'product', 'order', 'orderItem']);

        return Inertia::render('order::Dashboard/V1/ProductReview/Show', [
            'review' => (new ProductReviewResource($productReview))->resolve(),
        ]);
    }

    public function create(): Modal
    {
        $products = Product::select('id', 'name')->orderBy('name')->get();

        return Inertia::modal('order::Dashboard/V1/ProductReview/Create', [
            'products' => $products,
        ])->baseRoute('order.product-reviews.index');
    }

    public function store(StoreProductReviewRequest $request): RedirectResponse
    {
        ProductReview::create($request->validated());

        return redirect()->route('order.product-reviews.index')
            ->with('success', 'Product review created successfully.');
    }

    public function edit(ProductReview $productReview): Modal
    {
        $productReview->load(['customer', 'product']);

        return Inertia::modal('order::Dashboard/V1/ProductReview/Edit', [
            'review' => (new ProductReviewResource($productReview))->resolve(),
        ])->baseRoute('order.product-reviews.index');
    }

    public function update(UpdateProductReviewRequest $request, ProductReview $productReview): RedirectResponse
    {
        $productReview->update($request->validated());

        return redirect()->route('order.product-reviews.index')
            ->with('success', 'Product review updated successfully.');
    }

    public function destroy(ProductReview $productReview): RedirectResponse
    {
        $productReview->delete();

        return redirect()->route('order.product-reviews.index')
            ->with('success', 'Product review deleted successfully.');
    }

    public function replyModal(ProductReview $productReview): Modal
    {
        $productReview->load(['customer', 'product', 'order']);

        return Inertia::modal('order::Dashboard/V1/ProductReview/Reply', [
            'review' => (new ProductReviewResource($productReview))->resolve(),
        ])->baseRoute('order.product-reviews.index');
    }

    public function reply(ReplyProductReviewRequest $request, ProductReview $productReview): RedirectResponse
    {
        $productReview->addReply($request->validated()['reply']);

        return redirect()->back()->with('success', 'Reply added successfully.');
    }

    public function toggleActive(ProductReview $productReview): RedirectResponse
    {
        $productReview->update(['is_active' => !$productReview->is_active]);

        $status = $productReview->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Review {$status} successfully.");
    }

    protected function getStats(): array
    {
        return [
            'total' => ProductReview::count(),
            'active' => ProductReview::where('is_active', true)->count(),
            'pending_reply' => ProductReview::whereNull('reply')->count(),
            'average_rating' => round(ProductReview::avg('rating') ?? 0, 1),
            '5_star' => ProductReview::where('rating', 5)->count(),
            '4_star' => ProductReview::where('rating', 4)->count(),
            '3_star' => ProductReview::where('rating', 3)->count(),
            '2_star' => ProductReview::where('rating', 2)->count(),
            '1_star' => ProductReview::where('rating', 1)->count(),
        ];
    }
}
