<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RajaOngkirDistrict;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class DistrictController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/starter');
    }

    /**
     * Display a listing of districts (Kecamatan).
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // 1. DB Check
            $query = RajaOngkirDistrict::select('id', 'name', 'city_id');
            if ($request->id) {
                $query->where('id', $request->id);
            }
            if ($request->city_id) {
                $query->where('city_id', $request->city_id);
            }
            $dbData = $query->get();

            if ($dbData->isNotEmpty()) {
                $this->logRequest('/v3/destination/district', 'db', 200, true, $start, $request->all());
                return $this->successResponse($dbData);
            }

            // 2. API Fallback (RajaOngkir endpoint for Kecamatan is /subdistrict)
            $apiParams = [];
            if ($request->id) $apiParams['id'] = $request->id;
            if ($request->city_id) $apiParams['city'] = $request->city_id; // API param is 'city'

            $response = Http::withHeaders(['key' => $this->rajaongkir_key])
                ->get($this->rajaongkir_url . '/subdistrict', $apiParams);

            $data = $response->json();
            $this->logRequest('/v3/destination/district', 'api', $response->status(), $response->successful(), $start, $request->all());

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
                        'city_id' => $item['city_id']
                    ];
                }, $results);

                return $this->successResponse($mapped);
            }

            return response()->json($data, $response->status());
        } catch (\Exception $e) {
            $this->logRequest('/v3/destination/district', 'error', 500, false, $start, $request->all(), $e->getMessage());
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
            $data = RajaOngkirDistrict::select('id', 'name', 'city_id')->where('id', $id)->first();

            if ($data) {
                $this->logRequest("/v3/destination/district/$id", 'db', 200, true, $start, ['id' => $id]);
                return $this->successResponse($data);
            }

            // 2. API Fallback
            $response = Http::withHeaders(['key' => $this->rajaongkir_key])
                ->get($this->rajaongkir_url . '/subdistrict', ['id' => $id]);

            $apiData = $response->json();
            $this->logRequest("/v3/destination/district/$id", 'api', $response->status(), $response->successful(), $start, ['id' => $id]);

            if ($response->successful() && isset($apiData['rajaongkir']['results'])) {
                $item = $apiData['rajaongkir']['results'];
                $mapped = [
                    'id' => $item['subdistrict_id'],
                    'name' => $item['subdistrict_name'],
                    'city_id' => $item['city_id']
                ];
                return $this->successResponse($mapped);
            }

            return response()->json($apiData, $response->status());
        } catch (\Exception $e) {
            $this->logRequest("/v3/destination/district/$id", 'error', 500, false, $start, ['id' => $id], $e->getMessage());
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
            'meta' => ['message' => 'Success Get District', 'code' => 200, 'status' => 'success'],
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
