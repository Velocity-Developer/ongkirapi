<?php

namespace App\Helpers;

use App\Models\RajaongkirSubDistrict;
use App\Models\KodePos;
use App\Models\Subdistrict;
use Illuminate\Support\Facades\Log;

class RajaOngkirHelper
{
    public static function getSubDistrictIdByZipCode($zipCode)
    {
        //penanganan kode pos = '86121', karena tidak ada subdistric dengan kode pos ini
        //maka gunakan subdistric id = 35471 milik kecamatan Beru,Sikka, NTT
        if ($zipCode == 86121) {
            return 35471;
        }

        //penanganan zip_code = 86911, tidak tersedia
        if ($zipCode == 86911) {
            //gunakan zip_code = 86472
            $zipCode = 86472;
        }

        //penanganan untuk zip_code = 95247, tidak tersedia
        if ($zipCode == 95247) {
            //gunakan zip_code = 95239, Tuminting kota Manado
            $zipCode = 95239;
        }

        //jika kodepos = 15441 , dijadikan 15412
        if ($zipCode == 15441) {
            //gunakan zip_code = 95239, Tuminting kota Manado
            $zipCode = 15412;
        }

        $subDistrict = RajaongkirSubDistrict::where('zip_code', $zipCode)->first();

        if (!$subDistrict) {

            //get subdistricts_id by postal_kode            
            $subdistrict = Subdistrict::where('postal_code', $zipCode)->first();

            KodePos::updateOrCreate(
                ['kode_pos' => $zipCode], // kondisi pencarian (UNIQUE KEY)
                [
                    'subdistricts_id' => $subdistrict ? $subdistrict->id : null,
                    'status' => 'inactive',
                    'note'   => 'Kode Pos ' . $zipCode . ' tidak ditemukan di zip_code RajaongkirSubDistrict',
                ]
            );
        }

        return $subDistrict ? $subDistrict->id : null;
    }

    public static function getSubDistrictsByZipCode($zipCode)
    {
        return RajaongkirSubDistrict::where('zip_code', $zipCode)->get();
    }

    public static function getSubDistrictWithRelations($zipCode)
    {
        return RajaongkirSubDistrict::with(['district', 'province'])
            ->where('zip_code', $zipCode)
            ->first();
    }
}
