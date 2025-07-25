<?php

namespace App\Services;

use App\Models\Cost;
use App\Models\CostService;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Http;
use App\Helpers\LogJsonHelper;

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
    $couriers = $payload['courier'] ? explode(':', $payload['courier']) : [];

    //1. Cek Cost ada atau tidak
    $The_cost = Cost::where([
      'origin'      => $payload['origin'],
      'destination' => $payload['destination'],
    ])->first();

    //jika tidak ada, buat baru
    if (!$The_cost) {
      $The_cost = Cost::create([
        'origin'      => $payload['origin'],
        'destination' => $payload['destination'],
        'weight'      => 1000
      ]);
    }
    $cost_id = $The_cost->id ?? null;

    // 2. Cek apakah data sudah ada di database
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
    $params_body = [
      'origin'      => (int) $payload['origin'],
      'destination' => (int) $payload['destination'],
      'weight'      => (int) 1000, //fix to 1kg
      'courier'     => $payload['courier'],
      'length'      => $payload['length'] ?? null,
      'width'       => $payload['width'] ?? null,
      'height'      => $payload['height'] ?? null,
      'diameter'    => $payload['diameter'] ?? null,
      'price'       => $payload['price'] ?? 'lowest',
    ];
    $response = Http::asForm()->withHeaders([
      'key' => $this->apiKey,
    ])->post("{$this->endpoint}/calculate/domestic-cost", $params_body);

    //simpan log json
    LogJsonHelper::log([
      'payload'   => $params_body,
      'response'  => $response->json(),
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

    // 3. Simpan data ke tabel cost_service
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
