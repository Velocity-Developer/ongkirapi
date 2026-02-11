<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiKeyMiddleware;

use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\SubdistrictController;
use App\Http\Controllers\CostController;
use App\Http\Controllers\RajaOngkirAwbController;
use App\Http\Controllers\TestController;

// V2 Controllers - Full Relay to RajaOngkir
use App\Http\Controllers\V2\ProvinceController as V2ProvinceController;
use App\Http\Controllers\V2\CityController as V2CityController;
use App\Http\Controllers\V2\DistrictController as V2DistrictController;
use App\Http\Controllers\V2\SubdistrictController as V2SubdistrictController;
use App\Http\Controllers\V2\CostController as V2CostController;

// V3 Controllers - Clean Schema
use App\Http\Controllers\V3\ProvinceController as V3ProvinceController;
use App\Http\Controllers\V3\CityController as V3CityController;
use App\Http\Controllers\V3\DistrictController as V3DistrictController;
use App\Http\Controllers\V3\SubdistrictController as V3SubdistrictController;

use App\Http\Controllers\V3\V3CostController;

Route::middleware(['auth:sanctum'])->group(function () {});

Route::middleware([ApiKeyMiddleware::class])->group(function () {
    // Test endpoints for debugging API connectivity
    Route::get('/v2/test', [TestController::class, 'basic']);
    Route::get('/v2/test/comprehensive', [TestController::class, 'comprehensive']);
    Route::get('/v2/province/test', [TestController::class, 'province']);

    // V1 API - Legacy Database
    Route::apiResources([
        'v1/province'       => ProvinceController::class,
        'v1/city'           => CityController::class,
        'v1/subdistrict'    => SubdistrictController::class,
    ]);

    // V2 API - Full Relay to RajaOngkir
    Route::apiResources([
        'v2/province'       => V2ProvinceController::class,
        'v2/city'           => V2CityController::class,
        'v2/district'       => V2DistrictController::class,
        'v2/subdistrict'    => V2SubdistrictController::class,
    ]);

    // V3 API - Clean Schema
    Route::apiResources([
        'v3/destination/province'       => V3ProvinceController::class,
        'v3/destination/city'           => V3CityController::class,
        'v3/destination/district'       => V3DistrictController::class,
        'v3/destination/subdistrict'    => V3SubdistrictController::class,
    ]);

    Route::post('/v3/calculate/domestic-cost', [V3CostController::class, 'index']);

    Route::post('/v1/cost', [CostController::class, 'index']);
    Route::post('/v2/cost', [V2CostController::class, 'index']);
    Route::post('/v3/calculate/domestic-cost', [V3CostController::class, 'index']);
    Route::post('/v1/waybill', [RajaOngkirAwbController::class, 'index']);
    Route::post('/v3/waybill', [RajaOngkirAwbController::class, 'index']);
});
