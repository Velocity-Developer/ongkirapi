<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    /**
     * Basic API connectivity test
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function basic(Request $request): JsonResponse
    {
        return response()->json([
            'rajaongkir' => [
                'status' => [
                    'code' => 200,
                    'description' => 'Test endpoint success'
                ],
                'results' => [
                    'message' => 'API connection test successful',
                    'timestamp' => now()->toISOString(),
                    'server' => 'Velocity Developer Ongkir API',
                    'version' => 'v2.0',
                    'request_ip' => $request->ip(),
                    'api_key_provided' => $request->hasHeader('key'),
                    'api_key_value' => $request->header('key') ? 'SET (' . strlen($request->header('key')) . ' chars)' : 'NOT_SET',
                    'user_agent' => $request->userAgent(),
                    'request_time' => microtime(true),
                    'memory_usage' => memory_get_usage(true),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version()
                ]
            ]
        ]);
    }

    /**
     * Province test endpoint with mock data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function province(Request $request): JsonResponse
    {
        return response()->json([
            'rajaongkir' => [
                'status' => [
                    'code' => 200,
                    'description' => 'Province test endpoint success'
                ],
                'results' => [
                    [
                        'province_id' => '1',
                        'province' => 'Bali'
                    ],
                    [
                        'province_id' => '2', 
                        'province' => 'Bangka Belitung'
                    ],
                    [
                        'province_id' => '3',
                        'province' => 'Banten'
                    ],
                    [
                        'province_id' => '4',
                        'province' => 'Bengkulu'
                    ],
                    [
                        'province_id' => '5',
                        'province' => 'DI Yogyakarta'
                    ],
                    [
                        'province_id' => '6',
                        'province' => 'DKI Jakarta'
                    ],
                    [
                        'province_id' => '7',
                        'province' => 'Gorontalo'
                    ],
                    [
                        'province_id' => '8',
                        'province' => 'Jambi'
                    ],
                    [
                        'province_id' => '9',
                        'province' => 'Jawa Barat'
                    ],
                    [
                        'province_id' => '10',
                        'province' => 'Jawa Tengah'
                    ]
                ]
            ]
        ]);
    }

    /**
     * Comprehensive API test with detailed information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function comprehensive(Request $request): JsonResponse
    {
        // Test database connectivity if available
        $db_status = 'unknown';
        try {
            \DB::connection()->getPdo();
            $db_status = 'connected';
        } catch (\Exception $e) {
            $db_status = 'failed: ' . $e->getMessage();
        }

        // Test cache if available
        $cache_status = 'unknown';
        try {
            \Cache::put('test_key', 'test_value', 60);
            $cache_status = \Cache::get('test_key') === 'test_value' ? 'working' : 'failed';
            \Cache::forget('test_key');
        } catch (\Exception $e) {
            $cache_status = 'failed: ' . $e->getMessage();
        }

        return response()->json([
            'rajaongkir' => [
                'status' => [
                    'code' => 200,
                    'description' => 'Comprehensive test successful'
                ],
                'results' => [
                    'basic_info' => [
                        'message' => 'Comprehensive API test successful',
                        'timestamp' => now()->toISOString(),
                        'server' => 'Velocity Developer Ongkir API',
                        'version' => 'v2.0'
                    ],
                    'request_info' => [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                        'api_key_provided' => $request->hasHeader('key'),
                        'api_key_length' => $request->hasHeader('key') ? strlen($request->header('key')) : 0
                    ],
                    'server_info' => [
                        'php_version' => PHP_VERSION,
                        'laravel_version' => app()->version(),
                        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                        'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                        'execution_time' => microtime(true) - LARAVEL_START
                    ],
                    'services' => [
                        'database' => $db_status,
                        'cache' => $cache_status
                    ],
                    'headers' => $request->headers->all()
                ]
            ]
        ]);
    }
}