<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RajaongkirAwb extends Model
{
    protected $table = 'rajaongkir_awbs';

    protected $fillable = [
        'waybill_number',
        'courier',
        'waybill_date',
        'weight',
        'origin',
        'destination',
        'shipper_name',
        'shipper_address',
        'receiver_name',
        'receiver_address',
        'status',
        'pod_receiver',
    ];


    // relasi ke RajaongkirAwbManifest
    public function manifests()
    {
        return $this->hasMany(RajaongkirAwbManifest::class, 'rajaongkir_awb_id');
    }
}
