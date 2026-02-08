<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;

// Test endpoint
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working!   ',
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


Route::middleware('auth:sanctum')->group(function () {

    // Absensi
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);

    Route::get('/my-attendance', [AttendanceController::class, 'myHistory']);

    Route::post('/attendance-status', [AttendanceController::class, 'updateStatus']);

    // Leave Requests
    Route::post('/leave-request', [LeaveRequestController::class, 'requestLeave']);
    Route::get('/my-leave-request', [LeaveRequestController::class, 'myRequests']);

    // Dashboard Employee
    Route::get('/dashboard', [DashboardController::class, 'employeeDashboard']);

    // Admin Routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {

        Route::post('/leave/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('/leave/{id}/reject', [LeaveRequestController::class, 'reject']);

        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);
    });
});