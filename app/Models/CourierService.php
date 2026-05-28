<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierService extends Model
{
    protected $fillable = [
        'courier_id',
        'courier_code',
        'name',
        'code',
        'description',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
