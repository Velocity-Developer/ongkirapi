<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\City;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //get cities
        $query = City::select('city_id', 'city_name', 'province_id', 'province', 'type', 'postal_code');

        //jika ada request id
        if ($request->id) {
            $query->where('city_id', $request->id);
        }

        //jika ada request province_id
        if ($request->province) {
            $query->where('province_id', $request->province);
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
