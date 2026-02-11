<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subdistrict;
use App\Models\RajaongkirSubDistrict;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class SubdistrictController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/pro');
    }

    /**
     * Display a listing of subdistricts - check DB first, fallback to API
     */
    public function index(Request $request)
    {
        $start = microtime(true);
        $isDestination = $request->is('*destination*');

        try {
            // First, check database - use RajaOngkir table
            if ($isDestination) {
                $query = RajaOngkirSubDistrict::select('id', 'subdistrict_name', 'city_id', 'type', 'city', 'province_id', 'province');
            } else {
                $query = Subdistrict::select('subdistrict_id', 'subdistrict_name', 'city_id', 'type', 'city', 'province_id', 'province');
            }

            if ($request->id) {
                $query->where('subdistrict_id', $request->id);
            }

            if ($request->city) {
                $query->where('city_id', $request->city);
            }

            $dbData = $query->get();

            // If data exists in database, return it
            if ($dbData && count($dbData) > 0) {
                // Log database request
                ShippingLog::create([
                    'method'        => 'GET',
                    'endpoint'      => $isDestination ? '/v2/destination/subdistrict' : '/v2/subdistrict',
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
                        'message' => 'Success Get Subdistrict',
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
            ])->get($this->rajaongkir_url . '/subdistrict', $request->all());

            $data = $response->json();
            $status_code = $response->status();

            // Log API request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => $isDestination ? '/v2/destination/subdistrict' : '/v2/subdistrict',
                'source'        => 'api',
                'status_code'   => $status_code,
                'success'       => $response->successful(),
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => $request->all(),
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
            ]);

            // Transform API response to match our format
            if ($response->successful() && isset($data['rajaongkir']['results'])) {
                $result = [
                    'meta' => [
                        'message' => 'Success Get Subdistrict',
                        'code' => $status_code,
                        'status' => 'success'
                    ],
                    'data' => $data['rajaongkir']['results']
                ];
                return response()->json($result, $status_code);
            }

            return response()->json($data, $status_code);
        } catch (\Exception $e) {
            // Log error
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/subdistrict',
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

    /**
     * Display subdistricts by district ID
     */
    public function show($district_id)
    {
        $start = microtime(true);

        try {
            // First, check database for subdistricts by district_id using rajaongkir_sub_districts table
            $dbData = RajaongkirSubDistrict::select('id as subdistrict_id', 'name as subdistrict_name', 'zip_code', 'district_id')
                ->where('district_id', $district_id)
                ->get();

            // If data exists in database, return it
            if ($dbData && count($dbData) > 0) {
                // Transform data to standard subdistrict format
                $transformedData = $dbData->map(function ($item) {
                    return [
                        'subdistrict_id' => $item->subdistrict_id,
                        'subdistrict_name' => $item->subdistrict_name,
                        'city_id' => null, // Will be filled from relations if needed
                        'city' => null,
                        'type' => null,
                        'province_id' => null,
                        'province' => null,
                        'postal_code' => $item->zip_code
                    ];
                });

                // Log database request
                ShippingLog::create([
                    'method'        => 'GET',
                    'endpoint'      => '/v2/subdistrict/' . $district_id,
                    'source'        => 'db',
                    'status_code'   => 200,
                    'success'       => true,
                    'duration_ms'   => round((microtime(true) - $start) * 1000),
                    'payload'       => ['district_id' => $district_id],
                    'ip_address'    => request()->ip(),
                    'user_agent'    => request()->header('User-Agent'),
                ]);

                $result = [
                    'meta' => [
                        'message' => 'Success Get Subdistrict',
                        'code' => 200,
                        'status' => 'success'
                    ],
                    'data' => $transformedData
                ];

                return response()->json($result, 200);
            }

            // If no data in DB, fallback to RajaOngkir API
            $response = Http::withHeaders([
                'key' => $this->rajaongkir_key
            ])->get($this->rajaongkir_url . '/subdistrict', ['city' => $district_id]);

            $data = $response->json();
            $status_code = $response->status();

            // Log API request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/subdistrict/' . $district_id,
                'source'        => 'api',
                'status_code'   => $status_code,
                'success'       => $response->successful(),
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => ['district_id' => $district_id],
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
            ]);

            // Transform API response to match our format
            if ($response->successful() && isset($data['rajaongkir']['results'])) {
                $result = [
                    'meta' => [
                        'message' => 'Success Get Subdistrict',
                        'code' => $status_code,
                        'status' => 'success'
                    ],
                    'data' => $data['rajaongkir']['results']
                ];
                return response()->json($result, $status_code);
            }

            return response()->json($data, $status_code);
        } catch (\Exception $e) {
            // Log error
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/subdistrict/' . $district_id,
                'source'        => 'api',
                'status_code'   => 500,
                'success'       => false,
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => ['district_id' => $district_id],
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
            ]);

            return response()->json([
                'meta' => [
                    'message' => 'API Error: ' . $e->getMessage(),
                    'code' => 500,
                    'status' => 'error'
                ],
                'data' => []
            ], 500);
        }
    }
}
