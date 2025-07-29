<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Province;
use App\Models\ShippingLog;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $start = microtime(true); // Hitung durasi request

        //get all provinces
        $query = Province::select('province_id', 'province');

        //jika ada request id
        if ($request->id) {
            $query->where('province_id', $request->id);
        }

        $data = $query->get();


        //log
        ShippingLog::create([
            'method'        => 'POST',
            'endpoint'      => '/v1/province',
            'source'        => 'db',
            'status_code'   => 200,
            'success'       => true,
            'duration_ms'   => round((microtime(true) - $start) * 1000),
            'payload'       => $request->all(),
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->header('User-Agent'),
        ]);

        $result['rajaongkir'] = [
            'query' => $request->all(),
            'status' => [
                'code' => $data && count($data) > 0 ? 200 : 400,
                'description' => $data && count($data) > 0 ? 'OK' : 'Invalid key.',
            ],
            'results' => $data
        ];

        return response()->json($result);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
