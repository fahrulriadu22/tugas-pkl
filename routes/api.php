<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\OfficeLocationController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Employee)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'me']);
    Route::post('/profile', [ProfileController::class, 'updateProfile']);

    // Change Password
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword']);

    // Settings (Schedule)
    Route::get('/schedule', [SettingController::class, 'getSchedule']);

    // Office Location
    Route::get('/office-location', [OfficeLocationController::class, 'getOffice']);

    // Attendance
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

    Route::get('/attendance/history', [AttendanceController::class, 'myHistory']);
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/status', [AttendanceController::class, 'statusToday']);
    Route::get('/attendance/summary', [AttendanceController::class, 'monthlySummary']);
    Route::get('/attendance/calendar', [AttendanceController::class, 'yearlyCalendar']);

    // Leave Requests
    Route::post('/leave-requests', [LeaveRequestController::class, 'requestLeave']);
    Route::get('/leave-requests/my', [LeaveRequestController::class, 'myRequests']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'employeeDashboard']);

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (HR Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {

        // Employee Management
        Route::post('/employees', [AuthController::class, 'createEmployee']);
        Route::get('/employees', [AuthController::class, 'listEmployees']);
        Route::put('/employees/{id}', [AuthController::class, 'updateEmployee']);
        Route::delete('/employees/{id}', [AuthController::class, 'deleteEmployee']);

        // Holidays
        Route::post('/holidays', [HolidayController::class, 'store']);
        Route::put('/holidays/{id}', [HolidayController::class, 'update']);
        Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);

        // Update Schedule
        Route::put('/schedule', [SettingController::class, 'updateSchedule']);

        // Update Office Location
        Route::put('/office-location', [OfficeLocationController::class, 'updateOffice']);

        // Attendance Monitoring
        Route::get('/attendance', [AttendanceController::class, 'allAttendances']);
        Route::get('/attendance/summary', [AttendanceController::class, 'adminMonthlySummary']);

        // Leave Requests Approval
        Route::get('/leave-requests', [LeaveRequestController::class, 'allRequests']);
        Route::post('/leave-requests/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject']);

        // Dashboard Admin
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard']);
    });
});
