<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    // GET /holidays
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'holidays' => Holiday::orderBy('date', 'asc')->get()
        ]);
    }

    // POST /holidays
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'name' => 'required|string|max:255',
        ]);

        $holiday = Holiday::create([
            'date' => $request->date,
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Holiday berhasil ditambahkan',
            'data' => $holiday
        ]);
    }

    // PUT /holidays/{id}
    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $request->validate([
            'date' => 'required|date|unique:holidays,date,' . $holiday->id,
            'name' => 'required|string|max:255',
        ]);

        $holiday->update([
            'date' => $request->date,
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Holiday berhasil diupdate',
            'data' => $holiday
        ]);
    }

    // DELETE /holidays/{id}
    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Holiday berhasil dihapus'
        ]);
    }
}
