<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\RajaOngkirProvince;

class RajaOngkirProvinceSeeder extends Seeder
{

    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.key');
        $this->endpoint = config('services.rajaongkir.base_url');
    }
    public function run(): void
    {
        $response = Http::withHeaders([
            'key' => $this->apiKey,
        ])->get($this->endpoint . '/destination/province');

        if (!$response->successful()) {
            throw new \Exception('Gagal mengambil data provinsi dari API: ' . $response->body());
        }

        $data = $response->json('data');

        foreach ($data as $item) {
            RajaOngkirProvince::updateOrCreate(
                ['id' => $item['id']],
                ['name' => $item['name']]
            );
        }

        $this->command->info(count($data) . ' provinsi berhasil disimpan.');
    }
}
