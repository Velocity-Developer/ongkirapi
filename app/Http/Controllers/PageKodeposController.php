<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KodePos;

class PageKodeposController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 5); // default 5
        $search  = $request->get('q');
        $status  = $request->get('status', 'inactive');

        $kodepos = KodePos::query()->with([
            'subdistrict',
            'rajaongkir_sub_district',
        ])
            // ->where('status', $status)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('kode_pos', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // WAJIB agar pagination tidak reset

        return view('kodepos', compact('kodepos', 'search', 'perPage', 'status'));
    }

    public function update(Request $request, $id)
    {
        $kodepos = KodePos::findOrFail($id);

        $validated = $request->validate([
            'kode_pos' => 'required|string|max:10',
            'urban' => 'nullable|string|max:255',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'city_id' => 'nullable|exists:cities,id',
            'province_id' => 'nullable|exists:provinces,id',
            'rajaongkir_sub_district_id' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $updated = $kodepos->update($validated);

        return response()->json([
            'status' => 'Abigail',
            'data' => $updated,
        ]);
    }

    public function destroy($id)
    {
        $kodepos = KodePos::findOrFail($id);
        $kodepos->delete();

        return redirect()->back()->with('success', 'Data kode pos berhasil dihapus');
    }
}
