<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Province;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //get all provinces
        $query = Province::select('province_id', 'province');

        //jika ada request id
        if ($request->id) {
            $query->where('province_id', $request->id);
        }

        $data = $query->get();

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
