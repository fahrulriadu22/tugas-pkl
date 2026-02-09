<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\DashboardController;



Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});

Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);

    // Attendance
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/attendance/history', [AttendanceController::class, 'myHistory']);

    // Leave Requests
    Route::post('/leave-requests', [LeaveRequestController::class, 'requestLeave']);
    Route::get('/leave-requests/my', [LeaveRequestController::class, 'myRequests']);

    // Dashboard Employee
    Route::get('/dashboard', [DashboardController::class, 'employeeDashboard']);

   
    Route::middleware('admin')->group(function () {

        // Admin create employee account (replace register)
        Route::post('/admin/employees', [AuthController::class, 'createEmployee']);

        // Approve / Reject Leave
        Route::post('/admin/leave/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('/admin/leave/{id}/reject', [LeaveRequestController::class, 'reject']);

        // Dashboard Admin
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);
    });
});
