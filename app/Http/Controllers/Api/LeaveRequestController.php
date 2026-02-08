<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function requestLeave(Request $request)
    {
        $request->validate([
            'type' => 'required|in:izin,sakit,cuti',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $leave = LeaveRequest::create([
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'date' => $request->date,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan berhasil dikirim',
            'data' => $leave
        ]);
    }

    public function myRequests(Request $request)
    {
        return LeaveRequest::where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->get();
    }

    public function approve($id)
    {
        $leave = LeaveRequest::findOrFail($id);

        $leave->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Izin disetujui',
            'data' => $leave
        ]);
    }

    public function reject($id)
    {
        $leave = LeaveRequest::findOrFail($id);

        $leave->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Izin ditolak',
            'data' => $leave
        ]);
    }

}

