<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\RajaOngkirDistrict;
use App\Models\ShippingLog;

class CostController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://rajaongkir.komerce.id/api/v1/calculate/district/domestic-cost');
    }

    public function index(Request $request)
    {
        $start = microtime(true);

        // Validate request for district-to-district calculation
        $validator = Validator::make($request->all(), [
            'origin'            => ['required', 'numeric'],
            'destination'       => ['required', 'numeric'],
            'weight'            => ['required', 'numeric'],
            'courier'           => ['required', 'string'],
            'length'            => ['nullable', 'numeric'],
            'width'             => ['nullable', 'numeric'],
            'height'            => ['nullable', 'numeric'],
            'diameter'          => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => $request->all(),
                'meta' => [
                    'code' => 400,
                    'description' => $validator->errors(),
                ],

            ], 400);
        }

        try {
            // Verify origin and destination district exist in our database
            $originDistrict = RajaOngkirDistrict::where('id', $request->origin)->first();
            $destinationDistrict = RajaOngkirDistrict::where('id', $request->destination)->first();

            if (!$originDistrict || !$destinationDistrict) {
                return response()->json([
                    'data' => $request->all(),
                    'meta' => [
                        'code' => 404,
                        'description' => 'Origin or destination district not found in database. Please use valid district IDs.',
                    ],

                ], 404);
            }

            // Prepare request parameters for RajaOngkir API
            $params = [
                'origin' => $request->origin,
                'originType' => 'subdistrict',
                'destination' => $request->destination,
                'destinationType' => 'subdistrict',
                'weight' => $request->weight,
                'courier' => $request->courier,
            ];

            // Add optional dimensional parameters if provided
            if ($request->length) $params['length'] = $request->length;
            if ($request->width) $params['width'] = $request->width;
            if ($request->height) $params['height'] = $request->height;
            if ($request->diameter) $params['diameter'] = $request->diameter;

            // Make request to RajaOngkir API
            $response = Http::withHeaders([
                'key' => $this->rajaongkir_key
            ])->post($this->rajaongkir_url . '/cost', $params);

            $data = $response->json();
            $status_code = $response->status();

            // Log API request
            ShippingLog::create([
                'method'        => 'POST',
                'endpoint'      => '/v2/cost',
                'source'        => 'api',
                'status_code'   => $status_code,
                'success'       => $response->successful(),
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => $request->all(),
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
            ]);

            if ($response->successful()) {
                $data = $response->successful();
                return response()->json($data, $status_code);
            }

            // Return error response from RajaOngkir API
            return response()->json($data, $status_code);
        } catch (\Exception $e) {
            // Log error
            ShippingLog::create([
                'method'        => 'POST',
                'endpoint'      => '/v2/cost',
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
                    'query' => $request->all(),
                    'status' => [
                        'code' => 500,
                        'description' => 'API Error: ' . $e->getMessage()
                    ]
                ]
            ], 500);
        }
    }
}
