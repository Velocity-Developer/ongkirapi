<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RajaOngkirCity;
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
            // First, check database - use RajaOngkir table
            $query = RajaOngkirCity::select('id', 'name', 'province_id');
            
            if ($request->id) {
                $query->where('id', $request->id);
            }
            
            if ($request->province_id) {
                $query->where('province_id', $request->province_id);
            } elseif ($request->province) {
                 // Support legacy parameter name if needed, or strictly use clean schema?
                 // Let's support both for convenience but map to province_id
                $query->where('province_id', $request->province);
            }
            
            $dbData = $query->get();
            
            // If data exists in database, return it
            if ($dbData && count($dbData) > 0) {
                // Log database request
                ShippingLog::create([
                    'method'        => 'GET',
                    'endpoint'      => '/v3/city',
                    'source'        => 'db',
                    'status_code'   => 200,
                    'success'       => true,
                    'duration_ms'   => round((microtime(true) - $start) * 1000),
                    'payload'       => $request->all(),
                    'ip_address'    => request()->ip(),
                    'user_agent'    => request()->header('User-Agent'),
                ]);

                $result = [
                    'meta' => [
                        'message' => 'Success Get City',
                        'code' => 200,
                        'status' => 'success'
                    ],
                    'data' => $dbData
                ];

                return response()->json($result, 200);
            }

            // If no data in DB, fallback to RajaOngkir API
            $response = Http::withHeaders([
                'key' => $this->rajaongkir_key
            ])->get($this->rajaongkir_url . '/city', $request->all());

            $data = $response->json();
            $status_code = $response->status();

            // Transform API response to clean schema
            if (isset($data['rajaongkir']['results'])) {
                $results = $data['rajaongkir']['results'];
                
                if (isset($results['city_id'])) {
                    // Single result
                    $mapped = [
                        'id' => $results['city_id'],
                        'name' => $results['city_name'],
                        'province_id' => $results['province_id']
                    ];
                    $data['rajaongkir']['results'] = $mapped;
                } else {
                    // List results
                    $mapped = array_map(function($item) {
                        return [
                            'id' => $item['city_id'],
                            'name' => $item['city_name'],
                            'province_id' => $item['province_id']
                        ];
                    }, $results);
                    $data['rajaongkir']['results'] = $mapped;
                }
            }

            // Log API request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v3/city',
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
                'endpoint'      => '/v3/city',
                'source'        => 'api',
                'status_code'   => 500,
                'success'       => false,
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => $request->all(),
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
                'error_message' => $e->getMessage()
            ]);

            return response()->json([
                'meta' => [
                    'message' => 'Internal Server Error',
                    'code' => 500,
                    'status' => 'error'
                ]
            ], 500);
        }
    }
}
