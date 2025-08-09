<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
     * Display a listing of districts from RajaOngkir API
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // Make request to RajaOngkir API
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

            // Log the request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/district',
                'source'        => 'rajaongkir_api',
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
                'source'        => 'rajaongkir_api',
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