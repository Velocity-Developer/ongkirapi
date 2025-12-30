<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\City;
use App\Models\Subdistrict;
use App\Services\ShippingService;
use App\Helpers\RajaOngkirHelper;
use Illuminate\Support\Facades\Log;

class CostController extends Controller
{
    protected ShippingService $shipping;

    public function __construct(ShippingService $shipping)
    {
        $this->shipping = $shipping;
    }

    public function index(Request $request)
    {
        // Log::info('Request CostController', [
        //     'origin' => $request->origin,
        //     'originType' => $request->originType,
        //     'destination' => $request->destination,
        //     'destinationType' => $request->destinationType,
        //     'weight' => $request->weight,
        //     'courier' => $request->courier,
        //     'method' => $request->method(),
        //     'uri'    => $request->path(),
        //     'payload' => $request->all(),
        //     'ip'     => $request->ip(),
        // ]);


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

        // 3. Konversi postal code ke subdistric ID
        $origin_subdistric_id = RajaOngkirHelper::getSubDistrictIdByZipCode($origin_details->postal_code);
        $destination_subdistric_id = RajaOngkirHelper::getSubDistrictIdByZipCode($destination_details->postal_code);

        // 4. Hit service untuk ambil ongkir
        $shippingResult = $this->shipping->getCost([
            'origin'      => $origin_subdistric_id ?: $origin_details->postal_code,
            'destination' => $destination_subdistric_id ?: $destination_details->postal_code,
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
