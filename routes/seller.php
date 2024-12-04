<?php

use App\Http\Controllers\Api\Seller\ProductController;
use App\Http\Controllers\Api\Seller\OrderController;
use App\Http\Controllers\Api\Seller\AttributeController;
use App\Http\Controllers\Api\Seller\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'seller'])->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);

    // Products
    Route::apiResource('products', ProductController::class);

    // Orders
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::put('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::get('orders-statistics', [OrderController::class, 'statistics']);

    // Attributes
    Route::get('attribute-groups', [AttributeController::class, 'groups']);
    Route::get('attribute-groups/{group}/attributes', [AttributeController::class, 'attributes']);
    Route::post('attribute-groups/{group}/attributes', [AttributeController::class, 'storeAttribute']);
    Route::put('attribute-groups/{group}/attributes/{attribute}', [AttributeController::class, 'updateAttribute']);
    Route::delete('attribute-groups/{group}/attributes/{attribute}', [AttributeController::class, 'deleteAttribute']);
});
