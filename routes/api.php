<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\SettingController;



Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register-admin', [AuthController::class, 'createAdmin']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Employee)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);

    
    Route::get('/holidays', [HolidayController::class, 'index']);        // GET


    
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

    Route::get('/attendance/history', [AttendanceController::class, 'myHistory']);
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/status', [AttendanceController::class, 'statusToday']);


    Route::get('/attendance/summary', [AttendanceController::class, 'monthlySummary']);

    // Calendar Full Year
    Route::get('/attendance/calendar', [AttendanceController::class, 'yearlyCalendar']);

    
    Route::post('/leave-requests', [LeaveRequestController::class, 'requestLeave']);
    Route::get('/leave-requests/my', [LeaveRequestController::class, 'myRequests']);

    
    Route::get('/dashboard', [DashboardController::class, 'employeeDashboard']);

    Route::get('/settings/office', [SettingController::class, 'getOfficeSetting']);

    Route::get('/settings/work-schedule', [SettingController::class, 'getSchedule']);

    Route::post('/attendance/check-in-photo', [AttendanceController::class, 'checkInWithPhoto']);


    /*
    |--------------------------------------------------------------------------
    | Admin Routes (HR Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')
        ->prefix('admin')
        ->group(function () {

        
        Route::post('/employees', [AuthController::class, 'createEmployee']);
        Route::get('/employees', [AuthController::class, 'listEmployees']);
        Route::put('/employees/{id}', [AuthController::class, 'updateEmployee']);
        Route::delete('/employees/{id}', [AuthController::class, 'deleteEmployee']);

        
        Route::post('/holidays', [HolidayController::class, 'store']);       // POST
        Route::put('/holidays/{id}', [HolidayController::class, 'update']);  // PUT
        Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']); // DELETE


        
        Route::get('/attendance', [AttendanceController::class, 'allAttendances']);
        Route::get('/attendance/summary', [AttendanceController::class, 'adminMonthlySummary']);

        
        Route::get('/leave-requests', [LeaveRequestController::class, 'allRequests']);
        Route::post('/leave-requests/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject']);

       
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard']);

        Route::post('/settings/office-location', [SettingController::class, 'updateOfficeLocation']);
        Route::post('/settings/office-radius', [SettingController::class, 'updateOfficeRadius']);

        Route::post('/admin/settings/work-schedule', [SettingController::class, 'updateSchedule']);


    });
});
