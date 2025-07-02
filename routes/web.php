<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Your IP Address: ' . $_SERVER['REMOTE_ADDR'] . '<br><small>Copyright © ' . date('Y') . ' <a href="https://velocitydeveloper.com">Velocity Developer</a></small>';
});
