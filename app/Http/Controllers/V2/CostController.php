<?php

namespace App\Http\Controllers\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Controller;
use App\Models\RajaOngkirDistrict;
use App\Services\ShippingServiceV2;

class CostController extends Controller
{
    protected ShippingServiceV2 $shipping;

    public function __construct(ShippingServiceV2 $shipping)
    {
        $this->shipping = $shipping;
    }

    public function index(Request $request)
    {
        // 1. Validasi request - V2 hanya terima district ID
        $validator = Validator::make($request->all(), [
            'origin'            => ['required', 'numeric'],
            'destination'       => ['required', 'numeric'],
            'weight'            => ['required', 'numeric'],
            'courier'           => ['required'],
            'length'            => ['nullable', 'numeric'],
            'width'             => ['nullable', 'numeric'],
            'height'            => ['nullable', 'numeric'],
            'diameter'          => ['nullable', 'numeric'],
        ]);

        //weight
        $weight = $request->weight ?? 1000;

        //pembulatan 
        $weight_fix = ceil($weight / 1000) * 1000;
        $weight_fix = $weight_fix / 1000;

        if ($validator->fails()) {
            return response()->json([
                'rajaongkir' => [
                    'query' => $request->all(),
                    'status' => [
                        'code' => 400,
                        'description' => $validator->errors(),
                    ],
                ]
            ], 400);
        }

        // 2. Ambil detail origin/destination dari RajaOngkirDistrict
        $origin_details = RajaOngkirDistrict::where('id', $request->origin)->first();
        $destination_details = RajaOngkirDistrict::where('id', $request->destination)->first();

        if (!$origin_details || !$destination_details) {
            return response()->json([
                'rajaongkir' => [
                    'query' => $request->all(),
                    'status' => [
                        'code' => 404,
                        'description' => 'Origin or Destination district not found.',
                    ],
                ]
            ], 404);
        }

        // 3. V2: Langsung gunakan district ID tanpa konversi postal code
        // Tidak perlu konversi postal code ke district ID seperti V1
        
        // 4. Hit service untuk ambil ongkir
        $shippingResult = $this->shipping->getCost([
            'origin'      => $request->origin, // Langsung gunakan district ID
            'destination' => $request->destination, // Langsung gunakan district ID
            'weight'      => $request->weight,
            'courier'     => $request->courier,
            'length'      => $request->length,
            'width'       => $request->width,
            'height'      => $request->height,
            'diameter'    => $request->diameter,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->header('User-Agent'),
        ]);

        if ($shippingResult['error']) {
            return response()->json([
                'rajaongkir' => [
                    'query' => $request->all(),
                    'origin_details' => $origin_details,
                    'destination_details' => $destination_details,
                    'status' => [
                        'code' => $shippingResult['status'],
                        'description' => $shippingResult['message'] ?? 'Unknown error',
                    ]
                ]
            ], $shippingResult['status']);
        }

        // 5. Format response seperti RajaOngkir (sama dengan V1)
        $formatted = [];
        foreach ($shippingResult['data'] as $courierData) {
            $code = $courierData['code'];
            $cost = $courierData['cost'];

            // Jika belum ada, inisialisasi dulu
            if (!isset($formatted[$code])) {
                $formatted[$code] = [
                    'code' => $code,
                    'name' => $courierData['name'],
                    'costs' => [],
                ];
            }

            // Tambahkan service ke dalam daftar costs
            $formatted[$code]['costs'][] = [
                'service' => $courierData['service'],
                'description' => $courierData['description'],
                'cost' => [
                    [
                        'value' => $cost * $weight_fix,
                        'etd' => $courierData['etd'] ?? '',
                        'note' => '',
                    ]
                ]
            ];
        }

        $formatted = array_values($formatted);

        return response()->json([
            'rajaongkir' => [
                'query' => $request->all(),
                'origin_details' => $origin_details,
                'destination_details' => $destination_details,
                'status' => [
                    'code' => 200,
                    'description' => 'OK',
                ],
                'results' => $formatted
            ]
        ]);
    }
}