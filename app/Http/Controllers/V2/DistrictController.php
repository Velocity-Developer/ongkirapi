<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subdistrict;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class DistrictController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/pro');
    }

    /**
     * Display a listing of districts - check DB first, fallback to API
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // First, check database
            $query = Subdistrict::select('subdistrict_id', 'subdistrict_name', 'city_id', 'type', 'city', 'province_id', 'province');
            
            if ($request->id) {
                $query->where('subdistrict_id', $request->id);
            }
            
            if ($request->city) {
                $query->where('city_id', $request->city);
            }
            
            $dbData = $query->get();
            
            // If data exists in database, return it as district format
            if ($dbData && count($dbData) > 0) {
                // Transform to district format
                $districts = $dbData->map(function($item) {
                    return [
                        'district_id' => $item->subdistrict_id,
                        'district_name' => $item->subdistrict_name,
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
                    'rajaongkir' => [
                        'query' => $request->all(),
                        'status' => [
                            'code' => 200,
                            'description' => 'OK'
                        ],
                        'results' => $districts
                    ]
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
                $districts = array_map(function($item) {
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
}