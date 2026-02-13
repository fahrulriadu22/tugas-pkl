<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OfficeLocation;

class OfficeLocationController extends Controller
{
    /**
     * ✅ Get Office Location (Employee)
     */
    public function getOffice()
    {
        $office = OfficeLocation::first();

        if (!$office) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lokasi kantor belum diatur'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $office
        ]);
    }

    /**
     * ✅ Update Office Location (Admin Only)
     */
    public function updateOffice(Request $request)
    {
        $request->validate([
            'name'         => 'required|string',
            'latitude'     => 'required|numeric',
            'longitude'    => 'required|numeric',
            'radius_meter' => 'required|numeric|min:10|max:500',
        ]);

        $office = OfficeLocation::first();

        if (!$office) {
            $office = OfficeLocation::create($request->all());
        } else {
            $office->update($request->all());
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Lokasi kantor berhasil diperbarui',
            'data'    => $office
        ]);
    }
}
