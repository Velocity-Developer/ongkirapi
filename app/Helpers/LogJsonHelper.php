<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogJsonHelper
{

    public static function log(array $data, $filename = 'custom-log.json')
    {
        $path = storage_path('logs/' . $filename);
        $existing = File::exists($path) ? json_decode(File::get($path), true) : [];

        $existing[] = [
            'data' => $data,
            'time' => Carbon::now()->toDateTimeString(),
        ];

        File::put($path, json_encode($existing, JSON_PRETTY_PRINT));
    }
}
