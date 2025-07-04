<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\City;
use App\Models\Subdistrict;
use App\Services\ShippingService;

class CostController extends Controller
{
    protected ShippingService $shipping;

    public function __construct(ShippingService $shipping)
    {
        $this->shipping = $shipping;
    }

    public function index(Request $request)
    {
        // 1. Validasi request
        $validator = Validator::make($request->all(), [
            'origin'            => ['required'],
            'originType'        => ['required', 'in:city,subdistrict'],
            'destination'       => ['required'],
            'destinationType'   => ['required', 'in:city,subdistrict'],
            'weight'            => ['required', 'numeric'],
            'courier'           => ['required'],
            'length'            => ['nullable', 'numeric'],
            'width'             => ['nullable', 'numeric'],
            'height'            => ['nullable', 'numeric'],
            'diameter'          => ['nullable', 'numeric'],
        ]);

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

        // 2. Ambil detail origin/destination
        $origin_details = $request->originType === 'city'
            ? City::where('city_id', $request->origin)->first()
            : Subdistrict::where('subdistrict_id', $request->origin)->first();

        $destination_details = $request->destinationType === 'city'
            ? City::where('city_id', $request->destination)->first()
            : Subdistrict::where('subdistrict_id', $request->destination)->first();

        if (!$origin_details || !$destination_details) {
            return response()->json([
                'rajaongkir' => [
                    'query' => $request->all(),
                    'status' => [
                        'code' => 404,
                        'description' => 'Origin or Destination not found.',
                    ],
                ]
            ], 404);
        }

        // 3. Hit service untuk ambil ongkir
        $shippingResult = $this->shipping->getCost([
            'origin'      => $origin_details->postal_code,
            'destination' => $destination_details->postal_code,
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

        // 4. Format response seperti RajaOngkir
        $formatted = [];
        foreach ($shippingResult['data'] as $courierData) {
            $code = $courierData['code'];

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
                        'value' => $courierData['cost'],
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
