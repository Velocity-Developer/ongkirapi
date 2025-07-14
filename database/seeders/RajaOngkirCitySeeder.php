<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\RajaOngkirProvince;
use App\Models\RajaOngkirCity;

class RajaOngkirCitySeeder extends Seeder
{
    public function run(): void
    {
        $provinces = RajaOngkirProvince::all();

        foreach ($provinces as $province) {
            $response = Http::withHeaders([
                'key' => config('services.rajaongkir.key'),
            ])->get(config('services.rajaongkir.base_url') . "/destination/city/{$province->id}");

            if (!$response->successful()) {
                $this->command->error("Failed to fetch cities for province: {$province->name}");
                continue;
            }

            $this->command->line("Importing cities for province: {$province->name}");

            $cities = $response->json('data');

            foreach ($cities as $city) {
                $this->command->line("→ Importing city: {$city['name']}");
                RajaOngkirCity::updateOrCreate(
                    ['id' => $city['id']],
                    [
                        'name' => $city['name'],
                        'province_id' => $province->id,
                    ]
                );
            }

            $this->command->info("Imported cities for province: {$province->name}");
        }
    }
}
