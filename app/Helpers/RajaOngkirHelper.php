<?php

namespace App\Helpers;

use App\Models\RajaongkirSubDistrict;

class RajaOngkirHelper
{
    public static function getSubDistrictIdByZipCode($zipCode)
    {
        $subDistrict = RajaongkirSubDistrict::where('zip_code', $zipCode)->first();

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
