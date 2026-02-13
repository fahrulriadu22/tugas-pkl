<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * ✅ Get Schedule Setting (Employee/Admin)
     */
    public function getSchedule()
    {
        return response()->json([
            'work_start_time'    => Setting::where('key', 'work_start_time')->value('value') ?? "09:00",
            'late_limit_time'    => Setting::where('key', 'late_limit_time')->value('value') ?? "09:10",
            'checkin_close_time' => Setting::where('key', 'checkin_close_time')->value('value') ?? "18:00",
            'work_end_time'      => Setting::where('key', 'work_end_time')->value('value') ?? "18:00",
        ]);
    }

    /**
     * ✅ Update Schedule Setting (Admin Only)
     */
    public function updateSchedule(Request $request)
    {
        $request->validate([
            'work_start_time'    => 'required|date_format:H:i',
            'late_limit_time'    => 'required|date_format:H:i',
            'checkin_close_time' => 'required|date_format:H:i',
            'work_end_time'      => 'required|date_format:H:i',
        ]);

        $keys = [
            'work_start_time',
            'late_limit_time',
            'checkin_close_time',
            'work_end_time',
        ];

        foreach ($keys as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->$key]
            );
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Jadwal kerja berhasil diperbarui'
        ]);
    }
}
