<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingLog extends Model
{
  protected $fillable = [
    'method',
    'endpoint',
    'source',
    'status_code',
    'success',
    'duration_ms',
    'payload',
    'error_message',
  ];

  protected $casts = [
    'success' => 'boolean',
    'payload' => 'array',
  ];
}
