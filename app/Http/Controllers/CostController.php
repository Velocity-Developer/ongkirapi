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

        $status_code = 200;
        $status_description = 'OK';

        $validator = Validator::make($request->all(), [
            'origin'            => ['required'],
            'originType'        => ['required', 'in:city,subdistrict'],
            'destination'       => ['required'],
            'destinationType'   => ['required', 'in:city,subdistrict'],
            'weight'            => ['required'],
            'courier'           => ['required'],
            'length'            => ['nullable', 'numeric'],
            'width'             => ['nullable', 'numeric'],
            'height'            => ['nullable', 'numeric'],
            'diameter'          => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            $status_code = 400;
            $status_description = $validator->errors();
        }

        $origin_details = [];
        if ($request->originType == 'city') {
            $origin_details = City::where('city_id', $request->origin)->first();
        } else {
            $origin_details = Subdistrict::where('subdistrict_id', $request->origin)->first();
        }

        $destination_details = [];
        if ($request->destinationType == 'city') {
            $destination_details = City::where('city_id', $request->destination)->first();
        } else {
            $destination_details = Subdistrict::where('subdistrict_id', $request->destination)->first();
        }

        $shippingResult = $this->shipping->getCost([
            'origin'        => $request->origin,
            'destination'   => $request->destination,
            'weight'        => $request->weight,
            'courier'       => $request->courier,
        ]);


        $result['rajaongkir'] = [
            'query'                 => $request->all(),
            'origin_details'        => $origin_details,
            'destination_details'   => $destination_details,
            'status'                => [
                'code'          => $status_code,
                'description'   => $status_description,
            ],
            'results'               => $shippingResult
        ];

        return response()->json($result);
    }
}
