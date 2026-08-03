<?php

use App\Http\Controllers\API\Admin\AdminCategoryController;
use App\Http\Controllers\API\Admin\AdminCouponController;
use App\Http\Controllers\API\Admin\AdminDashboardController;
use App\Http\Controllers\API\Admin\AdminOrderController;
use App\Http\Controllers\API\Admin\AdminPayoutController;
use App\Http\Controllers\API\Admin\AdminProductController;
use App\Http\Controllers\API\Admin\AdminUserController;
use App\Http\Controllers\API\Admin\AdminVendorController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Cart\CartController;
use App\Http\Controllers\API\Category\CategoryController;
use App\Http\Controllers\API\Checkout\CheckoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\API\Order\OrderController;
use App\Http\Controllers\API\Payment\PaymentController;
use App\Http\Controllers\API\Product\ProductController;
use App\Http\Controllers\API\Product\ProductImageController;
use App\Http\Controllers\API\Product\ProductReviewController;
use App\Http\Controllers\API\Product\ProductVariationController;
use App\Http\Controllers\API\Profile\ProfileController;
use App\Http\Controllers\API\Transaction\TransactionController;
use App\Http\Controllers\API\Vendor\VendorApplicationController;
use App\Http\Controllers\API\Vendor\VendorController;
use App\Http\Controllers\API\Vendor\VendorCouponController;
use App\Http\Controllers\API\Vendor\VendorPayoutController;
use App\Http\Controllers\API\Vendor\VendorProfileController;
use App\Http\Controllers\Api\WishList\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
* ************Authentication routes*************
*/

/*
* ************ All Public routes *************
*/

//public routes auth/user
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

//public vendor routes

Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/vendors/{id}', [VendorController::class, 'show']);

//public category route

Route::get('/categories', [CategoryController::class, 'index']);

//public product routes

Route::prefix('v1')->group(function () {
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('{product}', [ProductController::class, 'show']);
        Route::get('slug/{slug}', [ProductController::class, 'getBySlug']);
        Route::get('{product}/related', [ProductController::class, 'related']);

        // Public review routes
        Route::get('{product}/reviews', [ProductReviewController::class, 'index']);
        Route::get('{product}/reviews/stats', [ProductReviewController::class, 'stats']);
        Route::get('{product}/reviews/{review}', [ProductReviewController::class, 'show']);
    });
});

//public routes for carts
// ============ PUBLIC ROUTES (No Auth Required) ============
// Cart routes - work for both guests and authenticated users
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::get('/summary', [CartController::class, 'summary']);
    Route::post('/add', [CartController::class, 'addItem']);
    Route::put('/items/{cartItemId}', [CartController::class, 'updateItem']);
    Route::delete('/items/{cartItemId}', [CartController::class, 'removeItem']);
    Route::post('/clear', [CartController::class, 'clear']);
    Route::post('/items/{cartItemId}/save-for-later', [CartController::class, 'saveForLater']);
    Route::post('/items/{cartItemId}/move-to-cart', [CartController::class, 'moveToCart']);
});

/*
* ************ All Protected routes *************
*/

/*
 * Authenticated User Routes
 */

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [ProfileController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/avatar', [ProfileController::class, 'updateAvatarFromUrl']);
    Route::put('/profile/address', [ProfileController::class, 'updateAddress']);
    Route::get('/profile/address', [ProfileController::class, 'getAddress']);
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword']);
});

Route::get('wishlist/check/{productId}', [WishlistController::class, 'check']); // Check if in wishlist
Route::middleware(['auth:sanctum'])->group(function () {
    // Wishlist Routes
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);                        // Get wishlist
        Route::post('/add', [WishlistController::class, 'store']);                    // Add to wishlist
        Route::delete('/remove/{productId}', [WishlistController::class, 'destroy']); // Remove from wishlist
        Route::post('/toggle', [WishlistController::class, 'toggle']);                // Toggle wishlist
        Route::get('/count', [WishlistController::class, 'count']);                   // Get wishlist count
        Route::delete('/clear', [WishlistController::class, 'clear']);                // Clear wishlist
    });
});

// Order Routes - Single route group with auth only
//Role-based authorization handled in controller
Route::middleware(['auth:sanctum', 'role:customer'])->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/summary', [OrderController::class, 'summary'])->name('orders.summary');
    Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});

