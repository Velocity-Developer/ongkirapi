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
    $response = Http::withHeaders([
      'key' => $this->apiKey
    ])->post("{$this->endpoint}/cost", [
      'origin'        => $payload['origin'],
      'originType'    => $payload['originType'],
      'destination'   => $payload['destination'],
      'destinationType' => $payload['destinationType'],
      'weight'        => $payload['weight'],
      'courier'       => $payload['courier'],
      'length'        => $payload['length'] ?? null,
      'width'         => $payload['width'] ?? null,
      'height'        => $payload['height'] ?? null,
      'diameter'      => $payload['diameter'] ?? null,
    ]);

    if ($response->successful()) {
      return $response->json();
    }

    return [
      'error' => true,
      'status' => $response->status(),
      'message' => $response->body(),
    ];
  }
}
