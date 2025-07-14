<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RajaOngkirProvince extends Model
{
    protected $table = 'rajaongkir_provinces';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
    ];
}
