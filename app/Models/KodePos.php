<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodePos extends Model
{
    protected $fillable = [
        'kode_pos',
        'status',
        'rajaongkir_sub_districts_id',
        'subdistricts_id',
        'note',
    ];

    //relasi ke subdistricts
    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistricts_id');
    }

    //relasi ke rajaongkir_sub_districts
    public function rajaongkir_sub_district()
    {
        return $this->belongsTo(RajaongkirSubDistrict::class, 'rajaongkir_sub_districts_id');
    }
}
