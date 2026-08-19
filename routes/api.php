<?php

use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CertificateController as AdminCertificateController;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Api\Public\ArticleController;
use App\Http\Controllers\Api\Public\AuctionController;
use App\Http\Controllers\Api\Public\AuthController;
use App\Http\Controllers\Api\Public\BannerController;
use App\Http\Controllers\Api\Public\CategoryController;
use App\Http\Controllers\Api\Public\CertificateController;
use App\Http\Controllers\Api\Public\PaymentWebhookController;
use App\Http\Controllers\Api\Public\ProductController;
use App\Http\Controllers\Api\Public\ServiceController;
use App\Http\Controllers\Api\User\AddressController;
use App\Http\Controllers\Api\User\BidController;
use App\Http\Controllers\Api\User\CartController;
use App\Http\Controllers\Api\User\CouponController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\ServiceBookingController;
use App\Http\Controllers\Api\Vendor\AuctionController as VendorAuctionController;
use App\Http\Controllers\Api\Vendor\CertificateController as VendorCertificateController;
use App\Http\Controllers\Api\Vendor\CouponController as VendorCouponController;
use App\Http\Controllers\Api\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Api\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Api\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Api\Vendor\ServiceController as VendorServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ===================== PUBLIC =====================
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::get('categories', [CategoryController::class, 'index']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);

    Route::get('banners', [BannerController::class, 'index']);

    Route::get('auctions', [AuctionController::class, 'index']);
    Route::get('auctions/{auction}', [AuctionController::class, 'show']);

    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{article:slug}', [ArticleController::class, 'show']);

    Route::get('certificates/{number}/verify', [CertificateController::class, 'verify']);

    Route::post('payments/webhook', [PaymentWebhookController::class, 'handle']);

    // ===================== USER (auth:sanctum) =====================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/items', [CartController::class, 'addItem']);
        Route::patch('cart/items/{cartItem}', [CartController::class, 'updateQuantity']);
        Route::delete('cart/items/{cartItem}', [CartController::class, 'removeItem']);

        Route::post('coupons/apply', [CouponController::class, 'apply']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{orderNumber}', [OrderController::class, 'show']);
        Route::post('orders/checkout', [OrderController::class, 'checkout']);

        Route::post('auctions/{auction}/bid', [BidController::class, 'store']);

        Route::get('service-bookings', [ServiceBookingController::class, 'index']);
        Route::post('service-bookings', [ServiceBookingController::class, 'store']);

        Route::apiResource('addresses', AddressController::class)->except(['show']);

        // ===================== VENDOR (role:vendor) =====================
        Route::middleware('role:vendor')->prefix('vendor')->group(function () {
            Route::apiResource('products', VendorProductController::class)->except(['show']);
            Route::post('products/{product}/certificate', [VendorCertificateController::class, 'storeForProduct']);

            Route::apiResource('services', VendorServiceController::class)->except(['show']);
            Route::post('services/{service}/certificate', [VendorCertificateController::class, 'storeForService']);

            Route::get('auctions', [VendorAuctionController::class, 'index']);
            Route::post('auctions', [VendorAuctionController::class, 'store']);
            Route::post('auctions/{auction}/cancel', [VendorAuctionController::class, 'cancel']);

            Route::apiResource('coupons', VendorCouponController::class)->except(['show']);

            Route::get('orders', [VendorOrderController::class, 'index']);
            Route::get('dashboard', [VendorDashboardController::class, 'index']);
        });

        // ===================== ADMIN (role:admin) =====================
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('vendors', [AdminVendorController::class, 'index']);
            Route::post('vendors/{vendorProfile}/approve', [AdminVendorController::class, 'approve']);
            Route::post('vendors/{vendorProfile}/reject', [AdminVendorController::class, 'reject']);
            Route::post('vendors/{vendorProfile}/suspend', [AdminVendorController::class, 'suspend']);

            Route::get('products', [AdminProductController::class, 'index']);
            Route::post('products/{product}/approve', [AdminProductController::class, 'approve']);
            Route::post('products/{product}/reject', [AdminProductController::class, 'reject']);

            Route::get('services', [AdminServiceController::class, 'index']);
            Route::post('services/{service}/approve', [AdminServiceController::class, 'approve']);
            Route::post('services/{service}/reject', [AdminServiceController::class, 'reject']);

            Route::post('certificates', [AdminCertificateController::class, 'store']);

            Route::apiResource('banners', AdminBannerController::class)->except(['show']);
            Route::apiResource('coupons', AdminCouponController::class)->except(['show']);
            Route::apiResource('categories', AdminCategoryController::class)->except(['show']);
            Route::apiResource('articles', AdminArticleController::class)->except(['show']);
        });
    });
});
