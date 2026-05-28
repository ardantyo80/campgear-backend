<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ReviewController;

// ==================== TEST ROUTE ====================
Route::get('/test', function () {
    return response()->json(['message' => 'API works']);
});

// ==================== PUBLIC ROUTES (No Auth) ====================

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/products/category/{slug}', [ProductController::class, 'byCategory']);

// Categories
Route::get('/categories', [CategoryController::class, 'index']);

// Product Reviews (public)
Route::get('/products/{slug}/reviews', [ReviewController::class, 'productReviews']);


// ==================== PROTECTED ROUTES (Need Auth Token) ====================

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Bookings
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings/{id}/pay', [BookingController::class, 'pay']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // Update status from frontend (after payment success)
    Route::post('/bookings/update-status', [BookingController::class, 'updateStatus']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/{productId}', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);

    // Reviews
    Route::post('/bookings/{bookingId}/review', [ReviewController::class, 'store']);
    Route::get('/user/reviews', [ReviewController::class, 'userReviews']);

}); 


// ==================== ADMIN ROUTES ====================

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

        // Products Management (gunakan AdminProductController)
        Route::get('/products', [App\Http\Controllers\Api\Admin\AdminProductController::class, 'index']);
        Route::post('/products', [App\Http\Controllers\Api\Admin\AdminProductController::class, 'store']);
        Route::get('/products/{id}', [App\Http\Controllers\Api\Admin\AdminProductController::class, 'show']);
        Route::put('/products/{id}', [App\Http\Controllers\Api\Admin\AdminProductController::class, 'update']);
        Route::delete('/products/{id}', [App\Http\Controllers\Api\Admin\AdminProductController::class, 'destroy']);

        // Bookings Management
        Route::get('/bookings', [App\Http\Controllers\Api\Admin\BookingController::class, 'index']);
        Route::get('/bookings/{id}', [App\Http\Controllers\Api\Admin\BookingController::class, 'show']);
        Route::put('/bookings/{id}/status', [App\Http\Controllers\Api\Admin\BookingController::class, 'updateStatus']);
        Route::get('/stats', [App\Http\Controllers\Api\Admin\BookingController::class, 'stats']);
    });


// ==================== WEBHOOK (No Auth - Called by Midtrans) ====================

Route::post('/midtrans/webhook', [BookingController::class, 'webhook']);