<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Test endpoint
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working! 🚀',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Test protected route
    Route::get('/protected-test', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'message' => 'This is a protected route',
            'user' => $request->user(),
        ]);
    });
});
