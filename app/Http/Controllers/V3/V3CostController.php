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
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        // Default to PRO endpoint
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/pro');
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
            // 2. Prepare params for RajaOngkir
            // Note: RajaOngkir /cost endpoint expects 'originType' and 'destinationType' if using subdistrict/city
            // But user example shows 'origin' and 'destination' ID directly.
            // Assuming IDs are 'subdistrict' (Kecamatan) IDs because V3 DistrictController returns Kecamatan.
            // However, RajaOngkir /cost usually takes city ID or subdistrict ID depending on account type.
            // PRO account: originType=subdistrict, destinationType=subdistrict by default or specified.

            // User example: https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost (This is a 3rd party wrapper likely)
            // But we are building V3 on top of official RajaOngkir API.
            // Let's assume standard RajaOngkir /cost behavior but with clean V3 response.

            $params = [
                'origin' => $request->origin,
                'originType' => 'subdistrict', // Defaulting to subdistrict (Kecamatan) as per V3 structure
                'destination' => $request->destination,
                'destinationType' => 'subdistrict',
                'weight' => $request->weight,
                'courier' => $request->courier,
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
            if ($response->successful() && isset($apiData['rajaongkir']['results'])) {
                $results = $apiData['rajaongkir']['results'];
                $costs = [];

                // Flatten the results
                foreach ($results as $courierResult) {
                    $courierCode = $courierResult['code'];
                    $courierName = $courierResult['name'];

                    foreach ($courierResult['costs'] as $costItem) {
                        $costs[] = [
                            'courier_code' => $courierCode,
                            'courier_name' => $courierName,
                            'service' => $costItem['service'],
                            'description' => $costItem['description'],
                            'cost' => $costItem['cost'][0]['value'],
                            'etd' => $costItem['cost'][0]['etd'],
                            'note' => $costItem['cost'][0]['note']
                        ];
                    }
                }

                // 6. Sort if requested
                if ($request->has('price')) {
                    $sortOrder = $request->price === 'lowest' ? SORT_ASC : SORT_DESC;
                    array_multisort(array_column($costs, 'cost'), $sortOrder, $costs);
                }

                return response()->json([
                    'meta' => [
                        'message' => 'Success Calculate Cost',
                        'code' => 200,
                        'status' => 'success'
                    ],
                    'data' => $costs
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
