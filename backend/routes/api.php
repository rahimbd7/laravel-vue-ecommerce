<?php

use App\Http\Controllers\API\Admin\AdminCategoryController;
use App\Http\Controllers\API\Admin\AdminVendorController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Cart\CartController;
use App\Http\Controllers\API\Category\CategoryController;
use App\Http\Controllers\API\Checkout\CheckoutController;
use App\Http\Controllers\API\Order\OrderController;
use App\Http\Controllers\API\Product\ProductController;
use App\Http\Controllers\API\Product\ProductImageController;
use App\Http\Controllers\API\Product\ProductReviewController;
use App\Http\Controllers\API\Product\ProductVariationController;
use App\Http\Controllers\API\Vendor\VendorApplicationController;
use App\Http\Controllers\API\Vendor\VendorController;
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
    // Route::post('/me', [AuthController::class, 'me']);
    // Route::apiResource('/users', UserController::class);
});





// Order Routes - Single route group with auth only
//Role-based authorization handled in controller
Route::middleware(['auth:sanctum'])->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/', [OrderController::class, 'store'])->name('orders.store');
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

/// ============ AUTHENTICATED ONLY ROUTES ============
Route::middleware(['auth:sanctum'])->group(function () {

    // Cart sync (authenticated only)
    Route::prefix('cart')->group(function () {
        Route::post('/sync', [CartController::class, 'syncGuestCart']);
    });
});


// Route::prefix('cart')->group(function () {
//     // Public routes (guest + authenticated both work)
//     Route::get('/', [CartController::class, 'index']);
//     Route::get('/summary', [CartController::class, 'summary']);
//     Route::post('/add', [CartController::class, 'addItem']);
//     Route::put('/items/{cartItemId}', [CartController::class, 'updateItem']);
//     Route::delete('/items/{cartItemId}', [CartController::class, 'removeItem']);
//     Route::post('/clear', [CartController::class, 'clear']);
//     Route::post('/items/{cartItemId}/save-for-later', [CartController::class, 'saveForLater']);
//     Route::post('/items/{cartItemId}/move-to-cart', [CartController::class, 'moveToCart']);
    
//     // Sync endpoint (authenticated only - requires token)
//     Route::middleware(['auth:sanctum'])->post('/sync', [CartController::class, 'sync']);
// });


// ============ CHECKOUT ROUTES ============
Route::middleware(['auth:sanctum'])->prefix('checkout')->group(function () {
    Route::get('/summary', [CheckoutController::class, 'summary']);
    Route::post('/process', [CheckoutController::class, 'process']);
});
