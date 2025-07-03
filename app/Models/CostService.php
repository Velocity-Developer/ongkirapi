<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostService extends Model
{
    protected $fillable = [
        'cost_id',
        'name',
        'code',
        'service',
        'description',
        'cost',
        'etd',
    ];

    public function cost()
    {
        return $this->belongsTo(Cost::class);
    }
}
