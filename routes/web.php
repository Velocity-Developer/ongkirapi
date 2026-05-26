<?php

use App\Http\Controllers\PageKodeposController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    echo 'Your IP address is: ' . request()->ip() . ' <br><small>Copyright © ' . date('Y') . ' <a href="https://velocitydeveloper.com">Velocity Developer</a></small>';
    // return Auth::check()
    //     ? redirect('/kodepos')
    //     : view('welcome');
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        return redirect()->intended('/kodepos');
    }

    return back()
        ->withErrors(['email' => 'Email atau password tidak sesuai.'])
        ->onlyInput('email');
})->middleware('guest')->name('login.attempt');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::get('/kodepos', [PageKodeposController::class, 'index']);
Route::put('/kodepos/update/{id}', [PageKodeposController::class, 'update']);
Route::delete('/kodepos/delete/{id}', [PageKodeposController::class, 'delete']);
