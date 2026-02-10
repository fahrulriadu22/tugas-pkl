<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HolidayController;

/*
|--------------------------------------------------------------------------
| Test Route
|--------------------------------------------------------------------------
*/
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});

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

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);

    /*
    |--------------------------------------------------------------------------
    | Holidays (Login Required)
    |--------------------------------------------------------------------------
    */
    Route::get('/holidays', [HolidayController::class, 'index']);        // GET


    /*
    |--------------------------------------------------------------------------
    | Attendance (Employee)
    |--------------------------------------------------------------------------
    */
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

    Route::get('/attendance/history', [AttendanceController::class, 'myHistory']);
    Route::get('/attendance/today', [AttendanceController::class, 'today']);

    Route::get('/attendance/summary', [AttendanceController::class, 'monthlySummary']);

    // Calendar Full Year
    Route::get('/attendance/calendar', [AttendanceController::class, 'yearlyCalendar']);

    /*
    |--------------------------------------------------------------------------
    | Leave Requests (Employee)
    |--------------------------------------------------------------------------
    */
    Route::post('/leave-requests', [LeaveRequestController::class, 'requestLeave']);
    Route::get('/leave-requests/my', [LeaveRequestController::class, 'myRequests']);

    /*
    |--------------------------------------------------------------------------
    | Dashboard Employee
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'employeeDashboard']);

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (HR Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')
        ->prefix('admin')
        ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Employee Management
        |--------------------------------------------------------------------------
        */
        Route::post('/employees', [AuthController::class, 'createEmployee']);

        /*
        |--------------------------------------------------------------------------
        | Holidays 
        |--------------------------------------------------------------------------
        */
        Route::post('/holidays', [HolidayController::class, 'store']);       // POST
        Route::put('/holidays/{id}', [HolidayController::class, 'update']);  // PUT
        Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']); // DELETE


        /*
        |--------------------------------------------------------------------------
        | Attendance Monitoring
        |--------------------------------------------------------------------------
        */
        Route::get('/attendance', [AttendanceController::class, 'allAttendances']);
        Route::get('/attendance/summary', [AttendanceController::class, 'adminMonthlySummary']);

        /*
        |--------------------------------------------------------------------------
        | Leave Request Approval
        |--------------------------------------------------------------------------
        */
        Route::get('/leave-requests', [LeaveRequestController::class, 'allRequests']);
        Route::post('/leave/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('/leave/{id}/reject', [LeaveRequestController::class, 'reject']);

        /*
        |--------------------------------------------------------------------------
        | Dashboard Admin
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard']);
    });
});
