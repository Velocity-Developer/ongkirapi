<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'city_id',
        'type',
        'city_name',
        'postal_code',
        'province_id',
        'province',
    ];

    //hidden
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    //relasi ke province
    public function province()
    {
        return $this->belongsTo(Province::class);
    }
}
