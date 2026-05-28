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

    //hidden fields
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
