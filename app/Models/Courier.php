<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'code',
        'logo',
    ];

    //hidden fields
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function cost_services()
    {
        return $this->hasMany(CostService::class, 'code', 'code');
    }

    public function courier_services()
    {
        return $this->hasMany(CourierService::class);
    }
}
