<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\Api\V1\CartController;
use Modules\Order\Http\Controllers\Api\V1\OrderController;
use Modules\Order\Http\Controllers\Api\V1\OutletReviewController;
use Modules\Order\Http\Controllers\Api\V1\ProductReviewController;

/*
|--------------------------------------------------------------------------
| API Routes - Order Module
|--------------------------------------------------------------------------
*/

// ==================== PRODUCT REVIEWS ====================

// Public routes (no auth required)
Route::prefix('v1')->group(function () {
    // Get reviews for a specific product
    Route::get('products/{productId}/reviews', [ProductReviewController::class, 'productReviews'])
        ->name('order.products.reviews');
    Route::get('products/{productId}/reviews/summary', [ProductReviewController::class, 'productSummary'])
        ->name('order.products.reviews.summary');

    // Get reviews for a specific outlet
    Route::get('outlets/{outletId}/reviews', [OutletReviewController::class, 'outletReviews'])
        ->name('order.outlets.reviews');
    Route::get('outlets/{outletId}/reviews/summary', [OutletReviewController::class, 'outletSummary'])
        ->name('order.outlets.reviews.summary');

    // Mark helpful (no auth needed)
    Route::post('product-reviews/{id}/helpful', [ProductReviewController::class, 'markHelpful'])
        ->name('order.product-reviews.helpful');
    Route::post('outlet-reviews/{id}/helpful', [OutletReviewController::class, 'markHelpful'])
        ->name('order.outlet-reviews.helpful');
});

// Protected routes (auth required)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // ==================== ORDERS ====================
    Route::get('customer/orders', [OrderController::class, 'index'])
        ->name('order.customer.orders');
    Route::get('customer/orders/{order}', [OrderController::class, 'show'])
        ->name('order.customer.orders.show');
    Route::post('customer/orders', [OrderController::class, 'store'])
        ->name('order.customer.orders.store');
    Route::post('customer/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->name('order.customer.orders.cancel');

    // ==================== CART ====================
    Route::get('customer/cart', [CartController::class, 'index'])
        ->name('order.customer.cart');
    Route::post('customer/cart', [CartController::class, 'store'])
        ->name('order.customer.cart.store');
    Route::put('customer/cart/{itemId}', [CartController::class, 'update'])
        ->name('order.customer.cart.update');
    Route::delete('customer/cart/{itemId}', [CartController::class, 'destroy'])
        ->name('order.customer.cart.destroy');
    Route::delete('customer/cart', [CartController::class, 'clear'])
        ->name('order.customer.cart.clear');

    // ==================== PRODUCT REVIEWS ====================
    // Customer's own product reviews
    Route::get('customer/reviews', [ProductReviewController::class, 'myReviews'])
        ->name('order.customer.product-reviews');
    Route::post('customer/reviews', [ProductReviewController::class, 'store'])
        ->name('order.customer.product-reviews.store');
    Route::put('customer/reviews/{id}', [ProductReviewController::class, 'update'])
        ->name('order.customer.product-reviews.update');
    Route::delete('customer/reviews/{id}', [ProductReviewController::class, 'destroy'])
        ->name('order.customer.product-reviews.destroy');

    // Customer's own outlet reviews
    Route::get('customer/outlet-reviews', [OutletReviewController::class, 'myReviews'])
        ->name('order.customer.outlet-reviews');
    Route::post('customer/outlet-reviews', [OutletReviewController::class, 'store'])
        ->name('order.customer.outlet-reviews.store');
    Route::put('customer/outlet-reviews/{id}', [OutletReviewController::class, 'update'])
        ->name('order.customer.outlet-reviews.update');
    Route::delete('customer/outlet-reviews/{id}', [OutletReviewController::class, 'destroy'])
        ->name('order.customer.outlet-reviews.destroy');
});
