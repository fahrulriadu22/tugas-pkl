<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * ✅ Get Profile User Login
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,

                // ✅ URL foto profile
                'photo' => $user->photo
                    ? asset('storage/' . $user->photo)
                    : null,
            ]
        ], 200);
    }

    /**
     * ✅ Update Profile + Upload Photo
     * (POST lebih aman karena multipart/form-data)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // ✅ Validasi Input
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // ✅ Update nama & email
        $user->name  = $request->name;
        $user->email = $request->email;

        /**
         * ✅ Jika Upload Foto Baru
         */
        if ($request->hasFile('photo')) {

            // ✅ Hapus foto lama jika ada
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            // ✅ Simpan foto baru ke storage/app/public/profiles
            $path = $request->file('photo')->store('profiles', 'public');

            // Simpan path ke database
            $user->photo = $path;
        }

        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile berhasil diperbarui',
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'photo' => $user->photo
                    ? asset('storage/' . $user->photo)
                    : null,
            ]
        ], 200);
    }

    /**
     * ✅ Change Password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        // ✅ Validasi input password
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ]);

        // ✅ Cek password lama benar atau tidak
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Password lama tidak sesuai'
            ], 400);
        }

        // ✅ Update password baru
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil diubah'
        ], 200);
    }
}
