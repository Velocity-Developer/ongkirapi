<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RajaongkirSubDistrict extends Model
{
  use HasFactory;

  protected $table = 'rajaongkir_sub_districts';

  protected $fillable = [
    'id',
    'name',
    'zip_code',
    'district_id',
  ];

  public $incrementing = false;
  protected $keyType = 'int';

  public function district()
  {
    return $this->belongsTo(RajaOngkirDistrict::class, 'district_id');
  }

  public function province()
  {
    return $this->hasOneThrough(
      RajaOngkirProvince::class,
      RajaOngkirDistrict::class,
      'id',
      'id',
      'district_id',
      'province_id'
    );
  }
}
