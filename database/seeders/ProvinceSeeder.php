<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Province;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $file = resource_path("rajaongkir/provinsi.json");

        // Decode ke array asosiatif (true sebagai parameter kedua)
        $results = json_decode(file_get_contents($file), true);

        foreach ($results['rajaongkir']['results'] as $result) {
            //create or update
            Province::updateOrCreate([
                'province_id'   => $result['province_id'],
                'province'      => $result['province']
            ]);
        }
    }
}
