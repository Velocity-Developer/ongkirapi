<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subdistrict extends Model
{
    protected $fillable = [
        'subdistrict_id',
        'subdistrict_name',
        'city_id',
        'type',
        'city',
        'province_id',
        'province',
    ];

    //hidden
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    //relasi ke city
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    //relasi ke province
    public function province()
    {
        return $this->belongsTo(Province::class);
    }
}
