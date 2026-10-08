<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\HowItWorksController;
use App\Http\Controllers\Player;
use App\Http\Controllers\TvController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureTvKey;
use Illuminate\Support\Facades\Route;

/*
| Guests see only registration, login, the tournament flow and the TV view
| (SPEC §2.8).
*/
Route::get('tv', TvController::class)->middleware(EnsureTvKey::class)->name('tv');
Route::get('how-it-works', HowItWorksController::class)->name('how-it-works');

Route::middleware('guest')->group(function () {
    Route::get('register', [Player\RegisterController::class, 'create'])->name('register');
    Route::post('register', [Player\RegisterController::class, 'store'])->name('register.store');
    Route::get('login', [Player\LoginController::class, 'create'])->name('login');
    Route::post('login', [Player\LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', Player\HomeController::class)->name('home');
    Route::get('rules', Player\RulesController::class)->name('rules');
    Route::get('device', [Player\DeviceLoginController::class, 'show'])->name('device');
    Route::post('device', [Player\DeviceLoginController::class, 'store'])->name('device.store');
    Route::post('logout', [Player\LoginController::class, 'destroy'])->name('logout');
    Route::get('ranking', [Player\RankingController::class, 'show'])->name('ranking');
    Route::post('ranking', [Player\RankingController::class, 'store'])->name('ranking.store');
    Route::get('schedule', Player\ScheduleController::class)->name('schedule');
    Route::get('standings', Player\StandingsController::class)->name('standings');
    Route::get('matches/{match}', [Player\MatchController::class, 'show'])->name('matches.show');
    Route::post('matches/{match}/result', [Player\MatchController::class, 'report'])->name('matches.report');

    Route::get('results', Player\ResultsController::class)->name('results');
    Route::get('final', [Player\FinalController::class, 'show'])->name('final');
    Route::post('final/advantage', [Player\FinalController::class, 'advantage'])->name('final.advantage');
    Route::post('final/pick', [Player\FinalController::class, 'pick'])->name('final.pick');
    Route::post('final/role', [Player\FinalController::class, 'role'])->name('final.role');
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
        Route::post('players/{player}/withdraw', [Admin\WithdrawalController::class, 'store'])->name('players.withdraw');

        Route::get('seeding', Admin\SeedingController::class)->name('seeding');

        Route::get('schedule', [Admin\ScheduleController::class, 'index'])->name('schedule');
        Route::post('schedule/generate', [Admin\ScheduleController::class, 'generate'])->name('schedule.generate');
        Route::post('schedule/rounds/{round}/swap', [Admin\ScheduleController::class, 'swap'])->name('schedule.swap');

        Route::get('results', [Admin\ResultsController::class, 'index'])->name('results');
        Route::post('matches/{match}/result', [Admin\ResultsController::class, 'report'])->name('matches.report');
        Route::post('rounds/{round}/close', [Admin\ResultsController::class, 'close'])->name('rounds.close');
        Route::post('rounds/{round}/reopen', [Admin\ResultsController::class, 'reopen'])->name('rounds.reopen');

        Route::get('tiebreaks', [Admin\TiebreakController::class, 'index'])->name('tiebreaks');
        Route::post('tiebreaks/qualification', [Admin\TiebreakController::class, 'storeQualification'])->name('tiebreaks.qualification');
        Route::post('tiebreaks/champion', [Admin\TiebreakController::class, 'storeChampion'])->name('tiebreaks.champion');

        Route::get('final', [Admin\FinalController::class, 'index'])->name('final');
        Route::post('final/advantage', [Admin\FinalController::class, 'advantage'])->name('final.advantage');
        Route::post('final/pick', [Admin\FinalController::class, 'pick'])->name('final.pick');
        Route::post('final/role', [Admin\FinalController::class, 'role'])->name('final.role');
        Route::post('final/undo', [Admin\FinalController::class, 'undo'])->name('final.undo');
        Route::post('final/maps', [Admin\FinalController::class, 'storeMap'])->name('final.maps.store');
        Route::delete('final/maps/last', [Admin\FinalController::class, 'destroyMap'])->name('final.maps.destroy');
    });
});
