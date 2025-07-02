<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\City;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $file = resource_path("rajaongkir/city.json");

        // Decode ke array asosiatif (true sebagai parameter kedua)
        $results = json_decode(file_get_contents($file), true);

        foreach ($results['rajaongkir']['results'] as $result) {

            //update or create by city_id
            City::updateOrCreate([
                'city_id'       => $result['city_id'],
                'city_name'     => $result['city_name'],
                'province_id'   => $result['province_id'],
                'province'      => $result['province'],
                'type'          => $result['type'],
                'postal_code'   => $result['postal_code']
            ]);

            //command info
            $this->command->info("City " . $result['city_name'] . " has been added");
        }
    }
}
