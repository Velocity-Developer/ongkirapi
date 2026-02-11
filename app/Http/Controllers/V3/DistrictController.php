<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RajaOngkirDistrict;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class DistrictController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        // Default to PRO because subdistrict endpoint usually requires PRO/Basic, not Starter
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/pro');
    }

    /**
     * Display a listing of districts (Kecamatan) - check DB first, fallback to API
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // First, check database - use RajaOngkir table
            $query = RajaOngkirDistrict::select('id', 'name', 'city_id');
            
            if ($request->id) {
                $query->where('id', $request->id);
            }
            
            if ($request->city_id) {
                $query->where('city_id', $request->city_id);
            } elseif ($request->city) {
                $query->where('city_id', $request->city);
            }
            
            $dbData = $query->get();
            
            // If data exists in database, return it
            if ($dbData && count($dbData) > 0) {
                // Log database request
                ShippingLog::create([
                    'method'        => 'GET',
                    'endpoint'      => '/v3/district',
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
                        'message' => 'Success Get District',
                        'code' => 200,
                        'status' => 'success'
                    ],
                    'data' => $dbData
                ];

                return response()->json($result, 200);
            }

            // If no data in DB, fallback to RajaOngkir API
            // Note: RajaOngkir endpoint for Kecamatan is '/subdistrict'
            $response = Http::withHeaders([
                'key' => $this->rajaongkir_key
            ])->get($this->rajaongkir_url . '/subdistrict', $request->all());

            $data = $response->json();
            $status_code = $response->status();

            // Transform API response to clean schema
            if (isset($data['rajaongkir']['results'])) {
                $results = $data['rajaongkir']['results'];
                
                if (isset($results['subdistrict_id'])) {
                    // Single result
                    $mapped = [
                        'id' => $results['subdistrict_id'],
                        'name' => $results['subdistrict_name'],
                        'city_id' => $results['city_id']
                    ];
                    $data['rajaongkir']['results'] = $mapped;
                } else {
                    // List results
                    $mapped = array_map(function($item) {
                        return [
                            'id' => $item['subdistrict_id'],
                            'name' => $item['subdistrict_name'],
                            'city_id' => $item['city_id']
                        ];
                    }, $results);
                    $data['rajaongkir']['results'] = $mapped;
                }
            }

            // Log API request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v3/district',
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
                'endpoint'      => '/v3/district',
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
