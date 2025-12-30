<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageKodeposController;

Route::get('/', function () {
    return 'Your IP Address: ' . $_SERVER['REMOTE_ADDR'] . '<br><small>Copyright © ' . date('Y') . ' <a href="https://velocitydeveloper.com">Velocity Developer</a></small>';
});

Route::get('/kodepos', [PageKodeposController::class, 'index']);
Route::put('/kodepos/update/{id}', [PageKodeposController::class, 'update']);
Route::delete('/kodepos/delete/{id}', [PageKodeposController::class, 'delete']);
