<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiKeyMiddleware;

use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\SubdistrictController;
use App\Http\Controllers\CostController;


Route::middleware(['auth:sanctum'])->group(function () {});

Route::middleware([ApiKeyMiddleware::class])->group(function () {
    Route::apiResources([
        'v1/province'       => ProvinceController::class,
        'v1/city'           => CityController::class,
        'v1/subdistrict'    => SubdistrictController::class,
    ]);
    Route::post('/v1/cost', [CostController::class, 'index']);
});
