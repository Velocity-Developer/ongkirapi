<?php

namespace Database\Seeders;

use App\Models\RajaongkirSubDistrict;
use App\Models\RajaOngkirDistrict;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class RajaOngkirSubDistrictSeeder extends Seeder
{
  public function run(): void
  {
    // Get all districts
    $districts = RajaOngkirDistrict::all();

    // Total districts
    $total_districts = $districts->count();
    $this->command->info("Total districts: {$total_districts}");

    $counter = 1;
    foreach ($districts as $district) {

      // Jika RajaongkirSubDistrict dengan district_id $district->id sudah ada, maka skip
      if (RajaongkirSubDistrict::where('district_id', $district->id)->count() > 0) {
        $this->command->info($total_districts . "/" . $counter . " Skip sub districts for district: {$district->name}");
        $counter++;
        continue;
      }

      $response = Http::withHeaders([
        'key' => config('services.rajaongkir.key'),
      ])->get(config('services.rajaongkir.base_url') . "/destination/sub-district/{$district->id}");

      if (!$response->successful()) {
        $this->command->error("Failed to fetch sub districts for district: {$district->name}");
        $counter++;
        continue;
      }

      $this->command->line("Importing sub districts for district: {$district->name}");

      $subDistricts = $response->json('data');

      foreach ($subDistricts as $subDistrict) {
        $this->command->line("→ Importing sub district: {$subDistrict['name']}");
        RajaongkirSubDistrict::updateOrCreate(
          ['id' => $subDistrict['id']],
          [
            'name' => $subDistrict['name'],
            'zip_code' => $subDistrict['zip_code'],
            'district_id' => $district->id,
          ]
        );
      }

      $this->command->info($total_districts . "/" . $counter . " Imported sub districts for district: {$district->name}");

      // Delay 30-50 detik
      sleep(rand(30, 50));

      $counter++;
    }
  }
}
