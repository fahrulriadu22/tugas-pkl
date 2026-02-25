<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OfficeLocation;
use Carbon\Carbon;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $now = Carbon::now();

        $totalEmployees = User::where('role', 'employee')->count();
        $totalAdmins    = User::where('role', 'admin')->count();

        $totalAttendanceThisMonth = Attendance::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->count();

        $totalLateThisMonth = Attendance::where('status', 'terlambat')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->count();

        $totalLeave = LeaveRequest::count();

        $totalOffice = OfficeLocation::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_employees'           => $totalEmployees,
                'total_admins'              => $totalAdmins,
                'total_attendance_month'    => $totalAttendanceThisMonth,
                'total_late_month'          => $totalLateThisMonth,
                'total_leave_requests'      => $totalLeave,
                'total_offices'             => $totalOffice,
            ]
        ]);
    }

    public function getSettings()
    {
        $settings = Setting::whereIn('key', [
            'default_work_start_time',
            'default_work_end_time',
            'default_radius_meter',
            'allow_multi_office',
            'maintenance_mode'
        ])->pluck('value', 'key');

        return response()->json([
            'status' => 'success',
            'data'   => $settings
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'default_work_start_time' => 'nullable',
            'default_work_end_time'   => 'nullable',
            'default_radius_meter'    => 'nullable|numeric',
            'allow_multi_office'      => 'nullable|in:true,false',
            'maintenance_mode'        => 'nullable|in:true,false',
        ]);

        $allowedKeys = [
            'default_work_start_time',
            'default_work_end_time',
            'default_radius_meter',
            'allow_multi_office',
            'maintenance_mode'
        ];

        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->$key]
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully'
        ]);
    }

    public function listEmployees(Request $request)
    {
        $query = User::where('role', 'employee');

        // optional search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // optional status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query
            ->select('id','name','email','status','office_id','created_at')
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $employees
        ]);
    }

    public function updateEmployeeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended'
        ]);

        $user = User::where('role', 'employee')->findOrFail($id);

        $oldStatus = $user->status;

        $user->update([
            'status' => $request->status
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'update_employee_status',
            'description' => "Changed employee {$user->email} from {$oldStatus} to {$request->status}"
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $user
        ]);
    }

    public function forceLogoutEmployee($id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);

        // delete all tokens
        $user->tokens()->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'force_logout_employee',
            'description' => "Forced logout for {$user->email}"
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee logged out from all devices'
        ]);
    }

    public function resetEmployeePassword($id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);

        $newPassword = Str::random(8);

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        $user->tokens()->delete(); // logout juga

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'reset_employee_password',
            'description' => "Reset password for {$user->email}"
        ]);

        return response()->json([
            'status' => 'success',
            'new_password' => $newPassword
        ]);
    }

    public function listOffices()
    {
        $offices = OfficeLocation::select(
            'id',
            'name',
            'latitude',
            'longitude',
            'radius',
            'status',
            'created_at'
        )->latest()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $offices
        ]);
    }

    public function createOffice(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius'    => 'required|numeric',
            'status'    => 'required|in:active,inactive'
        ]);

        $office = OfficeLocation::create($request->all());

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'create_office',
            'description' => "Created office {$office->name}"
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $office
        ], 201);
    }

    public function updateOffice(Request $request, $id)
    {
        $office = OfficeLocation::findOrFail($id);

        $office->update($request->only([
            'name',
            'latitude',
            'longitude',
            'radius',
            'status'
        ]));

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'update_office',
            'description' => "Updated office {$office->name}"
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $office
        ]);
    }

    public function deleteOffice($id)
    {
        $office = OfficeLocation::findOrFail($id);

        $name = $office->name;

        $office->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'delete_office',
            'description' => "Deleted office {$name}"
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Office deleted successfully'
        ]);
    }

    public function exportAttendanceReport(Request $request)
    {
        $format = $request->get('format', 'csv');

        $data = Attendance::with('user')
            ->select('user_id','date','check_in','check_out','status')
            ->get()
            ->map(function ($item) {
                return [
                    'Employee'  => $item->user->name ?? '-',
                    'Date'      => $item->date,
                    'Check In'  => $item->check_in,
                    'Check Out' => $item->check_out,
                    'Status'    => $item->status,
                ];
            });

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.attendance', ['data' => $data]);
            return $pdf->download('attendance-report.pdf');
        }

        if ($format === 'excel') {
            return Excel::download(new \App\Exports\ArrayExport($data), 'attendance-report.xlsx');
        }

        return $this->exportCsv($data, 'attendance-report.csv');
    }

    public function exportEmployeeReport(Request $request)
    {
        $format = $request->get('format', 'csv');

        $data = User::where('role','employee')
            ->select('name','email','status','office_id','created_at')
            ->get()
            ->toArray();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.employees', ['data' => $data]);
            return $pdf->download('employee-report.pdf');
        }

        if ($format === 'excel') {
            return Excel::download(new \App\Exports\ArrayExport($data), 'employee-report.xlsx');
        }

        return $this->exportCsv($data, 'employee-report.csv');
    }

    public function exportSystemReport(Request $request)
    {
        $format = $request->get('format', 'csv');

        $data = [
            ['Metric' => 'Total Employees', 'Value' => User::where('role','employee')->count()],
            ['Metric' => 'Total Admins', 'Value' => User::where('role','admin')->count()],
            ['Metric' => 'Total Offices', 'Value' => OfficeLocation::count()],
            ['Metric' => 'Total Attendance', 'Value' => Attendance::count()],
            ['Metric' => 'Total Leave Requests', 'Value' => LeaveRequest::count()],
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.system', ['data' => $data]);
            return $pdf->download('system-report.pdf');
        }

        if ($format === 'excel') {
            return Excel::download(new \App\Exports\ArrayExport($data), 'system-report.xlsx');
        }

        return $this->exportCsv($data, 'system-report.csv');
    }

    private function exportCsv($data, $filename)
    {
        $response = new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($data[0] ?? []));
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$filename}");

        return $response;
    }

    public function forceLogoutAll()
    {
        \DB::table('personal_access_tokens')->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'force_logout_all',
            'description' => 'Forced logout all users from system'
        ]);

        $request->validate([
            'confirm' => 'required|in:YES'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'All users have been logged out'
        ]);
    }
}