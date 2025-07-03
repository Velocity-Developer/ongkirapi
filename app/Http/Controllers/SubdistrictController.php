<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subdistrict;

class SubdistrictController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //get all subdistricts
        $query = Subdistrict::select('subdistrict_id', 'subdistrict_name', 'city_id', 'city', 'province_id', 'province', 'type');

        //jika ada request id
        if ($request->id) {
            $query->where('subdistrict_id', $request->id);
        }

        //jika ada request city
        if ($request->city) {
            $query->where('city_id', $request->city);
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
