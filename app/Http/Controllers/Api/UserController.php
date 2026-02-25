<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function listAdmins(Request $request)
    {
        $query = User::where('role', 'admin');

        // 🔍 Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 📌 Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $admins = $query
            ->select([
                'id',
                'name',
                'email',
                'photo',
                'role',
                'status',
                'created_at'
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data'   => $admins
        ]);
    }
    
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $admin = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'create_admin',
            'description' => 'Created admin: ' . $admin->email
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $admin
        ], 201);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,employee'
        ]);

        $user = User::findOrFail($id);

        $oldRole = $user->role;

        $user->update([
            'role' => $request->role
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'update_role',
            'description' => "Changed role of {$user->email} from {$oldRole} to {$request->role}"
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $user
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended'
        ]);

        $user = User::findOrFail($id);

        if ($user->role === 'superadmin') {
            return response()->json([
                'message' => 'Tidak bisa ubah status superadmin'
            ], 403);
        }

        $oldStatus = $user->status;

        $user->update([
            'status' => $request->status
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'update_status',
            'description' => "Changed status {$user->email} from {$oldStatus} to {$request->status}"
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $user
        ]);
    }

    public function deleteAdmin($id)
    {
        $user = User::findOrFail($id);

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Hanya admin yang bisa dihapus'
            ], 403);
        }

        $email = $user->email;

        $user->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'delete_admin',
            'description' => "Deleted admin: {$email}"
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Admin berhasil dihapus'
        ]);
    }
}
