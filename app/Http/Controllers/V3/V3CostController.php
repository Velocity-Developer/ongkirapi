<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\RajaOngkirDistrict;
use App\Models\ShippingLog;

class V3CostController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_KEY');
        // Default to Komerce/V1 endpoint from .env or fallback
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://rajaongkir.komerce.id/api/v1');
    }

    public function index(Request $request)
    {
        $start = microtime(true);

        // 1. Validation
        $validator = Validator::make($request->all(), [
            'origin'            => ['required', 'numeric'],
            'destination'       => ['required', 'numeric'],
            'weight'            => ['required', 'numeric'],
            'courier'           => ['required', 'string'],
            'price'             => ['nullable', 'in:lowest,highest'], // For sorting
        ]);

        if ($validator->fails()) {
            return response()->json([
                'meta' => [
                    'message' => 'Validation Error',
                    'code' => 400,
                    'status' => 'error'
                ],
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // 2. Prepare params for RajaOngkir (Komerce Endpoint)
            // Endpoint: /calculate/district/domestic-cost
            // Params: origin, destination, weight, courier, price

            $params = [
                'origin' => $request->origin,
                'destination' => $request->destination,
                'weight' => $request->weight,
                'courier' => $request->courier,
                'price' => $request->price ?? 'lowest'
            ];

            // 3. Call RajaOngkir API
            $response = Http::withHeaders([
                'key' => $this->rajaongkir_key,
                'content-type' => 'application/x-www-form-urlencoded'
            ])->asForm()->post($this->rajaongkir_url . '/calculate/district/domestic-cost', $params);

            $apiData = $response->json();
            $statusCode = $response->status();

            // 4. Log Request
            ShippingLog::create([
                'method' => 'POST',
                'endpoint' => '/v3/calculate/domestic-cost',
                'source' => 'api',
                'status_code' => $statusCode,
                'success' => $response->successful(),
                'duration_ms' => round((microtime(true) - $start) * 1000),
                'payload' => $request->all(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'error_message' => $response->failed() ? json_encode($apiData) : null
            ]);

            // 5. Process Response
            // Komerce API structure might be different from official RajaOngkir
            // Based on V2 Service: $existing->cost_services->toArray()
            // It seems Komerce returns a list of services directly in 'data' field or similar?
            // Let's assume standard RajaOngkir structure first, but if it fails, we dump the raw data.
            // Wait, V2 code just returns $existing->cost_services or API response.

            if ($response->successful()) {
                // Check if data is directly in 'data' key (common in modern APIs)
                $responseData = $apiData['data'] ?? $apiData;

                // If the response is already a list of costs (Komerce style), we might need to normalize it
                // Let's assume it returns a list of services similar to what we want.

                return response()->json([
                    'meta' => [
                        'message' => 'Success Calculate Cost',
                        'code' => 200,
                        'status' => 'success'
                    ],
                    'data' => $responseData
                ], 200);
            }

            // Error from API
            return response()->json([
                'meta' => [
                    'message' => 'API Error',
                    'code' => $statusCode,
                    'status' => 'error'
                ],
                'data' => $apiData
            ], $statusCode);
        } catch (\Exception $e) {
            ShippingLog::create([
                'method' => 'POST',
                'endpoint' => '/v3/calculate/domestic-cost',
                'source' => 'error',
                'status_code' => 500,
                'success' => false,
                'duration_ms' => round((microtime(true) - $start) * 1000),
                'payload' => $request->all(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'error_message' => $e->getMessage()
            ]);

            return response()->json([
                'meta' => [
                    'message' => 'Internal Server Error',
                    'code' => 500,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
