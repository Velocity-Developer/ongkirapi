<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Province;
use App\Models\RajaOngkirProvince;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class ProvinceController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_KEY');
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/starter');
    }

    /**
     * Display a listing of provinces - check DB first, fallback to API
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // First, check database - use RajaOngkir table
            $query = RajaOngkirProvince::select('id as province_id', 'name as province');

            if ($request->id) {
                $query->where('id', $request->id);
            }

            $dbData = $query->get();

            // If data exists in database, return it
            if ($dbData && count($dbData) > 0) {
                // Log database request
                ShippingLog::create([
                    'method'        => 'GET',
                    'endpoint'      => '/v2/province',
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
                        'message' => 'Success Get Province',
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
            ])->get($this->rajaongkir_url . '/province', $request->all());

            $data = $response->json();
            $status_code = $response->status();

            // Log API request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v2/province',
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
                'endpoint'      => '/v2/province',
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
