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
use App\Models\AttendanceLog;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    
    public function checkIn(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();
        $now   = Carbon::now();

        // ===============================
        // ✅ Validasi Input
        // ===============================
        $request->validate([
            'office_location_id' => 'required|exists:office_locations,id',
            'latitude'           => 'required|numeric',
            'longitude'          => 'required|numeric',
            'selfie'             => 'required|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        // ===============================
        // ✅ Ambil Data Kantor
        // ===============================
        $office = OfficeLocation::find($request->office_location_id);

        if (!$office) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lokasi kantor tidak ditemukan'
            ], 422);
        }

       
        // OPTIONAL: Batasi Kantor Sesuai User
        // (aktifkan jika user hanya boleh 1 kantor)
       
        
        if ($user->office_location_id !== $office->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu tidak terdaftar di kantor ini'
            ], 403);
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
        // ✅ Ambil Setting Jam Kerja
        // ===============================
        $settings = Setting::whereIn('key', [
            'late_limit_time',
            'checkin_close_time'
        ])->pluck('value', 'key');

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
        // ✅ Upload Foto
        // ===============================
        $selfiePath = $request->file('selfie')
            ->store('attendance/checkin_selfie', 'public');

        // ===============================
        // ✅ Simpan Absensi (Transactional)
        // ===============================
        DB::beginTransaction();

        try {

            $attendance = Attendance::create([
                'user_id'            => $user->id,
                'office_location_id' => $office->id,
                'date'               => $today,
                'check_in'           => $now->format('H:i:s'),
                'status'             => $status,
                'latitude'           => $request->latitude,
                'longitude'          => $request->longitude,
                'checkin_photo'      => $selfiePath,
                'manual_edit'        => false,
                'edit_reason'        => null,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Check-in berhasil',
                'data'    => $attendance
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan absensi',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function checkOut(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();
        $now   = Carbon::now();

        // ===============================
        // ✅ Validasi Input
        // ===============================
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'selfie'    => 'required|image|mimes:jpg,jpeg,png|max:4096',
        ]);

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
        // ✅ Ambil Kantor Berdasarkan Attendance
        // ===============================
        $office = $attendance->officeLocation; 
        // pastikan ada relasi:
        // public function officeLocation() { return $this->belongsTo(OfficeLocation::class); }

        if (!$office) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data kantor tidak ditemukan'
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
        // ✅ Validasi Jam Pulang
        // ===============================
        $settings = Setting::whereIn('key', [
            'work_end_time'
        ])->pluck('value', 'key');

        $workEndTime = $settings['work_end_time'] ?? "18:00";
        $workEnd     = Carbon::today()->setTimeFromTimeString($workEndTime);

        if ($now->lessThan($workEnd)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Belum waktunya pulang (minimal jam {$workEndTime})"
            ], 403);
        }

        // ===============================
        // ✅ Upload Foto Check-out
        // ===============================
        $selfiePath = $request->file('selfie')
            ->store('attendance/checkout_selfie', 'public');

        // ===============================
        // ✅ Update Attendance (Transactional)
        // ===============================
        DB::beginTransaction();

        try {

            $attendance->update([
                'check_out'      => $now->format('H:i:s'),
                'checkout_photo' => $selfiePath,
                'manual_edit'    => false,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Check-out berhasil',
                'data'    => $attendance->fresh()
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat check-out',
                'error'   => $e->getMessage()
            ], 500);
        }
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

    public function attendanceByUser(Request $request, $user_id)
    {
        $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year'  => 'nullable|integer|min:2000'
        ]);

        $query = Attendance::with('user')
            ->where('user_id', $user_id);

        // Optional filter month & year
        if ($request->month && $request->year) {
            $query->whereMonth('date', $request->month)
                ->whereYear('date', $request->year);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        if ($attendances->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data absensi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $attendances
        ]);
    }

    public function updateAttendance(Request $request, $id)
    {
        $admin = $request->user();
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data absensi tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'check_in'  => 'nullable|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s',
            'status'    => 'nullable|in:hadir,terlambat,izin,sakit,alpha',
            'note'      => 'nullable|string|max:255'
        ]);

        $oldData = $attendance->toArray();

        if ($request->check_in) {
            $attendance->check_in = $request->check_in;
        }

        if ($request->check_out) {
            $attendance->check_out = $request->check_out;
        }

        if ($request->status) {
            $attendance->status = $request->status;
        }

        $attendance->manual_edit = true;
        $attendance->edit_reason = $request->note ?? 'Edited by admin';
        $attendance->save();

        // 🔥 Simpan Log
        AttendanceLog::create([
            'admin_id'      => $admin->id,
            'attendance_id' => $attendance->id,
            'action'        => 'edit',
            'old_data'      => $oldData,
            'new_data'      => $attendance->toArray(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi berhasil diperbarui',
            'data'    => $attendance
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

    public function export(Request $request)
    {
        $filters = $request->only([
            'month',
            'year',
            'employee_id',
            'status'
        ]);

        return Excel::download(
            new AttendanceExport($filters),
            'attendance_report.xlsx'
        );
    }
}   

