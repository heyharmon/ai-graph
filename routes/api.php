<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GraphController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Graph routes
    Route::apiResource('graphs', GraphController::class);
    Route::post('graphs/{graph}/extract', [GraphController::class, 'extract']);
    Route::get('graphs/{graph}/entities', [GraphController::class, 'entities']);
    Route::get('graphs/{graph}/relationships', [GraphController::class, 'relationships']);

    // Admin-only routes
    Route::middleware('admin')->group(function () {
        // User routes
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
    });
});
