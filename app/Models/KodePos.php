<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodePos extends Model
{
    protected $fillable = [
        'kode_pos',
        'status',
        'rajaongkir_sub_districts_id',
        'note',
    ];

    //relasi ke rajaongkir_sub_districts
    public function rajaongkir_sub_district()
    {
        return $this->belongsTo(RajaongkirSubDistrict::class);
    }
}
