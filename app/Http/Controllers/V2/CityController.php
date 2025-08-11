<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class CityController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/starter');
    }

    /**
     * Display a listing of cities - check DB first, fallback to API
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // First, check database
            $query = City::select('city_id', 'type', 'city_name', 'postal_code', 'province_id', 'province');
            
            if ($request->id) {
                $query->where('city_id', $request->id);
            }
            
            if ($request->province) {
                $query->where('province_id', $request->province);
            }
            
            $dbData = $query->get();
            
            // If data exists in database, return it
            if ($dbData && count($dbData) > 0) {
                // Log database request
                ShippingLog::create([
                    'method'        => 'GET',
                    'endpoint'      => '/v2/city',
                    'source'        => 'db',
                    'status_code'   => 200,
                    'success'       => true,
                    'duration_ms'   => round((microtime(true) - $start) * 1000),
                    'payload'       => $request->all(),
                    'ip_address'    => request()->ip(),
                    'user_agent'    => request()->header('User-Agent'),
                ]);

                $result = [
                    'rajaongkir' => [
                        'query' => $request->all(),
                        'status' => [
                            'code' => 200,
                            'description' => 'OK'
                        ],
                        'results' => $dbData
                    ]
                ];

                return response()->json($result, 200);
            }

            // If no data in DB, fallback to RajaOngkir API
            $response = Http::withHeaders([
                'key' => $this->rajaongkir_key
            ])->get($this->rajaongkir_url . '/city', $request->all());

            $data = $response->json();
            $status_code = $response->status();

            // Log API request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/city',
                'source'        => 'api',
                'status_code'   => $status_code,
                'success'       => $response->successful(),
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => $request->all(),
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
            ]);

            return response()->json($data, $status_code);

        } catch (\Exception $e) {
            // Log error
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/city',
                'source'        => 'api',
                'status_code'   => 500,
                'success'       => false,
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => $request->all(),
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
            ]);

            return response()->json([
                'rajaongkir' => [
                    'status' => [
                        'code' => 500,
                        'description' => 'API Error: ' . $e->getMessage()
                    ]
                ]
            ], 500);
        }
    }
}