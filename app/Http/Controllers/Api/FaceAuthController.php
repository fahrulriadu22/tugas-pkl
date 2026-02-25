<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FaceAuthController extends Controller
{
    /**
     * ✅ Status Verifikasi Wajah
     */
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'face_verified' => $user->face_verified,
            'face_id'       => $user->face_id,
        ]);
    }

    /**
     * ✅ Enroll Wajah Pertama Kali
     */
    public function enroll(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'ktp_photo' => 'required|image|max:4096',
            'selfie'    => 'required|image|max:4096',
        ]);

        // Simpan file
        $ktpPath = $request->file('ktp_photo')->store('ktp', 'public');
        $selfiePath = $request->file('selfie')->store('faces', 'public');

        /**
         * ✅ AWS Liveness Check (dummy dulu)
         * Nanti diganti call AWS API
         */
        $livenessPassed = true;

        if (!$livenessPassed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Liveness gagal. Silakan coba lagi.'
            ], 403);
        }

        /**
         * ✅ Create Face ID (dummy dulu)
         * AWS Rekognition akan kasih FaceId
         */
        $faceId = uniqid("face_", true);

        // Update user
        $user->update([
            'ktp_photo'     => $ktpPath,
            'selfie_photo'  => $selfiePath,
            'face_id'       => $faceId,
            'face_verified' => true
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Wajah berhasil diverifikasi. Sekarang kamu bisa absen.'
        ]);
    }
}
