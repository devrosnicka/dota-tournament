<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Player;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

/*
| Guests see only registration, login and the TV view (SPEC §2.8).
*/
Route::middleware('guest')->group(function () {
    Route::get('register', [Player\RegisterController::class, 'create'])->name('register');
    Route::post('register', [Player\RegisterController::class, 'store'])->name('register.store');
    Route::get('login', [Player\LoginController::class, 'create'])->name('login');
    Route::post('login', [Player\LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', Player\HomeController::class)->name('home');
    Route::get('device', [Player\DeviceLoginController::class, 'show'])->name('device');
    Route::post('device', [Player\DeviceLoginController::class, 'store'])->name('device.store');
    Route::post('logout', [Player\LoginController::class, 'destroy'])->name('logout');
    Route::get('ranking', [Player\RankingController::class, 'show'])->name('ranking');
    Route::post('ranking', [Player\RankingController::class, 'store'])->name('ranking.store');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [Admin\AuthController::class, 'create'])->name('login');
    Route::post('login', [Admin\AuthController::class, 'store'])->name('login.store');

    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'destroy'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::post('phase/advance', [Admin\PhaseController::class, 'advance'])->name('phase.advance');
        Route::post('phase/revert', [Admin\PhaseController::class, 'revert'])->name('phase.revert');

        Route::get('players', [Admin\PlayerController::class, 'index'])->name('players.index');
        Route::patch('players/{player}', [Admin\PlayerController::class, 'update'])->name('players.update');
        Route::delete('players/{player}', [Admin\PlayerController::class, 'destroy'])->name('players.destroy');
        Route::post('players/{player}/login-code', [Admin\PlayerController::class, 'loginCode'])->name('players.login-code');

        Route::get('seeding', Admin\SeedingController::class)->name('seeding');
    });
});
