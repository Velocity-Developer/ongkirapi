<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RajaOngkirCity extends Model
{
  protected $table = 'rajaongkir_cities';

  protected $primaryKey = 'id';

  public $incrementing = false;

  protected $keyType = 'int';

  protected $fillable = [
    'id',
    'name',
    'province_id',
  ];

  public function province()
  {
    return $this->belongsTo(RajaOngkirProvince::class, 'province_id');
  }
}
