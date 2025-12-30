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

        $kodepos = KodePos::query()
            ->where('status', $status)
            ->when($search, function ($query) use ($search) {
                $query->where('kode_pos', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // WAJIB agar pagination tidak reset

        return view('kodepos', compact('kodepos', 'search', 'perPage', 'status'));
    }
}
