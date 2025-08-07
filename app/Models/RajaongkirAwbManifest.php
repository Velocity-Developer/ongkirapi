<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RajaongkirAwbManifest extends Model
{
    // use HasFactory;
    protected $table = 'rajaongkir_awb_manifests';

    protected $fillable = [
        'rajaongkir_awb_id',
        'manifest_code',
        'manifest_description',
        'manifest_date',
        'manifest_time',
        'city_name',
    ];

    // relasi ke RajaongkirAwb
    public function awb()
    {
        return $this->belongsTo(RajaongkirAwb::class, 'rajaongkir_awb_id');
    }
}
