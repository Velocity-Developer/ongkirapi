<?php

namespace App\Services;

use App\Models\Cost;
use App\Models\CostService;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;
use App\Helpers\LogJsonHelper;

class ShippingServiceV2
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.key');
        $this->endpoint = config('services.rajaongkir.base_url');
    }

    public function getCost(array $payload)
    {
        $start = microtime(true);

        $ip = $payload['ip_address'];
        $userAgent = $payload['user_agent'];

        $couriers = $payload['courier'] ? explode(':', $payload['courier']) : [];
        
        // Filter valid couriers
        $couriers = array_filter($couriers, function ($code) {
            return in_array($code, ['jne', 'sicepat', 'ide', 'sap', 'jnt', 'ninja', 'tiki', 'lion', 'anteraja', 'pos', 'ncs', 'rex', 'rpx', 'sentral', 'star', 'wahana', 'dse']);
        });

        // 1. Check if Cost exists
        $The_cost = Cost::where([
            'origin'      => $payload['origin'],
            'destination' => $payload['destination'],
        ])->first();

        if (!$The_cost) {
            $The_cost = Cost::create([
                'origin'      => $payload['origin'],
                'destination' => $payload['destination'],
                'weight'      => 1000
            ]);
        }
        $cost_id = $The_cost->id ?? null;

        // 2. Check if data exists in database
        $existing = Cost::with('cost_services')
            ->where([
                'origin'      => $payload['origin'],
                'destination' => $payload['destination'],
            ])
            ->whereHas('cost_services', function ($query) use ($couriers) {
                $query->whereIn('code', $couriers);
            })
            ->first();

        if ($existing) {
            ShippingLog::create([
                'method'        => 'POST',
                'endpoint'      => '/calculate/district/domestic-cost',
                'source'        => 'db',
                'status_code'   => 200,
                'success'       => true,
                'duration_ms'   => round((microtime(true) - $start) * 1000),
                'payload'       => $payload,
                'ip_address'    => $ip,
                'user_agent'    => (string) $userAgent,
            ]);

            return [
                'error' => false,
                'status' => 200,
                'data' => $existing->cost_services->toArray(),
            ];
        }

        // 3. Hit API with V2 endpoint (district-based)
        $params_body = [
            'origin'      => (int) $payload['origin'],
            'destination' => (int) $payload['destination'],
            'weight'      => (int) 1000, // Fixed to 1kg
            'courier'     => $couriers ? implode(':', $couriers) : null,
            'length'      => $payload['length'] ?? null,
            'width'       => $payload['width'] ?? null,
            'height'      => $payload['height'] ?? null,
            'diameter'    => $payload['diameter'] ?? null,
            'price'       => $payload['price'] ?? 'lowest',
        ];

        // V2: Use district endpoint
        $response = Http::asForm()->withHeaders([
            'key' => $this->apiKey,
        ])->post("{$this->endpoint}/calculate/district/domestic-cost", $params_body);

        // Log JSON response
        LogJsonHelper::log([
            'payload'   => $params_body,
            'response'  => $response->json(),
        ]);

        $duration = round((microtime(true) - $start) * 1000);

        if (!$response->successful()) {
            ShippingLog::create([
                'method'        => 'POST',
                'endpoint'      => '/calculate/district/domestic-cost',
                'source'        => 'api',
                'status_code'   => $response->status(),
                'success'       => false,
                'duration_ms'   => $duration,
                'payload'       => $payload,
                'error_message' => $response->body(),
                'ip_address'    => (string) $ip,
                'user_agent'    => (string) $userAgent,
            ]);

            return [
                'error' => true,
                'status' => $response->status(),
                'message' => $response->body(),
            ];
        }

        $decoded = $response->json();
        $services = $decoded['data'] ?? [];

        // 4. Save data to cost_service table
        foreach ($services as $service) {
            CostService::create([
                'cost_id'     => $cost_id,
                'name'        => $service['name'],
                'code'        => $service['code'],
                'service'     => $service['service'],
                'description' => $service['description'] ?? null,
                'cost'        => $service['cost'] ?? 0,
                'etd'         => $service['etd'] ?? null,
            ]);
        }

        // 5. Log API success
        ShippingLog::create([
            'method'        => 'POST',
            'endpoint'      => '/calculate/district/domestic-cost',
            'source'        => 'api',
            'status_code'   => $response->status(),
            'success'       => true,
            'duration_ms'   => $duration,
            'payload'       => $payload,
            'ip_address'    => (string) $ip,
            'user_agent'    => (string) $userAgent,
        ]);

        return [
            'error' => false,
            'status' => 200,
            'data' => $services,
            'payload' => $payload,
        ];
    }
}