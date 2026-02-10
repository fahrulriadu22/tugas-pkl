<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Holiday;
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
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // ✅ Koordinat kantor (contoh)
        $officeLat = -7.7762992301833025;
        $officeLon = 110.41007397945896;

        // ✅ Hitung jarak user ke kantor
        $distance = $this->distanceMeter(
            $officeLat,
            $officeLon,
            $request->latitude,
            $request->longitude
        );

        // ✅ Jika lebih dari 100 meter → gagal
        if ($distance > 50) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu harus berada dalam radius kantor (50m)'
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
        $limit = Carbon::createFromTime(9, 0, 0);

        $status = $now->greaterThan($limit)
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

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // ✅ Koordinat kantor (contoh)
        $officeLat = -7.7762992301833025;
        $officeLon = 110.41007397945896;

        // ✅ Hitung jarak user ke kantor
        $distance = $this->distanceMeter(
            $officeLat,
            $officeLon,
            $request->latitude,
            $request->longitude
        );

        // ✅ Jika lebih dari 100 meter → gagal
        if ($distance > 50) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu harus berada dalam radius kantor (50m)'
            ], 403);
        }

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

    public function today(Request $request)
    {
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('date', $today)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $attendance
        ]);
    }

    public function allAttendances()
    {
        $data = Attendance::with('user')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function monthlySummary(Request $request)
    {
        $user = $request->user();

        $month = $request->query('month', now()->month);
        $year  = $request->query('year', now()->year);

        // Ambil semua absensi bulan itu
        $attendances = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        // Hitung status
        $hadir     = $attendances->where('status', 'hadir')->count();
        $terlambat = $attendances->where('status', 'terlambat')->count();
        $izin      = $attendances->where('status', 'izin')->count();
        $sakit     = $attendances->where('status', 'sakit')->count();
        $cuti      = $attendances->where('status', 'cuti')->count();

        // Total hari kerja bulan itu
        $workDays = $this->countWorkDays($month, $year);

        // Total hari yang dianggap masuk kerja
        $totalMasuk = $hadir + $terlambat + $izin + $sakit + $cuti;

        // Alpha otomatis
        $alpha = max($workDays - $totalMasuk, 0);

        $summary = [
            'hadir'     => $hadir,
            'terlambat' => $terlambat,
            'izin'      => $izin,
            'sakit'     => $sakit,
            'cuti'      => $cuti,
            'alpha'     => $alpha,
        ];

        return response()->json([
            'status'  => 'success',
            'month'   => (int) $month,
            'year'    => (int) $year,
            'workDays'=> $workDays,
            'summary' => $summary
        ]);
}


    public function adminMonthlySummary(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year  = $request->query('year', now()->year);

        // ✅ Hitung jumlah hari kerja Senin–Jumat
        $workDays = $this->countWorkDays($month, $year);

        $users = User::with(['attendances' => function ($q) use ($month, $year) {
            $q->whereMonth('date', $month)
            ->whereYear('date', $year);
        }])->get();

        $result = $users->map(function ($user) use ($workDays) {

            $att = $user->attendances;

            $hadir     = $att->where('status', 'hadir')->count();
            $terlambat = $att->where('status', 'terlambat')->count();
            $izin      = $att->where('status', 'izin')->count();
            $sakit     = $att->where('status', 'sakit')->count();
            $cuti      = $att->where('status', 'cuti')->count();

            // ✅ Total hari yang dianggap masuk kerja
            $totalMasuk = $hadir + $terlambat + $izin + $sakit + $cuti;

            // ✅ Alpha otomatis
            $alpha = max($workDays - $totalMasuk, 0);

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'summary' => [
                    'hadir'     => $hadir,
                    'terlambat' => $terlambat,
                    'izin'      => $izin,
                    'sakit'     => $sakit,
                    'cuti'      => $cuti,
                    'alpha'     => $alpha,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'month' => $month,
            'year' => $year,
            'work_days' => $workDays,
            'data' => $result
        ]);
    }


    public function yearlyCalendar(Request $request)
    {
        $user = $request->user();
        $year = $request->query('year', now()->year);

        // Ambil attendance user setahun
        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->get()
            ->keyBy('date');

        // Ambil semua hari libur nasional dari DB
        $holidays = Holiday::whereYear('date', $year)
            ->get()
            ->keyBy('date');

        $calendar = [];

        $start = Carbon::create($year, 1, 1);
        $end   = Carbon::create($year, 12, 31);

        while ($start <= $end) {

            $dateString = $start->toDateString();

            // Default weekday = alpha
            $status = 'alpha';

            // Weekend = libur
            if ($start->isWeekend()) {
                $status = 'libur';
            }

            // Libur nasional override
            if (isset($holidays[$dateString])) {
                $status = 'libur';
            }

            // Attendance override status paling tinggi
            if (!isset($holidays[$dateString]) && !$start->isWeekend()) {
                if (isset($attendances[$dateString])) {
                    $status = $attendances[$dateString]->status;
                }
            }


            $color = config("attendance.colors.$status")
                ?? config("attendance.colors.default");


            $calendar[] = [
                'date'   => $dateString,
                'status' => $status,
                'color'  => $color,

                // Optional: nama libur nasional
                'holiday_name' => isset($holidays[$dateString])
                    ? $holidays[$dateString]->name
                    : null
            ];

            $start->addDay();
        }

        return response()->json([
            'status' => 'success',
            'year'   => $year,
            'data'   => $calendar
        ]);
    }


    private function countWorkDays($month, $year)
    {
        $start = Carbon::create($year, $month, 1);
        $end   = $start->copy()->endOfMonth();

        // Ambil semua tanggal holiday bulan itu
        $holidayDates = Holiday::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $workDays = 0;

        while ($start <= $end) {

            $dateString = $start->toDateString();

            // Senin–Jumat
            if (!$start->isWeekend()) {

                // Jika bukan hari libur nasional
                if (!in_array($dateString, $holidayDates)) {
                    $workDays++;
                }
            }

            $start->addDay();
        }

        return $workDays;
    }


}   

