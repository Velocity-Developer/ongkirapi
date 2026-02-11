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
        $this->rajaongkir_key = env('RAJAONGKIR_KEY');
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
     * Display subdistricts by district ID.
     */
    public function show($id)
    {
        $start = microtime(true);
        $district_id = $id;

        try {
            // 1. DB Check
            $dbData = RajaongkirSubDistrict::select('id', 'name', 'district_id', 'zip_code')
                ->where('district_id', $district_id)
                ->get();

            if ($dbData->isNotEmpty()) {
                $this->logRequest("/v3/destination/subdistrict/$district_id", 'db', 200, true, $start, ['district_id' => $district_id]);
                return response()->json([
                    'meta' => ['message' => 'Success Get Subdistrict By District ID', 'code' => 200, 'status' => 'success'],
                    'data' => $dbData
                ], 200);
            }

            // 2. API Fallback (Warning: RajaOngkir doesn't have a direct "Subdistrict by District" endpoint that returns Kelurahan)
            // But we follow V2 pattern which tries to fetch something or just returns error/empty if not found.
            // Wait, V2 SubdistrictController::show($district_id) does:
            // $response = ...->get(..., ['city' => $district_id]); -> This is weird in V2, it treats district_id as city param?
            // Actually V2 code: get($this->rajaongkir_url . '/subdistrict', ['city' => $district_id]);
            // This suggests V2 treats the parameter as a City ID to get Districts (Kecamatan), OR it's a variable naming confusion.
            // But based on the user's request, they want V3 to match V2 behavior.

            $response = Http::withHeaders(['key' => $this->rajaongkir_key])
                ->get($this->rajaongkir_url . '/subdistrict', ['city' => $district_id]);

            $apiData = $response->json();
            $this->logRequest("/v3/destination/subdistrict/$district_id", 'api', $response->status(), $response->successful(), $start, ['district_id' => $district_id]);

            if ($response->successful() && isset($apiData['rajaongkir']['results'])) {
                $results = $apiData['rajaongkir']['results'];
                if (isset($results['subdistrict_id'])) {
                    $results = [$results];
                }

                $mapped = array_map(function ($item) {
                    return [
                        'id' => $item['subdistrict_id'],
                        'name' => $item['subdistrict_name'],
                        'district_id' => $item['city_id'],
                        'zip_code' => null
                    ];
                }, $results);

                return response()->json([
                    'meta' => ['message' => 'Success Get Subdistrict By District ID', 'code' => 200, 'status' => 'success'],
                    'data' => $mapped
                ], 200);
            }

            return response()->json($apiData, $response->status());
        } catch (\Exception $e) {
            $this->logRequest("/v3/destination/subdistrict/$district_id", 'error', 500, false, $start, ['district_id' => $district_id], $e->getMessage());
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
