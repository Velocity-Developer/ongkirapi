<?php

namespace App\Services;

use App\Models\Cost;
use App\Models\CostService;
use App\Models\ShippingLog;
use App\Models\RajaongkirAwb;
use App\Models\RajaongkirAwbManifest;
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
    $start = microtime(true);

    $ip = $payload['ip_address'];
    $userAgent = $payload['user_agent'];

    $couriers = $payload['courier'] ? explode(':', $payload['courier']) : [];
    $couriers = array_filter($couriers, function ($code) {
      return in_array($code, ['jne', 'sicepat', 'ide', 'sap', 'jnt', 'ninja', 'tiki', 'lion', 'anteraja', 'pos', 'ncs', 'rex', 'rpx', 'sentral', 'star', 'wahana', 'dse']);
    });

    // 1. Cek data di database
    $existing = Cost::with('cost_services')
      ->where([
        'origin'      => $payload['origin'],
        'destination' => $payload['destination'],
      ])
      ->whereHas('cost_services', function ($query) use ($couriers) {
        $query->whereIn('code', $couriers);
      })
      ->first();

    // 2. Jika data ada dan umur data < 3 bulan, langsung dari DB
    if ($existing && $existing->updated_at > now()->subMonths(3)) {
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

    // 3. Data tidak ada atau sudah > 3 bulan, coba API jika limit masih ada
    $todayApiCount = ShippingLog::where('endpoint', '/calculate/domestic-cost')
      ->where('source', 'api')
      ->whereDate('created_at', today())
      ->count();

    if ($todayApiCount < 100) {
      $params_body = [
        'origin'      => (int) $payload['origin'],
        'destination' => (int) $payload['destination'],
        'weight'      => (int) 1000,
        'courier'     => $couriers ? implode(':', $couriers) : null,
        'length'      => $payload['length'] ?? null,
        'width'       => $payload['width'] ?? null,
        'height'      => $payload['height'] ?? null,
        'diameter'    => $payload['diameter'] ?? null,
        'price'       => $payload['price'] ?? 'lowest',
      ];
      $response = Http::asForm()->withHeaders([
        'key' => $this->apiKey,
      ])->post("{$this->endpoint}/calculate/domestic-cost", $params_body);

      LogJsonHelper::log([
        'payload'   => $params_body,
        'response'  => $response->json(),
      ]);

      $duration = round((microtime(true) - $start) * 1000);

      if ($response->successful()) {
        $decoded = $response->json();
        $services = $decoded['data'] ?? [];

        $The_cost = Cost::updateOrCreate([
          'origin'      => $payload['origin'],
          'destination' => $payload['destination'],
        ], [
          'weight' => 1000,
        ]);
        $cost_id = $The_cost->id;

        // Hapus cost_service lama untuk courier yang diminta, lalu insert baru
        CostService::where('cost_id', $cost_id)
          ->whereIn('code', $couriers)
          ->delete();

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

    // 4. Limit habis, pakai data dari DB (meskipun stale) atau error jika tidak ada
    if ($existing) {
      ShippingLog::create([
        'method'        => 'POST',
        'endpoint'      => '/calculate/domestic-cost',
        'source'        => 'rate_limit',
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

    ShippingLog::create([
      'method'        => 'POST',
      'endpoint'      => '/calculate/domestic-cost',
      'source'        => 'rate_limit',
      'status_code'   => 429,
      'success'       => false,
      'duration_ms'   => round((microtime(true) - $start) * 1000),
      'payload'       => $payload,
      'error_message' => 'Daily request limit to RajaOngkir reached (100/day) and no cached data available',
      'ip_address'    => (string) $ip,
      'user_agent'    => (string) $userAgent,
    ]);

    return [
      'error' => true,
      'status' => 429,
      'message' => 'Daily request limit to RajaOngkir has been reached (100 requests/day). No cached data available. Please try again tomorrow.',
    ];
  }

  public function getWaybill(array $payload)
  {
    $start = microtime(true);

    $awb = RajaongkirAwb::where('waybill_number', $payload['awb'])->first();

    // 1. Jika data ada dan umur data < 3 bulan, langsung dari DB
    if ($awb && $awb->updated_at > now()->subMonths(3)) {
      $manifest = RajaongkirAwbManifest::where('rajaongkir_awb_id', $awb->id)->get();
      $new_manifest = $manifest->toArray();

      ShippingLog::create([
        'method'        => 'POST',
        'endpoint'      => '/v1/waybill',
        'source'        => 'db',
        'status_code'   => 200,
        'success'       => true,
        'duration_ms'   => round((microtime(true) - $start) * 1000),
        'payload'       => $payload,
        'ip_address'    => $payload['ip_address'] ?? '127.0.0.1',
      ]);

      return [
        'rajaongkir' => [
          'status' => [
            'code' => 200,
            'description' => 'OK',
          ],
          'result' => $this->formatWaybillResponse($awb, $new_manifest),
        ],
      ];
    }

    // 2. Data tidak ada atau sudah > 3 bulan, coba API jika limit masih ada
    $todayApiCount = ShippingLog::where('endpoint', '/v1/waybill')
      ->where('source', 'api')
      ->whereDate('created_at', today())
      ->count();

    if ($todayApiCount < 100) {
      $params_body = [
        'awb' => $payload['awb'],
        'courier' => $payload['courier'],
        'last_phone_number' => $payload['last_phone_number'] ?? null,
      ];
      $response = Http::asForm()->withHeaders([
        'key' => $this->apiKey,
      ])->post("{$this->endpoint}/track/waybill", $params_body);

      if ($response->successful()) {
        $data = $response->json();

        RajaongkirAwb::updateOrCreate(
          ['waybill_number' => $data['data']['details']['waybill_number']],
          [
            'courier' => $data['data']['summary']['courier_name'] . '-' . $data['data']['summary']['service_code'],
            'waybill_date' => $data['data']['details']['waybill_date'] . ' ' . $data['data']['details']['waybill_time'] ?? null,
            'weight' => $data['data']['details']['weight'] ?? null,
            'shipper_name' => $data['data']['details']['shipper_name'] ?? null,
            'shipper_address' => $data['data']['details']['shipper_address1'] ?? null,
            'receiver_name' => $data['data']['details']['receiver_name'] ?? null,
            'receiver_address' => $data['data']['details']['receiver_address1'] ?? null,
            'status' => $data['data']['delivery_status']['status'],
            'pod_receiver' => $data['data']['delivery_status']['pod_receiver'] ?? null,
          ]
        );

        $awb = RajaongkirAwb::where('waybill_number', $data['data']['details']['waybill_number'])->first();

        if (isset($data['data']['manifest']) && is_array($data['data']['manifest'])) {
          RajaongkirAwbManifest::where('rajaongkir_awb_id', $awb->id)->delete();
          foreach ($data['data']['manifest'] as $manifest) {
            RajaongkirAwbManifest::Create([
              'rajaongkir_awb_id' => $awb->id,
              'manifest_date' => $manifest['manifest_date'],
              'manifest_time' => $manifest['manifest_time'],
              'manifest_code' => $manifest['manifest_code'],
              'manifest_description' => $manifest['manifest_description'],
              'city_name' => $manifest['city_name'] ?? null,
            ]);
          }
          $manifest = RajaongkirAwbManifest::where('rajaongkir_awb_id', $awb->id)->get();
        }
        $new_manifest = $manifest ? $manifest->toArray() : [];

        ShippingLog::create([
          'method'        => 'POST',
          'endpoint'      => '/v1/waybill',
          'source'        => 'api',
          'status_code'   => $response->status(),
          'success'       => true,
          'duration_ms'   => round((microtime(true) - $start) * 1000),
          'payload'       => $payload,
          'ip_address'    => $payload['ip_address'] ?? '127.0.0.1',
        ]);

        return [
          'rajaongkir' => [
            'status' => [
              'code' => 200,
              'description' => 'OK',
            ],
            'result' => $this->formatWaybillResponse($awb, $new_manifest),
          ],
        ];
      }

      ShippingLog::create([
        'method'        => 'POST',
        'endpoint'      => '/v1/waybill',
        'source'        => 'api',
        'status_code'   => $response->status(),
        'success'       => false,
        'duration_ms'   => round((microtime(true) - $start) * 1000),
        'payload'       => $payload,
        'error_message' => $response->body(),
        'ip_address'    => $payload['ip_address'] ?? '127.0.0.1',
      ]);

      // Fall through to stale data below if API fails
    }

    // 3. Limit habis atau API gagal, pakai data dari DB (meskipun stale)
    if ($awb) {
      $manifest = RajaongkirAwbManifest::where('rajaongkir_awb_id', $awb->id)->get();
      $new_manifest = $manifest->toArray();

      ShippingLog::create([
        'method'        => 'POST',
        'endpoint'      => '/v1/waybill',
        'source'        => 'rate_limit',
        'status_code'   => 200,
        'success'       => true,
        'duration_ms'   => round((microtime(true) - $start) * 1000),
        'payload'       => $payload,
        'ip_address'    => $payload['ip_address'] ?? '127.0.0.1',
      ]);

      return [
        'rajaongkir' => [
          'status' => [
            'code' => 200,
            'description' => 'OK',
          ],
          'result' => $this->formatWaybillResponse($awb, $new_manifest),
        ],
      ];
    }

    // 4. Tidak ada data di DB dan limit habis
    ShippingLog::create([
      'method'        => 'POST',
      'endpoint'      => '/v1/waybill',
      'source'        => 'rate_limit',
      'status_code'   => 429,
      'success'       => false,
      'duration_ms'   => round((microtime(true) - $start) * 1000),
      'payload'       => $payload,
      'error_message' => 'Daily request limit to RajaOngkir reached (100/day) and no cached data available',
      'ip_address'    => $payload['ip_address'] ?? '127.0.0.1',
    ]);

    return [
      'rajaongkir' => [
        'status' => [
          'code' => 429,
          'description' => 'Daily request limit to RajaOngkir has been reached (100 requests/day). No cached data available. Please try again tomorrow.',
        ],
      ],
    ];
  }

  /**
   * Format waybill data + manifest menjadi response standar.
   */
  private function formatWaybillResponse(RajaongkirAwb $awb, array $new_manifest): array
  {
    return [
      'summary' => [
        'courier_name' => explode("-", $awb->courier)[0] ?? null,
        'waybill_number' => $awb->waybill_number,
        'service_code'  => explode("-", $awb->courier)[1] ?? null,
        'waybill_date' => explode(" ", $awb->waybill_date)[0] ?? null,
        'waybill_time' => explode(" ", $awb->waybill_date)[1] ?? null,
        'weight' => $awb->weight,
        'shipper_name' => $awb->shipper_name,
        'origin' => $awb->shipper_address,
        'receiver_name' => $awb->receiver_name,
        'destination' => $awb->receiver_address,
        'status' => $awb->status,
      ],
      'details' => [
        'waybill_time' => explode(" ", $awb->waybill_date)[1] ?? null,
        'weight' => $awb->weight,
      ],
      'manifest' => $new_manifest ? array_map(function ($m) {
        return [
          'manifest_description' => $m['manifest_code'] ?? null,
          'manifest_date' => $m['manifest_date'] ?? null,
          'manifest_time' => $m['manifest_time'] ?? null,
          'city_name' => $m['city_name'] ?? null,
        ];
      }, $new_manifest) : [],
    ];
  }
}
