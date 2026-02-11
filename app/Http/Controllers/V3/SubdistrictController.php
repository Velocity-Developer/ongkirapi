<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RajaongkirSubDistrict;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class SubdistrictController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        // Default to PRO because subdistrict endpoint usually requires PRO/Basic
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/pro');
    }

    /**
     * Display a listing of subdistricts (Kelurahan).
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // 1. DB Check
            $query = RajaongkirSubDistrict::select('id', 'name', 'district_id', 'zip_code');
            if ($request->id) {
                $query->where('id', $request->id);
            }
            if ($request->district_id) {
                $query->where('district_id', $request->district_id);
            }
            $dbData = $query->get();

            if ($dbData->isNotEmpty()) {
                $this->logRequest('/v3/destination/subdistrict', 'db', 200, true, $start, $request->all());
                return $this->successResponse($dbData);
            }

            // 2. API Fallback
            // Note: RajaOngkir /subdistrict endpoint returns Kecamatan, not Kelurahan.
            // But we follow V2 reference which falls back to this endpoint.
            $apiParams = [];
            if ($request->id) $apiParams['id'] = $request->id;
            if ($request->district_id) $apiParams['city'] = $request->district_id; // Mapping district_id to city? V2 did this.

            $response = Http::withHeaders(['key' => $this->rajaongkir_key])
                ->get($this->rajaongkir_url . '/subdistrict', $apiParams);

            $data = $response->json();
            $this->logRequest('/v3/destination/subdistrict', 'api', $response->status(), $response->successful(), $start, $request->all());

            if ($response->successful() && isset($data['rajaongkir']['results'])) {
                $results = $data['rajaongkir']['results'];
                // Normalize single/list response
                if (isset($results['subdistrict_id'])) {
                    $results = [$results];
                }

                $mapped = array_map(function ($item) {
                    return [
                        'id' => $item['subdistrict_id'],
                        'name' => $item['subdistrict_name'],
                        'district_id' => $item['city_id'], // This is likely wrong semantically (city_id vs district_id) but matches API structure
                        'zip_code' => null // API /subdistrict (Kecamatan) doesn't have zip code
                    ];
                }, $results);

                return $this->successResponse($mapped);
            }

            return response()->json($data, $response->status());
        } catch (\Exception $e) {
            $this->logRequest('/v3/destination/subdistrict', 'error', 500, false, $start, $request->all(), $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $start = microtime(true);

        try {
            // 1. DB Check
            $data = RajaongkirSubDistrict::select('id', 'name', 'district_id', 'zip_code')->where('id', $id)->first();

            if ($data) {
                $this->logRequest("/v3/destination/subdistrict/$id", 'db', 200, true, $start, ['id' => $id]);
                return $this->successResponse($data);
            }

            // 2. API Fallback
            $response = Http::withHeaders(['key' => $this->rajaongkir_key])
                ->get($this->rajaongkir_url . '/subdistrict', ['id' => $id]);

            $apiData = $response->json();
            $this->logRequest("/v3/destination/subdistrict/$id", 'api', $response->status(), $response->successful(), $start, ['id' => $id]);

            if ($response->successful() && isset($apiData['rajaongkir']['results'])) {
                $item = $apiData['rajaongkir']['results'];
                $mapped = [
                    'id' => $item['subdistrict_id'],
                    'name' => $item['subdistrict_name'],
                    'district_id' => $item['city_id'],
                    'zip_code' => null
                ];
                return $this->successResponse($mapped);
            }

            return response()->json($apiData, $response->status());
        } catch (\Exception $e) {
            $this->logRequest("/v3/destination/subdistrict/$id", 'error', 500, false, $start, ['id' => $id], $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    private function logRequest($endpoint, $source, $statusCode, $success, $startTime, $payload, $error = null)
    {
        ShippingLog::create([
            'method' => 'GET',
            'endpoint' => $endpoint,
            'source' => $source,
            'status_code' => $statusCode,
            'success' => $success,
            'duration_ms' => round((microtime(true) - $startTime) * 1000),
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'error_message' => $error
        ]);
    }

    private function successResponse($data)
    {
        return response()->json([
            'meta' => ['message' => 'Success Get Subdistrict', 'code' => 200, 'status' => 'success'],
            'data' => $data
        ], 200);
    }

    private function errorResponse($message)
    {
        return response()->json([
            'meta' => ['message' => 'Internal Server Error', 'code' => 500, 'status' => 'error', 'error' => $message]
        ], 500);
    }
}
