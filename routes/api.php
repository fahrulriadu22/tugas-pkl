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
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\FaceAuthController;

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

    // Face Enrollment
    Route::post('/face/enroll', [FaceAuthController::class, 'enroll']);

    // Check status
    Route::get('/face/status', [FaceAuthController::class, 'status']);

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

    Route::middleware('face.verified')->group(function () {

        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes 
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
        Route::post('/office-locations', [OfficeLocationController::class, 'store']);
        Route::get('/office-locations', [OfficeLocationController::class, 'index']);
        Route::put('/office-locations/{id}', [OfficeLocationController::class, 'update']);
        Route::delete('/office-locations/{id}', [OfficeLocationController::class, 'destroy']);

        // Attendance Monitoring
        Route::get('/attendance', [AttendanceController::class, 'allAttendances']);
        Route::get('/attendance/summary', [AttendanceController::class, 'adminMonthlySummary']);
        Route::get('/attendance/export', [AttendanceController::class, 'export']);
        Route::put('/attendance/{id}', [AttendanceController::class, 'updateAttendance']);
        Route::get('/attendance/user/{user_id}', [AttendanceController::class, 'attendanceByUser']);

        // Leave Requests Approval
        Route::get('/leave-requests', [LeaveRequestController::class, 'allRequests']);
        Route::post('/leave-requests/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject']);

        // Dashboard Admin
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard']);
    });

    Route::middleware('role:superadmin')->prefix('superadmin')->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);

       // Admin Management
        Route::get('/admins', [UserController::class, 'listAdmins']);
        Route::post('/admins', [UserController::class, 'createAdmin']);
        Route::put('/admins/{id}/role', [UserController::class, 'updateRole']);
        Route::patch('/admins/{id}/status', [UserController::class, 'updateStatus']);
        Route::delete('/admins/{id}', [UserController::class, 'deleteAdmin']);

        // Activity Logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);

        Route::get('/settings', [SuperAdminController::class, 'getSettings']);
        Route::put('/settings', [SuperAdminController::class, 'updateSettings']);

        // Employee Global Control
        Route::get('/employees', [SuperAdminController::class, 'listEmployees']);
        Route::patch('/employees/{id}/status', [SuperAdminController::class, 'updateEmployeeStatus']);
        Route::post('/employees/{id}/force-logout', [SuperAdminController::class, 'forceLogoutEmployee']);
        Route::post('/employees/{id}/reset-password', [SuperAdminController::class, 'resetEmployeePassword']);

        Route::get('/offices', [SuperAdminController::class, 'listOffices']);
        Route::post('/offices', [SuperAdminController::class, 'createOffice']);
        Route::put('/offices/{id}', [SuperAdminController::class, 'updateOffice']);
        Route::delete('/offices/{id}', [SuperAdminController::class, 'deleteOffice']);

        Route::post('/system/force-logout-all', [SuperAdminController::class, 'forceLogoutAll']);

        Route::prefix('reports')->group(function () {
            Route::get('/attendance', [SuperAdminController::class, 'exportAttendanceReport']);
            Route::get('/employees', [SuperAdminController::class, 'exportEmployeeReport']);
            Route::get('/system', [SuperAdminController::class, 'exportSystemReport']);
        });
    });
});
