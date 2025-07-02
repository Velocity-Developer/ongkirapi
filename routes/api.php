<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiKeyMiddleware;

use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\CityController;

Route::middleware(['auth:sanctum'])->group(function () {});

Route::middleware([ApiKeyMiddleware::class])->group(function () {
    Route::apiResources([
        'v1/province'   => ProvinceController::class,
        'v1/city'       => CityController::class
    ]);
});
