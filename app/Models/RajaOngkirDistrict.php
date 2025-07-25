<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RajaOngkirDistrict extends Model
{
    use HasFactory;

    protected $table = 'rajaongkir_districts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'city_id',
        'name',
    ];

    public function city()
    {
        return $this->belongsTo(RajaOngkirCity::class, 'city_id');
    }
}
