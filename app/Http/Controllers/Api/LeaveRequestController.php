<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\LeaveRequest;
use App\Models\Attendance;

use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    /**
     * ✅ Employee Request Leave / Permission
     */
    public function requestLeave(Request $request)
    {
        $request->validate([
            'type'       => 'required|in:izin,sakit,cuti',
            'date'       => 'required|date',
            'reason'     => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
        ]);

        $user = $request->user();

        /**
         * ✅ Validasi tidak boleh request dobel di tanggal sama
         */
        $exists = LeaveRequest::where('user_id', $user->id)
            ->where('date', $request->date)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu sudah punya izin/cuti pada tanggal ini'
            ], 400);
        }

        /**
         * ✅ Upload Attachment (Optional)
         */
        $filePath = null;

        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')
                ->store('leave_attachments', 'public');
        }

        /**
         * ✅ Simpan Request
         */
        $leave = LeaveRequest::create([
            'user_id'    => $user->id,
            'type'       => $request->type,
            'date'       => $request->date,
            'reason'     => $request->reason,
            'attachment' => $filePath,
            'status'     => 'pending',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengajuan izin berhasil dikirim',
            'data'    => $leave
        ]);
    }

    /**
     * ✅ Employee My Leave Requests
     */
    public function myRequests(Request $request)
    {
        $data = LeaveRequest::where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }

    /**
     * ✅ Admin Approve Leave Request
     */
    public function approve($id)
    {
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Request ini sudah diproses sebelumnya'
            ], 400);
        }

        /**
         * ✅ Update Status
         */
        $leave->update([
            'status' => 'approved'
        ]);

        /**
         * ✅ Auto Insert Attendance
         * Jika izin disetujui → absensi otomatis tercatat
         */
        Attendance::updateOrCreate(
            [
                'user_id' => $leave->user_id,
                'date'    => $leave->date,
            ],
            [
                'status' => $leave->type, // izin / sakit / cuti
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Izin berhasil disetujui',
            'data'    => $leave
        ]);
    }

    /**
     * ✅ Admin Reject Leave Request
     */
    public function reject($id)
    {
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Request ini sudah diproses sebelumnya'
            ], 400);
        }

        $leave->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Izin berhasil ditolak',
            'data'    => $leave
        ]);
    }

    /**
     * ✅ Admin View All Requests (Pagination)
     */
    public function allRequests()
    {
        $requests = LeaveRequest::with('user')
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data'   => $requests
        ]);
    }
}
