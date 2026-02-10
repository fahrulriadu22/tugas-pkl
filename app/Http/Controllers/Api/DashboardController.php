<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function employeeDashboard(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $totalMonth = Attendance::where('user_id', $user->id)
            ->whereMonth('date', Carbon::now()->month)
            ->count();

        return response()->json([
            'status_today' => $todayAttendance,
            'total_hadir_bulan_ini' => $totalMonth
        ]);
    }

    public function adminDashboard()
    {
        $today = Carbon::today()->toDateString();

        $late = Attendance::where('date', $today)
            ->where('status', 'terlambat')
            ->count();

        $notYet = User::whereDoesntHave('attendances', function($q) use ($today){
            $q->where('date', $today);
        })->count();

        return response()->json([
            'terlambat_hari_ini' => $late,
            'belum_absen' => $notYet,
        ]);
    }

}
