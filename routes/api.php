<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProvinceController;

Route::middleware(['auth:sanctum'])->group(function () {});

Route::apiResources([
    'province'      => ProvinceController::class,
]);
