<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Vendor\VendorApplicationController;
use App\Http\Controllers\API\Vendor\VendorController;
use App\Http\Controllers\API\Admin\AdminVendorController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
* ************Authentication routes*************
*/


//public routes auth/user
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

//public vendor routes

Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/vendors/{id}', [VendorController::class, 'show']);

/*
* Protected routes
*/

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Route::post('/me', [AuthController::class, 'me']);
    // Route::apiResource('/users', UserController::class);
});

Route::middleware('auth:sanctum')->group(function () {
  Route::prefix('vendor')->group(function () {
     Route::post('/apply', [VendorApplicationController::class, 'store']);
     Route::get('/application-status', [VendorApplicationController::class, 'applicationStatus']);
  });
});

/*
* Admin Routes
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/vendor/pending', [AdminVendorController::class, 'pendingVendors']);
    Route::post('/vendor/{vendor}/approve', [AdminVendorController::class, 'approvedVendors']);
    Route::post('/vendor/{vendor}/reject', [AdminVendorController::class, 'rejectVendor']);
});
