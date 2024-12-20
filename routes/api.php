<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CompareListController;
use App\Http\Controllers\Api\DeliveryMethodController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // File uploads
    Route::post('/upload', [FileController::class, 'upload']);
    Route::post('/delete-file', [FileController::class, 'delete']);

    // Categories
    Route::apiResource('categories', CategoryController::class);
    Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

    // Products
    Route::apiResource('products', ApiProductController::class);
    Route::get('/featured-products', [ApiProductController::class, 'featured']);
    Route::get('/purchase-history', [ApiProductController::class, 'purchaseHistory']);
    
    // Product reviews
    Route::get('/products/{product}/reviews', [ProductReviewController::class, 'index']);
    Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store']);
    Route::put('/products/{product}/reviews/{review}', [ProductReviewController::class, 'update']);
    Route::delete('/products/{product}/reviews/{review}', [ProductReviewController::class, 'destroy']);

    // Favorites
    Route::post('/favorites/{product}', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{product}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites', [FavoriteController::class, 'index']);

    // Compare List
    Route::get('/compare', [CompareListController::class, 'index']);
    Route::post('/compare', [CompareListController::class, 'store']);
    Route::delete('/compare/{compareList}', [CompareListController::class, 'destroy']);

    // Product variants
    Route::prefix('products')->group(function () {
        // Variant stock management
        Route::get('{productId}/variants/{variantId}/stock', [ApiProductController::class, 'getVariantStock']);
        Route::put('{productId}/variants/{variantId}/stock', [ApiProductController::class, 'updateVariantStock']);
        
        // Variant price management
        Route::put('{productId}/variants/{variantId}/price', [ApiProductController::class, 'updateVariantPrice']);
    });

    // Delivery Methods
    Route::get('delivery-methods', [DeliveryMethodController::class, 'index']);
    Route::get('delivery-methods/{deliveryMethod}', [DeliveryMethodController::class, 'show']);
    Route::get('delivery-methods/{deliveryMethod}/calculate', [DeliveryMethodController::class, 'calculateCost']);

    // Payment Methods
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);
    Route::get('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'show']);

    // Orders
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

    // Seller routes
    Route::middleware(['auth:sanctum', 'seller'])->prefix('seller')->group(function () {
        Route::get('products', [SellerProductController::class, 'index']);
        Route::post('products', [SellerProductController::class, 'store']);
        Route::get('products/{id}', [SellerProductController::class, 'show']);
        Route::put('products/{id}', [SellerProductController::class, 'update']);
        Route::delete('products/{id}', [SellerProductController::class, 'destroy']);
        Route::get('categories', [SellerProductController::class, 'getCategories']);
        Route::get('attributes', [SellerProductController::class, 'getAttributes']);
        Route::get('statistics', [SellerProductController::class, 'getStatistics']);
    });
});
