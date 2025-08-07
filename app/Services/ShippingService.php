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
    $start = microtime(true); // Hitung durasi request

    // {"width": null, "height": null, "length": null, "origin": "57462", "weight": "1000", "courier": "jne:jnt", "diameter": null, "ip_address": "127.0.0.1", "user_agent": "PostmanRuntime/7.44.1", "destination": "11710"}

    $ip = $payload['ip_address'];
    $userAgent = $payload['user_agent'];

    $couriers = $payload['courier'] ? explode(':', $payload['courier']) : [];
    //the valid courier is jne, sicepat, ide, sap, jnt, ninja, tiki, lion, anteraja, pos, ncs, rex, rpx, sentral, star, wahana, dse
    //jika di couriers ada yang tidak valid, hapus dari array

    $couriers = array_filter($couriers, function ($code) {
      return in_array($code, ['jne', 'sicepat', 'ide', 'sap', 'jnt', 'ninja', 'tiki', 'lion', 'anteraja', 'pos', 'ncs', 'rex', 'rpx', 'sentral', 'star', 'wahana', 'dse']);
    });

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

  public function getWaybill(array $payload)
  {
    $start = microtime(true);

    $awb = RajaongkirAwb::where('waybill_number', $payload['awb'])->first();

    // Jika tidak ada atau ada tapi status bukan DELIVERED dan updated_at lebih dari 1 jam
    if (!$awb || $awb->status !== 'DELIVERED' && $awb->updated_at < now()->subHour()) {
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
                    'courier' => $data['data']['summary']['courier_name'] .'-'.$data['data']['summary']['service_code'],
                    'waybill_date' => $data['data']['details']['waybill_date'] .' '.$data['data']['details']['waybill_time'] ?? null,
                    'weight' => $data['data']['details']['weight'] ?? null,
                    'shipper_name' => $data['data']['details']['shipper_name'] ?? null,
                    'shipper_address' => $data['data']['details']['shipper_address1'] ?? null,
                    'receiver_name' => $data['data']['details']['receiver_name'] ?? null,
                    'receiver_address' => $data['data']['details']['receiver_address1'] ?? null,
                    'status' => $data['data']['delivery_status']['status'],
                    'pod_receiver' => $data['data']['delivery_status']['pod_receiver'] ?? null,
                ]
            );

            // Ambil data yang baru saja disimpan
            $awb = RajaongkirAwb::where('waybill_number', $data['data']['details']['waybill_number'])->first();
            
            // simpan ke manifest
            if (isset($data['data']['manifest']) && is_array($data['data']['manifest'])) {
                // Hapus manifest lama jika ada
                RajaongkirAwbManifest::where('rajaongkir_awb_id', $awb->id)->delete();
                foreach ($data['data']['manifest'] as $manifest) {
                    RajaongkirAwbManifest::Create(
                        [
                          'rajaongkir_awb_id' => $awb->id,
                          'manifest_date' => $manifest['manifest_date'],
                          'manifest_time' => $manifest['manifest_time'],
                          'manifest_code' => $manifest['manifest_code'],
                          'manifest_description' => $manifest['manifest_description'],
                          'city_name' => $manifest['city_name'] ?? null,
                        ],
                    );
                }

                $manifest = RajaongkirAwbManifest::where('rajaongkir_awb_id', $awb->id)->get();
            }
            // Jika manifest tidak ada, set ke array kosong
            $new_manifest = $manifest->toArray();
            // Log Shipping response
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

            // return data awb
            return [
              'rajaongkir' => [
                // 'error' => false,
                'status' => [
                  'code' => 200,
                  'description' => 'OK',
                ],
                'result' => [
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
                  // jika manifest tidak kosong, tampilkan manifest
                  'manifest' => $new_manifest ? array_map(function ($m) {
                    return [
                      'manifest_description' => $m['manifest_code'] ?? null,
                      'manifest_date' => $m['manifest_date'] ?? null,
                      'manifest_time' => $m['manifest_time'] ?? null,
                      'city_name' => $m['city_name'] ?? null,
                    ];
                  }, $new_manifest) : [],
                ],
                // 'payload' => $payload,
              ],
            ];
        } else {
            // return error response
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
        }
    } else {
      $manifest = RajaongkirAwbManifest::where('rajaongkir_awb_id', $awb->id)->get();
      $new_manifest = $manifest->toArray();
      // Log Shipping response
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

      // Return data awb
      return [
        'rajaongkir' => [
          // 'error' => false,
          'status' => [
            'code' => 200,
            'description' => 'OK',
          ],
          'result' => [
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
            // jika manifest tidak kosong, tampilkan manifest
            'manifest' => $new_manifest ? array_map(function ($m) {
              return [
                'manifest_description' => $m['manifest_code'] ?? null,
                'manifest_date' => $m['manifest_date'] ?? null,
                'manifest_time' => $m['manifest_time'] ?? null,
                'city_name' => $m['city_name'] ?? null,
              ];
            }, $new_manifest) : [],
          ],
          // 'payload' => $payload,
        ],
      ];
    }
  }
}