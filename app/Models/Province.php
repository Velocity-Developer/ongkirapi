<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $fillable = [
        'province_id',
        'province',
        'code',
    ];

    //hidden
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    //relasi ke city
    public function city()
    {
        return $this->hasMany(City::class);
    }

    //relasi ke subdistrict
    public function subdistrict()
    {
        return $this->hasMany(Subdistrict::class);
    }
}
