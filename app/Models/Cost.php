<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cost extends Model
{
    protected $fillable = [
        'origin',
        'origin_type',
        'destination',
        'destination_type',
        'courier',
        'weight',
        'result',
    ];

    public function cost_services()
    {
        return $this->hasMany(CostService::class);
    }

    protected $casts = [
        'result' => 'array',
    ];
}
