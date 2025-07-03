<?php

namespace App\Services;

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
      'price'         => $payload['price'] ?? 'lowest', // tambahan jika kamu ingin sorting
    ]);

    if ($response->successful()) {
      return [
        'error' => false,
        'status' => $response->status(),
        'data' => $response->body(),
      ];
    }

    return [
      'error' => true,
      'status' => $response->status(),
      'message' => $response->body(),
    ];
  }
}
