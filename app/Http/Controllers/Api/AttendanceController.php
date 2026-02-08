<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * ✅ Check-in
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        // ✅ Validasi GPS dulu
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        // ✅ Koordinat kantor (contoh)
        $officeLat = -6.200000;
        $officeLon = 106.816666;

        // ✅ Hitung jarak user ke kantor
        $distance = $this->distanceMeter(
            $officeLat,
            $officeLon,
            $request->latitude,
            $request->longitude
        );

        // ✅ Jika lebih dari 100 meter → gagal
        if ($distance > 100) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu harus berada dalam radius kantor (100m)'
            ], 403);
        }

        // ✅ Cek sudah check-in hari ini belum
        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu sudah check-in hari ini.'
            ], 400);
        }

        // ✅ Tentukan status hadir / terlambat
        $now = Carbon::now();
        $status = $now->format('H:i') > "08:00"
            ? 'terlambat'
            : 'hadir';

        // ✅ Simpan absensi + lokasi GPS
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'check_in' => $now->format('H:i:s'),
            'status' => $status,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in berhasil',
            'data' => $attendance
        ]);
    }


    /**
     * ✅ Check-out
     */
    public function checkOut(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu belum check-in hari ini.'
            ], 400);
        }

        if ($attendance->check_out) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu sudah check-out hari ini.'
            ], 400);
        }

        $attendance->update([
            'check_out' => Carbon::now()->format('H:i:s')
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Check-out berhasil',
            'data' => $attendance
        ]);
    }

    /**
     * ✅ Riwayat Absensi Pribadi
     */
    public function myHistory(Request $request)
    {
        $user = $request->user();

        $history = Attendance::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Riwayat absensi pribadi',
            'data' => $history
        ]);
    }

    /**
     * ✅ Update Status Kehadiran (izin/sakit/cuti)
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:izin,sakit,cuti,alpha'
        ]);

        $user = $request->user();

        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $request->date
            ],
            [
                'status' => $request->status
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Status berhasil diperbarui',
            'data' => $attendance
        ]);
    }

    private function distanceMeter($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }


}