/*
 * Vendor Routes
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('vendor')->group(function () {
        Route::post('/apply', [VendorApplicationController::class, 'store']);
        Route::get('/application-status', [VendorApplicationController::class, 'applicationStatus']);
    });
});

// ============ VENDOR PROFILE ROUTES ============
//  Vendor profile endpoints

// ============ VENDOR PROFILE ROUTES ============
Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor')->group(function () {
    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [VendorProfileController::class, 'index']);
        Route::put('/', [VendorProfileController::class, 'update']);
        Route::post('/logo', [VendorProfileController::class, 'updateLogoFromUrl']);
        Route::get('/stats', [VendorProfileController::class, 'stats']);
        Route::get('/analytics', [VendorProfileController::class, 'analytics']);
    });

    // Shipping
    Route::prefix('shipping')->group(function () {
        Route::get('/', [VendorProfileController::class, 'getShipping']);
        Route::put('/', [VendorProfileController::class, 'updateShipping']);
    });
    //coupons
    Route::get('/coupons', [VendorCouponController::class, 'index']);
    Route::post('/coupons', [VendorCouponController::class, 'store']);
    Route::get('/coupons/analytics', [VendorCouponController::class, 'analytics']);

});

/*
* Admin Routes
*/

/***
 * *Admin Vendor Management Routes
 */
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/vendor/pending', [AdminVendorController::class, 'pendingVendors']);
    Route::post('/vendor/{vendor}/approve', [AdminVendorController::class, 'approvedVendors']);
    Route::post('/vendor/{vendor}/reject', [AdminVendorController::class, 'rejectVendor']);

});

/**
 * *Admin Category Management Routes
 */

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('/categories', AdminCategoryController::class);
});

/**
 * *Product Management Routes by Vendors
 */

Route::prefix('v1')->middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {

        // ========== PRODUCT MANAGEMENT ==========
        Route::apiResource('products', ProductController::class)
            ->except(['index', 'show']);

        // Additional product routes
        Route::prefix('products')->group(function () {
            Route::get('stats/overview', [ProductController::class, 'stats']);
        });

        // ========== PRODUCT IMAGES ==========
        Route::prefix('products/{product}')->group(function () {
            Route::get('images', [ProductImageController::class, 'index']);
            Route::post('images', [ProductImageController::class, 'store']);
            Route::get('images/{image}', [ProductImageController::class, 'show']);
            Route::put('images/{image}', [ProductImageController::class, 'update']);
            Route::delete('images/{image}', [ProductImageController::class, 'destroy']);
            Route::patch('images/{image}/set-primary', [ProductImageController::class, 'setPrimary']);
            Route::patch('images/reorder', [ProductImageController::class, 'reorder']);
        });

        // ========== PRODUCT VARIATIONS ==========
        Route::prefix('products/{product}')->group(function () {
            Route::get('variations', [ProductVariationController::class, 'index']);
            Route::post('variations', [ProductVariationController::class, 'store']);
            Route::get('variations/{variation}', [ProductVariationController::class, 'show']);
            Route::put('variations/{variation}', [ProductVariationController::class, 'update']);
            Route::delete('variations/{variation}', [ProductVariationController::class, 'destroy']);
            Route::patch('variations/{variation}/set-default', [ProductVariationController::class, 'setDefault']);
            Route::patch('variations/{variation}/update-stock', [ProductVariationController::class, 'updateStock']);
            Route::patch('variations/reorder', [ProductVariationController::class, 'reorder']);
            Route::post('variations/bulk/update', [ProductVariationController::class, 'bulkUpdate']);
        });

    });
});

/**
 * *Order Management Routes by Vendors
 */

Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor')->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [App\Http\Controllers\API\Vendor\VendorDashboardController::class, 'index']);
        Route::get('/orders', [App\Http\Controllers\API\Vendor\VendorDashboardController::class, 'orders']);
        Route::get('/orders/stats', [App\Http\Controllers\API\Vendor\VendorDashboardController::class, 'orderStats']);
        Route::get('/orders/recent', [App\Http\Controllers\API\Vendor\VendorDashboardController::class, 'recentOrders']);
        Route::get('/orders/{orderId}', [App\Http\Controllers\API\Vendor\VendorDashboardController::class, 'showOrder']);
        Route::put('/orders/{orderId}/status', [App\Http\Controllers\API\Vendor\VendorDashboardController::class, 'updateOrderStatus']);
    });
});

