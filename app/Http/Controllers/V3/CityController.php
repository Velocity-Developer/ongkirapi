<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RajaOngkirCity;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class CityController extends Controller
{
    private $rajaongkir_key;
    private $rajaongkir_url;

    public function __construct()
    {
        $this->rajaongkir_key = env('RAJAONGKIR_API_KEY');
        $this->rajaongkir_url = env('RAJAONGKIR_API_URL', 'https://api.rajaongkir.com/starter');
    }

    /**
     * Display a listing of cities.
     */
    public function index(Request $request)
    {
        $start = microtime(true);

        try {
            // 1. DB Check
            $query = RajaOngkirCity::select('id', 'name', 'province_id');
            if ($request->id) {
                $query->where('id', $request->id);
            }
            if ($request->province_id) {
                $query->where('province_id', $request->province_id);
            }
            $dbData = $query->get();

            if ($dbData->isNotEmpty()) {
                $this->logRequest('/v3/destination/city', 'db', 200, true, $start, $request->all());
                return $this->successResponse($dbData);
            }

            // 2. API Fallback
            // RajaOngkir API /city supports 'id' and 'province' (province_id) parameters
            $apiParams = [];
            if ($request->id) $apiParams['id'] = $request->id;
            if ($request->province_id) $apiParams['province'] = $request->province_id;

            $response = Http::withHeaders(['key' => $this->rajaongkir_key])
                ->get($this->rajaongkir_url . '/city', $apiParams);

            $data = $response->json();
            $this->logRequest('/v3/destination/city', 'api', $response->status(), $response->successful(), $start, $request->all());

            if ($response->successful() && isset($data['rajaongkir']['results'])) {
                $results = $data['rajaongkir']['results'];
                // Normalize single/list response
                if (isset($results['city_id'])) {
                    $results = [$results];
                }

                $mapped = array_map(function ($item) {
                    return [
                        'id' => $item['city_id'],
                        'name' => $item['type'] . ' ' . $item['city_name'], // e.g. "Kota Banda Aceh"
                        'province_id' => $item['province_id']
                    ];
                }, $results);

                return $this->successResponse($mapped);
            }

            return response()->json($data, $response->status());
        } catch (\Exception $e) {
            $this->logRequest('/v3/destination/city', 'error', 500, false, $start, $request->all(), $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Display cities by province ID.
     */
    public function show($id)
    {
        $start = microtime(true);
        $province_id = $id;

        try {
            // 1. DB Check
            $dbData = RajaOngkirCity::select('id', 'name', 'province_id')
                ->where('province_id', $province_id)
                ->get();

            if ($dbData->isNotEmpty()) {
                $this->logRequest("/v3/destination/city/$province_id", 'db', 200, true, $start, ['province_id' => $province_id]);
                return response()->json([
                    'meta' => ['message' => 'Success Get City By Province ID', 'code' => 200, 'status' => 'success'],
                    'data' => $dbData
                ], 200);
            }

            // 2. API Fallback
            $response = Http::withHeaders(['key' => $this->rajaongkir_key])
                ->get($this->rajaongkir_url . '/city', ['province' => $province_id]);

            $apiData = $response->json();
            $this->logRequest("/v3/destination/city/$province_id", 'api', $response->status(), $response->successful(), $start, ['province_id' => $province_id]);

            if ($response->successful() && isset($apiData['rajaongkir']['results'])) {
                $results = $apiData['rajaongkir']['results'];
                // Normalize single/list response (though /city?province=... usually returns list)
                if (isset($results['city_id'])) {
                    $results = [$results];
                }

                $mapped = array_map(function ($item) {
                    return [
                        'id' => $item['city_id'],
                        'name' => $item['type'] . ' ' . $item['city_name'],
                        'province_id' => $item['province_id']
                    ];
                }, $results);

                return response()->json([
                    'meta' => ['message' => 'Success Get City By Province ID', 'code' => 200, 'status' => 'success'],
                    'data' => $mapped
                ], 200);
            }

            return response()->json($apiData, $response->status());
        } catch (\Exception $e) {
            $this->logRequest("/v3/destination/city/$province_id", 'error', 500, false, $start, ['province_id' => $province_id], $e->getMessage());
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
            'meta' => ['message' => 'Success Get City', 'code' => 200, 'status' => 'success'],
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
