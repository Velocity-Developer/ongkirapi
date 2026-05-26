<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subdistrict;
use App\Models\RajaOngkirDistrict;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class DistrictController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_KEY');
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/pro');
    }

    /**
     * Display a listing of districts - check DB first, fallback to API
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // First, check database - use RajaOngkir table
            $query = RajaOngkirDistrict::select('id as district_id', 'name as district_name', 'city_id');

            if ($request->id) {
                $query->where('id', $request->id);
            }

            if ($request->city) {
                $query->where('city_id', $request->city);
            }

            $dbData = $query->get();

            // If data exists in database, return it as district format
            if ($dbData && count($dbData) > 0) {
                // Transform to district format
                $districts = $dbData->map(function ($item) {
                    return [
                        'district_id' => $item->district_id,
                        'district_name' => $item->district_name,
                        'city_id' => $item->city_id,
                        'city' => $item->city,
                        'type' => $item->type ?? '',
                        'province_id' => $item->province_id,
                        'province' => $item->province
                    ];
                });

                // Log database request
                ShippingLog::create([
                    'method'        => 'GET',
                    'endpoint'      => '/v2/district',
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
                    'data' => $districts
                ];

                return response()->json($result, 200);
            }

            // If no data in DB, fallback to RajaOngkir API
            $response = Http::withHeaders([
                'key' => $this->rajaongkir_key
            ])->get($this->rajaongkir_url . '/subdistrict', $request->all());

            $data = $response->json();
            $status_code = $response->status();

            // Transform the response to match district format if needed
            if (isset($data['rajaongkir']['results']) && is_array($data['rajaongkir']['results'])) {
                $districts = array_map(function ($item) {
                    return [
                        'district_id' => $item['subdistrict_id'] ?? $item['id'],
                        'district_name' => $item['subdistrict_name'] ?? $item['name'],
                        'city_id' => $item['city_id'],
                        'city' => $item['city'],
                        'type' => $item['type'] ?? '',
                        'province_id' => $item['province_id'],
                        'province' => $item['province']
                    ];
                }, $data['rajaongkir']['results']);

                $data['rajaongkir']['results'] = $districts;
            }

            // Log API request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/district',
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
                        'message' => 'Success Get District',
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
                'endpoint'      => '/v2/district',
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
     * Display districts by city ID
     */
    public function show($city_id)
    {
        $start = microtime(true);

        try {
            // First, check database - use RajaOngkir table
            $query = RajaOngkirDistrict::select('id as district_id', 'name as district_name', 'city_id')
                ->where('city_id', $city_id);

            $dbData = $query->get();

            // If data exists in database, return it as district format
            if ($dbData && count($dbData) > 0) {
                // Transform to district format
                $districts = $dbData->map(function ($item) {
                    return [
                        'district_id' => $item->district_id,
                        'district_name' => $item->district_name,
                        'city_id' => $item->city_id,
                        'city' => $item->city,
                        'type' => $item->type ?? '',
                        'province_id' => $item->province_id,
                        'province' => $item->province
                    ];
                });

                // Log database request
                ShippingLog::create([
                    'method'        => 'GET',
                    'endpoint'      => '/v2/district/' . $city_id,
                    'source'        => 'db',
                    'status_code'   => 200,
                    'success'       => true,
                    'duration_ms'   => round((microtime(true) - $start) * 1000),
                    'payload'       => ['city_id' => $city_id],
                    'ip_address'    => request()->ip(),
                    'user_agent'    => request()->header('User-Agent'),
                ]);

                $result = [
                    'meta' => [
                        'message' => 'Success Get District',
                        'code' => 200,
                        'status' => 'success'
                    ],
                    'data' => $districts
                ];

                return response()->json($result, 200);
            }

            // If no data in DB, fallback to RajaOngkir API
            $response = Http::withHeaders([
                'key' => $this->rajaongkir_key
            ])->get($this->rajaongkir_url . '/subdistrict', ['city' => $city_id]);

            $data = $response->json();
            $status_code = $response->status();

            // Transform the response to match district format if needed
            if (isset($data['rajaongkir']['results']) && is_array($data['rajaongkir']['results'])) {
                $districts = array_map(function ($item) {
                    return [
                        'district_id' => $item['subdistrict_id'] ?? $item['id'],
                        'district_name' => $item['subdistrict_name'] ?? $item['name'],
                        'city_id' => $item['city_id'],
                        'city' => $item['city'],
                        'type' => $item['type'] ?? '',
                        'province_id' => $item['province_id'],
                        'province' => $item['province']
                    ];
                }, $data['rajaongkir']['results']);

                $data['rajaongkir']['results'] = $districts;
            }

            // Log API request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/district/' . $city_id,
                'source'        => 'api',
                'status_code'   => $status_code,
                'success'       => $response->successful(),
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => ['city_id' => $city_id],
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
            ]);

            // Transform API response to match our format
            if ($response->successful() && isset($data['rajaongkir']['results'])) {
                $result = [
                    'meta' => [
                        'message' => 'Success Get District',
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
                'endpoint'      => '/v2/district/' . $city_id,
                'source'        => 'api',
                'status_code'   => 500,
                'success'       => false,
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => ['city_id' => $city_id],
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
