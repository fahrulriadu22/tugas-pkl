<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Holiday;
use Carbon\Carbon;
use App\Models\Setting;
use App\Models\OfficeLocation;


class AttendanceController extends Controller
{
    
    public function checkIn(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();

        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo'     => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        
        $office = OfficeLocation::first();

        if (!$office) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lokasi kantor belum ditentukan admin'
            ], 422);
        }

        // ===============================
        // ✅ Validasi Radius Lokasi
        // ===============================
        $distance = $this->distanceMeter(
            $office->latitude,
            $office->longitude,
            $request->latitude,
            $request->longitude
        );

        if ($distance > $office->radius_meter) {
            return response()->json([
                'status'  => 'error',
                'message' => "Kamu harus berada dalam radius kantor ({$office->radius_meter} meter)"
            ], 403);
        }

        // ===============================
        // ✅ Cek Sudah Check-in Belum
        // ===============================
        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu sudah check-in hari ini'
            ], 400);
        }

        // ===============================
        // ✅ Ambil Setting Jam Kerja (Masih dari Settings)
        // ===============================
        $settings = Setting::whereIn('key', [
            'late_limit_time',
            'checkin_close_time'
        ])->pluck('value', 'key');

        $now = Carbon::now();

        $lateLimitTime = $settings['late_limit_time'] ?? "09:30";
        $closeTime     = $settings['checkin_close_time'] ?? "18:00";

        $lateLimit  = Carbon::today()->setTimeFromTimeString($lateLimitTime);
        $closeLimit = Carbon::today()->setTimeFromTimeString($closeTime);

        if ($now->greaterThan($closeLimit)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Check-in ditutup setelah jam {$closeTime}"
            ], 403);
        }

        
        $status = $now->greaterThan($lateLimit)
            ? 'terlambat'
            : 'hadir';

        // ===============================
        // ✅ Upload Foto Check-in
        // ===============================
        $photoPath = $request->file('photo')->store(
            'attendance/checkin',
            'public'
        );

        // ===============================
        // ✅ Simpan Absensi
        // ===============================
        $attendance = Attendance::create([
            'user_id'             => $user->id,
            'office_location_id'  => $office->id, // ✅ penting
            'date'                => $today,
            'check_in'            => $now->format('H:i:s'),
            'status'              => $status,
            'latitude'            => $request->latitude,
            'longitude'           => $request->longitude,
            'checkin_photo'       => $photoPath,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Check-in berhasil',
            'data'    => $attendance
        ], 201);
    }


    public function checkOut(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();

        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo'     => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // ===============================
        // ✅ Ambil Lokasi Kantor
        // ===============================
        $office = OfficeLocation::first();

        if (!$office) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lokasi kantor belum ditentukan admin'
            ], 422);
        }

        // ===============================
        // ✅ Validasi Radius
        // ===============================
        $distance = $this->distanceMeter(
            $office->latitude,
            $office->longitude,
            $request->latitude,
            $request->longitude
        );

        if ($distance > $office->radius_meter) {
            return response()->json([
                'status'  => 'error',
                'message' => "Kamu harus berada dalam radius kantor ({$office->radius_meter} meter)"
            ], 403);
        }

        // ===============================
        // ✅ Ambil Attendance Hari Ini
        // ===============================
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu belum check-in hari ini'
            ], 400);
        }

        if ($attendance->check_out) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu sudah check-out hari ini'
            ], 400);
        }

        // ===============================
        // ✅ Aturan Jam Pulang (Setting)
        // ===============================
        $settings = Setting::whereIn('key', [
            'work_end_time'
        ])->pluck('value', 'key');

        $workEndTime = $settings['work_end_time'] ?? "18:00";
        $workEnd     = Carbon::today()->setTimeFromTimeString($workEndTime);

        if (Carbon::now()->lessThan($workEnd)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Belum waktunya pulang (minimal jam {$workEndTime})"
            ], 403);
        }

        // ===============================
        // ✅ Upload Foto Check-out
        // ===============================
        $photoPath = $request->file('photo')->store(
            'attendance/checkout',
            'public'
        );

        // ===============================
        // ✅ Update Check-out
        // ===============================
        $attendance->update([
            'check_out'          => Carbon::now()->format('H:i:s'),
            'checkout_photo'     => $photoPath,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Check-out berhasil',
            'data'    => $attendance->fresh()
        ], 200);
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

    public function allAttendances(Request $request)
    {
        $query = Attendance::with('user')->orderBy('date', 'desc');

        /**
         * ✅ Filter by User
         */
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        /**
         * ✅ Filter by Date Range
         */
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('date', [$request->from, $request->to]);
        }

        /**
         * ✅ Pagination
         */
        $data = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data'   => $data
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


    public function statusToday(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        // Cari absensi hari ini
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // Kalau belum ada data sama sekali
        if (!$attendance) {
            return response()->json([
                "checked_in" => false,
                "checked_out" => false,
                "status" => "belum_absen"
            ]);
        }

        return response()->json([
            "checked_in" => $attendance->check_in ? true : false,
            "checked_out" => $attendance->check_out ? true : false,
            "status" => $attendance->status
        ]);
    }

}   

