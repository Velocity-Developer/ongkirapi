<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingLog extends Model
{

  protected $fillable = [
    'method',
    'endpoint',
    'domain',
    'source',
    'status_code',
    'success',
    'duration_ms',
    'payload',
    'error_message',
    'ip_address',
    'user_agent',
  ];

  protected $casts = [
    'success' => 'boolean',
    'payload' => 'array',
  ];

  protected static function booted(): void
  {
    static::creating(function (ShippingLog $shippingLog) {
      if (!$shippingLog->domain && $shippingLog->user_agent) {
        $shippingLog->domain = self::extractDomainFromUserAgent($shippingLog->user_agent);
      }
    });
  }

  public static function extractDomainFromUserAgent(string $userAgent): ?string
  {
    if (preg_match('/https?:\/\/[^\s)]+/i', $userAgent, $matches)) {
      return parse_url($matches[0], PHP_URL_HOST) ?: null;
    }

    if (preg_match('/(?:[a-z0-9-]+\.)+[a-z]{2,}/i', $userAgent, $matches)) {
      return strtolower($matches[0]);
    }

    if (preg_match('/^([a-z][a-z0-9_-]*)\/[\d.]+/i', $userAgent, $matches)) {
      return $matches[1];
    }

    return null;
  }
}
