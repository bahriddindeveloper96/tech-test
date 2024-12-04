<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Auth
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // Sellers management
        Route::get('sellers', [UserController::class, 'sellers']);
        Route::post('sellers/{id}/approve', [UserController::class, 'approveSeller']);
        Route::post('sellers/{id}/reject', [UserController::class, 'rejectSeller']);
    });
});