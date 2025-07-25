<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Seeder;
use App\Models\RajaOngkirCity;
use App\Models\RajaOngkirDistrict;

class RajaOngkirDistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //get all city
        $cities = RajaOngkirCity::all();

        //get city offset 10
        // $cities = RajaOngkirCity::offset(119)->limit(10)->get();

        //total city
        $total_city = $cities->count();
        $this->command->info("Total city: {$cities->count()}");

        $counter = 1;
        foreach ($cities as $city) {

            //jika RajaOngkirDistrict dengan city_id $city->id sudah ada, maka skip
            if (RajaOngkirDistrict::where('city_id', $city->id)->count() > 0) {
                $this->command->info($total_city . "/" . $counter . " Skip districts for city: {$city->name}");
                $counter++;
                continue;
            }

            $response = Http::withHeaders([
                'key' => config('services.rajaongkir.key'),
            ])->get(config('services.rajaongkir.base_url') . "/destination/district/{$city->id}");

            if (!$response->successful()) {
                $this->command->error("Failed to fetch districts for city: {$city->name}");
                continue;
            }

            $this->command->line("Importing districts for city: {$city->name}");

            $districts = $response->json('data');

            foreach ($districts as $district) {
                $this->command->line("→ Importing district: {$district['name']}");
                RajaOngkirDistrict::updateOrCreate(
                    ['id' => $district['id']],
                    [
                        'name' => $district['name'],
                        'city_id' => $city->id,
                    ]
                );
            }

            $this->command->info($total_city . "/" . $counter . " Imported districts for city: {$city->name}");

            //delay 10-30 detik
            sleep(rand(10, 30));

            $counter++;
        }
    }
}
