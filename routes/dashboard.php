<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\Dashboard\V1\CartController;
use Modules\Order\Http\Controllers\Dashboard\V1\OrderController;
use Modules\Order\Http\Controllers\Dashboard\V1\OutletReviewController;
use Modules\Order\Http\Controllers\Dashboard\V1\ProductReviewController;

Route::middleware(['auth', 'verified', 'auto.permission'])
    ->prefix('dashboard')
    ->group(function () {
        // ==================== ORDER ROUTES ====================

        // Orders CRUD
        Route::resource('orders', OrderController::class)
            ->names('order.orders')
            ->parameters(['orders' => 'order']);

        // Order Status Updates
        Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('order.orders.update-status');
        Route::put('orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])
            ->name('order.orders.update-payment-status');

        // ==================== CART ROUTES ====================

        // Carts CRUD
        Route::resource('carts', CartController::class)
            ->names('order.carts')
            ->parameters(['carts' => 'cart']);

        // Cart Actions
        Route::put('carts/{cart}/toggle-status', [CartController::class, 'toggleStatus'])
            ->name('order.carts.toggle-status');
        Route::post('carts/{cart}/convert-to-order', [CartController::class, 'convertToOrder'])
            ->name('order.carts.convert-to-order');
        Route::delete('carts/{cart}/clear-items', [CartController::class, 'clearItems'])
            ->name('order.carts.clear-items');

        // ==================== PRODUCT REVIEW ROUTES ====================

        // Product Reviews CRUD
        Route::resource('product-reviews', ProductReviewController::class)
            ->names('order.product-reviews')
            ->parameters(['product-reviews' => 'productReview']);

        // Product Review Actions
        Route::get('product-reviews/{productReview}/reply', [ProductReviewController::class, 'replyModal'])
            ->name('order.product-reviews.reply-modal');
        Route::post('product-reviews/{productReview}/reply', [ProductReviewController::class, 'reply'])
            ->name('order.product-reviews.reply');
        Route::put('product-reviews/{productReview}/toggle-active', [ProductReviewController::class, 'toggleActive'])
            ->name('order.product-reviews.toggle-active');

        // ==================== OUTLET REVIEW ROUTES ====================

        // Outlet Reviews CRUD
        Route::resource('outlet-reviews', OutletReviewController::class)
            ->names('order.outlet-reviews')
            ->parameters(['outlet-reviews' => 'outletReview']);

        // Outlet Review Actions
        Route::get('outlet-reviews/{outletReview}/reply', [OutletReviewController::class, 'replyModal'])
            ->name('order.outlet-reviews.reply-modal');
        Route::post('outlet-reviews/{outletReview}/reply', [OutletReviewController::class, 'reply'])
            ->name('order.outlet-reviews.reply');
        Route::put('outlet-reviews/{outletReview}/toggle-active', [OutletReviewController::class, 'toggleActive'])
            ->name('order.outlet-reviews.toggle-active');
    });
