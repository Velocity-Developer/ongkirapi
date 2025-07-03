<?php

namespace App\Services;

use App\Models\Cost;
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
    $existing = Cost::where([
      'origin' => $payload['origin'],
      'origin_type' => $payload['originType'] ?? 'subdistrict',
      'destination' => $payload['destination'],
      'destination_type' => $payload['destinationType'] ?? 'subdistrict',
      'courier' => $payload['courier'],
      'weight' => $payload['weight'],
    ])->first();

    if ($existing) {
      return [
        'error' => false,
        'status' => 200,
        'data' => json_encode($existing->result), // kembalikan sebagai string seperti API
      ];
    }

    // 2. Ambil dari API jika tidak ada
    $response = Http::asForm()->withHeaders([
      'key' => $this->apiKey,
    ])->post("{$this->endpoint}/calculate/domestic-cost", [
      'origin'        => (int) $payload['origin'],
      'destination'   => (int) $payload['destination'],
      'weight'        => (int) $payload['weight'],
      'courier'       => $payload['courier'],
      'length'        => $payload['length'] ?? null,
      'width'         => $payload['width'] ?? null,
      'height'        => $payload['height'] ?? null,
      'diameter'      => $payload['diameter'] ?? null,
      'price'         => $payload['price'] ?? 'lowest',
    ]);

    if ($response->successful()) {
      $decoded = $response->json();

      // 3. Simpan ke DB
      Cost::create([
        'origin' => $payload['origin'],
        'origin_type' => $payload['originType'] ?? 'subdistrict',
        'destination' => $payload['destination'],
        'destination_type' => $payload['destinationType'] ?? 'subdistrict',
        'courier' => $payload['courier'],
        'weight' => $payload['weight'],
        'result' => $decoded['data'] ?? [],
      ]);

      return [
        'error' => false,
        'status' => 200,
        'data' => json_encode($decoded['data'] ?? []),
      ];
    }

    return [
      'error' => true,
      'status' => $response->status(),
      'message' => $response->body(),
    ];
  }
}