/**
 * *Authenticated Review Actions (Customer/Vendor/Admin)
 */
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('products/{product}')->group(function () {
        Route::post('reviews', [ProductReviewController::class, 'store']);
        Route::put('reviews/{review}', [ProductReviewController::class, 'update']);
        Route::delete('reviews/{review}', [ProductReviewController::class, 'destroy']);
        Route::post('reviews/{review}/helpful', [ProductReviewController::class, 'helpful']);
        Route::post('reviews/{review}/unhelpful', [ProductReviewController::class, 'unhelpful']);
    });
});

/**
 * *Admin Product Management Routes
 */
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('products/{product}/reviews/{review}/approve', [ProductReviewController::class, 'approve']);
    Route::post('products/{product}/reviews/{review}/reject', [ProductReviewController::class, 'reject']);
});

//carts routes
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::get('/summary', [CartController::class, 'summary']);
    Route::post('/add', [CartController::class, 'addItem']);
    Route::put('/items/{cartItemId}', [CartController::class, 'updateItem']);
    Route::delete('/items/{cartItemId}', [CartController::class, 'removeItem']);
    Route::post('/clear', [CartController::class, 'clear']);
    Route::post('/items/{cartItemId}/save-for-later', [CartController::class, 'saveForLater']);
    Route::post('/items/{cartItemId}/move-to-cart', [CartController::class, 'moveToCart']);
});

/**
 * *Admin Order Management Routes
 */

Route::post('/', [OrderController::class, 'store'])->name('orders.store')->middleware('role:admin');

/// ============ AUTHENTICATED ONLY ROUTES ============
Route::middleware(['auth:sanctum'])->group(function () {
    // Cart sync (authenticated only)
    Route::prefix('cart')->group(function () {
        Route::post('/sync', [CartController::class, 'syncGuestCart']);
    });
});

// Public routes (guest + authenticated both work)
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::get('/summary', [CartController::class, 'summary']);
    Route::post('/add', [CartController::class, 'addItem']);
    Route::put('/items/{cartItemId}', [CartController::class, 'updateItem']);
    Route::delete('/items/{cartItemId}', [CartController::class, 'removeItem']);
    Route::post('/clear', [CartController::class, 'clear']);
    Route::post('/items/{cartItemId}/save-for-later', [CartController::class, 'saveForLater']);
    Route::post('/items/{cartItemId}/move-to-cart', [CartController::class, 'moveToCart']);
});

// ============ CHECKOUT ROUTES ============
Route::middleware(['auth:sanctum'])->prefix('checkout')->group(function () {
    Route::get('/summary', [CheckoutController::class, 'summary']);
    Route::post('/process', [CheckoutController::class, 'process']);
});

// ============ PAYMENT ROUTES ============
Route::middleware(['auth:sanctum'])->prefix('payments')->group(function () {
    Route::get('/my', [PaymentController::class, 'getMyPayments']);
    Route::get('/{paymentId}', [PaymentController::class, 'getPaymentDetails']);
    Route::get('/order/{orderId}/status', [PaymentController::class, 'getPaymentStatus']);
});

// ============ VENDOR PAYOUT ROUTES ============
Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor')->group(function () {
    Route::prefix('payouts')->group(function () {
        Route::get('/dashboard', [VendorPayoutController::class, 'dashboard']);
        Route::get('/history', [VendorPayoutController::class, 'history']);
        Route::get('/earnings-summary', [VendorPayoutController::class, 'earningsSummary']);
    });
});

// ============ ADMIN PAYOUT ROUTES ============
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::prefix('payouts')->group(function () {
        Route::get('/pending', [AdminPayoutController::class, 'pendingPayouts']);
        Route::post('/process', [AdminPayoutController::class, 'processPayouts']);
        Route::get('/summary', [AdminPayoutController::class, 'payoutSummary']);
        Route::get('/{payoutId}', [AdminPayoutController::class, 'payoutDetails']);
    });
});

// ============ TRANSACTION ROUTES ============
Route::middleware(['auth:sanctum'])->prefix('transactions')->group(function () {
    Route::get('/my', [TransactionController::class, 'myTransactions']);
    Route::get('/summary', [TransactionController::class, 'transactionSummary']);
});

// Admin transaction routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'adminTransactions']);
});

