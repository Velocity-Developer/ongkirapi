<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RajaongkirSubDistrict;
use App\Models\ShippingLog;

class SubdistrictController extends Controller
{
    /**
     * Display a listing of subdistricts (Kelurahan) - DB Only
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            $query = RajaongkirSubDistrict::select('id', 'name', 'district_id', 'zip_code');

            if ($request->id) {
                $query->where('id', $request->id);
            }

            if ($request->district_id) {
                $query->where('district_id', $request->district_id);
            } elseif ($request->district) {
                $query->where('district_id', $request->district);
            }

            $dbData = $query->get();

            // Log database request
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v3/destination/subdistrict',
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
                    'message' => 'Success Get Subdistrict',
                    'code' => 200,
                    'status' => 'success'
                ],
                'data' => $dbData
            ];

            return response()->json($result, 200);
        } catch (\Exception $e) {
            // Log error
            ShippingLog::create([
                'method'        => 'GET',
                'endpoint'      => '/v3/destination/subdistrict',
                'source'        => 'db',
                'status_code'   => 500,
                'success'       => false,
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => $request->all(),
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->header('User-Agent'),
                'error_message' => $e->getMessage()
            ]);

            return response()->json([
                'meta' => [
                    'message' => 'Internal Server Error',
                    'code' => 500,
                    'status' => 'error'
                ]
            ], 500);
        }
    }
}
