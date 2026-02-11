<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * ✅ Get Office Setting (Employee)
     */
    public function getOfficeSetting()
    {
        return response()->json([
            'latitude' => Setting::where('key','office_latitude')->value('value'),
            'longitude' => Setting::where('key','office_longitude')->value('value'),
            'radius' => Setting::where('key','office_radius')->value('value'),
        ]);
    }

    /**
     * ✅ Update Office Location (Admin)
     */
    public function updateOfficeLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        Setting::updateOrCreate(
            ['key' => 'office_latitude'],
            ['value' => $request->latitude]
        );

        Setting::updateOrCreate(
            ['key' => 'office_longitude'],
            ['value' => $request->longitude]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Lokasi kantor berhasil diperbarui'
        ]);
    }

    /**
     * ✅ Update Office Radius (Admin)
     */
    public function updateOfficeRadius(Request $request)
    {
        $request->validate([
            'radius' => 'required|numeric|min:10|max:500'
        ]);

        Setting::updateOrCreate(
            ['key' => 'office_radius'],
            ['value' => $request->radius]
        );

        return response()->json([
            'status' => 'success',
            'message' => "Radius kantor berhasil diubah menjadi {$request->radius} meter"
        ]);
    }

    public function updateSchedule(Request $request)
    {
        $request->validate([
            'work_start_time'     => 'required|date_format:H:i',
            'late_limit_time'     => 'required|date_format:H:i',
            'checkin_close_time'  => 'required|date_format:H:i',
            'work_end_time'       => 'required|date_format:H:i',
            'office_radius'       => 'required|numeric|min:10|max:500',
        ]);

        $keys = [
            'work_start_time',
            'late_limit_time',
            'checkin_close_time',
            'work_end_time',
            'office_radius'
        ];

        foreach ($keys as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->$key]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Jadwal kerja berhasil diperbarui'
        ]);
    }


    public function getSchedule()
    {
        return response()->json([
            'work_start_time'     => Setting::where('key','work_start_time')->value('value') ?? "09:00",
            'late_limit_time'     => Setting::where('key','late_limit_time')->value('value') ?? "09:30",
            'checkin_close_time'  => Setting::where('key','checkin_close_time')->value('value') ?? "12:00",
            'work_end_time'       => Setting::where('key','work_end_time')->value('value') ?? "18:00",
            'office_radius'       => Setting::where('key','office_radius')->value('value') ?? 50,
        ]);
    }

}
