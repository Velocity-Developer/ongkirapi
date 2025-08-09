<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiKeyMiddleware;

use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\SubdistrictController;
use App\Http\Controllers\CostController;
use App\Http\Controllers\RajaOngkirAwbController;

// V2 Controllers - Full Relay to RajaOngkir
use App\Http\Controllers\V2\ProvinceController as V2ProvinceController;
use App\Http\Controllers\V2\CityController as V2CityController;
use App\Http\Controllers\V2\DistrictController as V2DistrictController;
use App\Http\Controllers\V2\SubdistrictController as V2SubdistrictController;


Route::middleware(['auth:sanctum'])->group(function () {});

Route::middleware([ApiKeyMiddleware::class])->group(function () {
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
    
    Route::post('/v1/cost', [CostController::class, 'index']);
    Route::post('/v1/waybill', [RajaOngkirAwbController::class, 'index']);
});
