<?php

namespace App\Services;

use App\Models\Cost;
use App\Models\CostService;
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
    // 1. Cek apakah data sudah ada di database
    $existing = Cost::with('cost_services')->where([
      'origin' => $payload['origin'],
      'destination' => $payload['destination'],
      'service' => $payload['courier'],
      'weight' => $payload['weight'],
    ])->first();

    if ($existing) {
      return [
        'error' => false,
        'status' => 200,
        'data' => $existing->cost_services->toArray(),
      ];
    }

    // 2. Ambil dari API jika belum ada
    $response = Http::asForm()->withHeaders([
      'key' => $this->apiKey,
    ])->post("{$this->endpoint}/calculate/domestic-cost", [
      'origin'      => (int) $payload['origin'],
      'destination' => (int) $payload['destination'],
      'weight'      => (int) $payload['weight'],
      'courier'     => $payload['courier'],
      'length'      => $payload['length'] ?? null,
      'width'       => $payload['width'] ?? null,
      'height'      => $payload['height'] ?? null,
      'diameter'    => $payload['diameter'] ?? null,
      'price'       => $payload['price'] ?? 'lowest',
    ]);

    if (!$response->successful()) {
      return [
        'error' => true,
        'status' => $response->status(),
        'message' => $response->body(),
      ];
    }

    $decoded = $response->json();
    $services = $decoded['data'] ?? [];

    // 3. Simpan ke tabel costs
    $cost = Cost::create([
      'origin' => $payload['origin'],
      'destination' => $payload['destination'],
      'service' => $payload['courier'],
      'weight' => $payload['weight']
    ]);

    // 4. Simpan ke tabel cost_services
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

    return [
      'error' => false,
      'status' => 200,
      'data' => $services,
      'payload' => $payload
    ];
  }
}
