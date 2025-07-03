<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Subdistrict;

class SubdistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $file = resource_path("rajaongkir/kecamatan.json");

        // Decode ke array asosiatif (true sebagai parameter kedua)
        $results = json_decode(file_get_contents($file), true);

        foreach ($results as $i => $resultx) {
            foreach ($resultx['rajaongkir']['results'] as $i => $result) {

                //update or create by city_id
                Subdistrict::updateOrCreate([
                    'subdistrict_id'    => $result['subdistrict_id'],
                    'subdistrict_name'  => $result['subdistrict_name'],
                    'postal_code'       => $result['postal_code']
                ], [
                    'city_id'           => $result['city_id'],
                    'city'              => $result['city'],
                    'type'              => $result['type'],
                    'province_id'       => $result['province_id'],
                    'province'          => $result['province']
                ]);

                //command info
                $this->command->info("Subdistric " . $result['subdistrict_name'] . " has been added");
            }
        }
    }
}
