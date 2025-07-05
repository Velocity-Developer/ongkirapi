<?php

namespace App\Services;

use App\Models\Cost;
use App\Models\CostService;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;

class ShippingService
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
    $start = microtime(true); // Hitung durasi request

    // {"width": null, "height": null, "length": null, "origin": "57462", "weight": "1000", "courier": "jne:jnt", "diameter": null, "ip_address": "127.0.0.1", "user_agent": "PostmanRuntime/7.44.1", "destination": "11710"}

    $ip = $payload['ip_address'];
    $userAgent = $payload['user_agent'];

    // 1. Cek apakah data sudah ada di database
    $existing = Cost::with('cost_services')->where([
      'origin'      => $payload['origin'],
      'destination' => $payload['destination'],
    ])->first();

    if ($existing) {
      ShippingLog::create([
        'method'        => 'POST',
        'endpoint'      => '/calculate/domestic-cost',
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

    // 2. Ambil dari API
    $response = Http::asForm()->withHeaders([
      'key' => $this->apiKey,
    ])->post("{$this->endpoint}/calculate/domestic-cost", [
      'origin'      => (int) $payload['origin'],
      'destination' => (int) $payload['destination'],
      'weight'      => (int) 1000, //fix to 1kg
      'courier'     => $payload['courier'],
      'length'      => $payload['length'] ?? null,
      'width'       => $payload['width'] ?? null,
      'height'      => $payload['height'] ?? null,
      'diameter'    => $payload['diameter'] ?? null,
      'price'       => $payload['price'] ?? 'lowest',
    ]);

    $duration = round((microtime(true) - $start) * 1000);

    if (!$response->successful()) {
      ShippingLog::create([
        'method'        => 'POST',
        'endpoint'      => '/calculate/domestic-cost',
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

    // 3. Simpan data ke tabel cost dan cost_service
    // 
    $cost = Cost::create([
      'origin'      => $payload['origin'],
      'destination' => $payload['destination'],
      'weight'      => 1000,
    ]);

    foreach ($services as $service) {
      CostService::create([
        'cost_id'     => $cost->id,
        'name'        => $service['name'],
        'code'        => $service['code'],
        'service'     => $service['service'],
        'description' => $service['description'] ?? null,
        'cost'        => $service['cost'] ?? 0,
        'etd'         => $service['etd'] ?? null,
      ]);
    }

    // 4. Log API success
    ShippingLog::create([
      'method'        => 'POST',
      'endpoint'      => '/calculate/domestic-cost',
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