//============ ADMIN DASHBOARD ROUTES ============
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index']);
        Route::get('/revenue', [AdminDashboardController::class, 'revenue']);
        Route::get('/users', [AdminDashboardController::class, 'users']);
        Route::get('/vendors', [AdminDashboardController::class, 'vendors']);
        Route::get('/orders', [AdminDashboardController::class, 'orders']);
        Route::get('/commissions', [AdminDashboardController::class, 'commissions']);
        Route::get('/activities', [AdminDashboardController::class, 'activities']);
        Route::get('/top-vendors', [AdminDashboardController::class, 'topVendors']);
        Route::post('/clear-cache', [AdminDashboardController::class, 'clearCache']);
    });
    // ===================== USER MANAGEMENT =====================
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::post('/', [AdminUserController::class, 'store']);
        Route::get('/{id}', [AdminUserController::class, 'show']);
        Route::put('/{id}', [AdminUserController::class, 'update']);
        Route::delete('/{id}', [AdminUserController::class, 'destroy']);
        Route::post('/{id}/restore', [AdminUserController::class, 'restore']);
        Route::get('/{id}/orders', [AdminUserController::class, 'orders']);
        Route::get('/{id}/activities', [AdminUserController::class, 'activities']);
        Route::get('/{id}/stats', [AdminUserController::class, 'stats']);
        Route::get('/{id}/activities/export', [AdminUserController::class, 'exportActivities']);
        Route::post('/bulk-delete', [AdminUserController::class, 'bulkDelete']);
        Route::post('/bulk-status', [AdminUserController::class, 'bulkStatus']);
    });
// ===================== ADMIN ORDER MANAGEMENT =====================

    Route::prefix('orders')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/stats', [AdminOrderController::class, 'stats']);
        Route::get('/export', [AdminOrderController::class, 'export']);
        Route::get('/{id}', [AdminOrderController::class, 'show']);
        Route::get('/{id}/timeline', [AdminOrderController::class, 'timeline']);
        Route::get('/vendor/{vendorId}', [AdminOrderController::class, 'vendorOrders']);
        Route::put('/{id}/status', [AdminOrderController::class, 'updateStatus']);
        Route::post('/{id}/cancel', [AdminOrderController::class, 'cancel']);
    });
    Route::prefix('dashboard/products')->group(function () {
        Route::get('/', [AdminProductController::class, 'index']);
        Route::get('/stats', [AdminProductController::class, 'stats']);
        Route::get('/export', [AdminProductController::class, 'export']);
        Route::get('/{id}', [AdminProductController::class, 'show']);
        Route::put('/{id}', [AdminProductController::class, 'update']);
        Route::delete('/{id}', [AdminProductController::class, 'destroy']);
        Route::post('/{id}/restore', [AdminProductController::class, 'restore']);
        Route::put('/{id}/toggle-visibility', [AdminProductController::class, 'toggleVisibility']);
        Route::post('/bulk-action', [AdminProductController::class, 'bulkAction']);
        Route::get('/vendor/{vendorId}', [AdminProductController::class, 'vendorProducts']);
    });
// ============ ADMIN COUPON ROUTES ============
    Route::prefix('coupons')->group(function () {
        Route::get('/analytics', [AdminCouponController::class, 'analytics']);
        Route::get('/', [AdminCouponController::class, 'index']);
        Route::post('/', [AdminCouponController::class, 'store']);
        Route::get('/{id}', [AdminCouponController::class, 'show']);
        Route::put('/{id}', [AdminCouponController::class, 'update']);
        Route::delete('/{id}', [AdminCouponController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [AdminCouponController::class, 'toggleStatus']);
        Route::get('/export', [AdminCouponController::class, 'export']);
    });
});

// ============ VENDOR COUPON ROUTES ============
Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor')->group(function () {
    Route::prefix('coupons')->group(function () {
        Route::get('/', [VendorCouponController::class, 'index']);
        Route::post('/', [VendorCouponController::class, 'store']);
        Route::get('/{id}', [VendorCouponController::class, 'show']);
        Route::put('/{id}', [VendorCouponController::class, 'update']);
        Route::delete('/{id}', [VendorCouponController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [VendorCouponController::class, 'toggleStatus']);
        Route::get('/analytics', [VendorCouponController::class, 'analytics']);
    });
});

// ============ user COUPON ROUTES ============
Route::middleware(['auth:sanctum'])->prefix('coupons')->group(function () {
    Route::get('/available', [CouponController::class, 'available']);
    Route::post('/validate', [CouponController::class, 'validateCoupon']);
    Route::post('/apply', [CouponController::class, 'apply']);
    Route::delete('/remove', [CouponController::class, 'remove']);
    Route::get('/applied', [CouponController::class, 'applied']);
    Route::delete('/clear', [CouponController::class, 'clear']);
    Route::get('/history', [CouponController::class, 'history']);
});
