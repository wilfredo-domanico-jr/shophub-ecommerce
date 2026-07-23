<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\FlashSaleController as AdminFlashSaleController;
use App\Http\Controllers\Api\Admin\JobOpeningController as AdminJobOpeningController;
use App\Http\Controllers\Api\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Api\Admin\NewsletterSubscriberController as AdminNewsletterSubscriberController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Admin\UploadController as AdminUploadController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CareerController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\FlashSaleController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\VoucherController;
use Illuminate\Support\Facades\Route;

// Public storefront
Route::get('/config', [ConfigController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::get('/products/{product:slug}/reviews', [ReviewController::class, 'index']);
Route::get('/careers', [CareerController::class, 'index']);
Route::get('/vouchers', [VoucherController::class, 'index']);
Route::get('/flash-sale', [FlashSaleController::class, 'current']);
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1');
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe'])->middleware('throttle:10,1');

// Order tracking (public — works for orders placed before accounts existed).
// Throttled: order numbers are guessable enough to brute-force otherwise.
Route::post('/orders/track', [OrderController::class, 'track'])->middleware('throttle:15,1');

// Stripe webhook (public — authenticated by signature verification against
// STRIPE_WEBHOOK_SECRET, not by session/token).
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

// Auth
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');

// Social login (GET — full-page browser navigations, not XHR)
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->middleware('throttle:10,1');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Cart (server-side, so it survives refresh and follows the account).
    // Throttled like the other write-heavy customer routes — nothing here
    // was rate-limited before, so a stolen/scripted token could otherwise
    // hammer these for free.
    Route::get('/cart', [CartController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/cart/items', [CartController::class, 'store'])->middleware('throttle:60,1');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->middleware('throttle:60,1');
    Route::delete('/cart', [CartController::class, 'clear'])->middleware('throttle:60,1');

    // Customer account
    // Throttled: each order queues a confirmation email to a caller-chosen
    // address — unlimited checkouts would be a mail cannon.
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/orders/{order}/pay', [PaymentController::class, 'pay'])->middleware('throttle:10,1');
    Route::get('/my/orders/{orderNumber}/payment-status', [PaymentController::class, 'paymentStatus'])->middleware('throttle:30,1');
    Route::post('/vouchers/preview', [VoucherController::class, 'preview'])->middleware('throttle:20,1');
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::get('/my/orders', [OrderController::class, 'myOrders']);

    // Reviews (verified purchasers; posting throttled like checkout)
    Route::post('/products/{product:slug}/reviews', [ReviewController::class, 'store'])->middleware('throttle:10,1');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::post('/uploads', [AdminUploadController::class, 'store']);

        Route::apiResource('categories', AdminCategoryController::class)->except(['show']);
        Route::apiResource('products', AdminProductController::class)->except(['show']);

        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);

        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::patch('/reviews/{review}/visibility', [AdminReviewController::class, 'updateVisibility']);
        Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy']);

        Route::apiResource('users', AdminUserController::class)->except(['show']);
        Route::apiResource('careers', AdminJobOpeningController::class)->except(['show']);
        Route::apiResource('vouchers', AdminVoucherController::class)->except(['show']);
        Route::apiResource('flash-sales', AdminFlashSaleController::class)->except(['show']);

        Route::apiResource('newsletters', AdminNewsletterController::class)->except(['show']);
        Route::post('/newsletters/{newsletter}/send', [AdminNewsletterController::class, 'send']);
        Route::get('/newsletter-subscribers', [AdminNewsletterSubscriberController::class, 'index']);
        Route::delete('/newsletter-subscribers/{subscriber}', [AdminNewsletterSubscriberController::class, 'destroy']);
    });
});
