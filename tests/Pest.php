<?php

use App\Enums\Phase;
use App\Http\AdminSession;
use App\Tournament\TournamentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the application with a fresh in-memory database. Unit
| tests cover the pure domain services and run without the framework.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => config([
        'tournament.registration_code' => 'lan-party',
        'tournament.admin_password' => 'secret-admin',
        'tournament.tv_key' => 'tv-key',
    ]))
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function asAdmin(): TestCase
{
    return test()->withSession([AdminSession::KEY => true]);
}

function setPhase(Phase $phase): void
{
    app(TournamentSettings::class)->setPhase($phase);
}
