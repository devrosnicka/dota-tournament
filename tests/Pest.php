<?php

use App\Enums\Phase;
use App\Http\AdminSession;
use App\Models\Player;
use App\Tournament\Schedule;
use App\Tournament\TournamentSettings;
use Illuminate\Database\Eloquent\Collection;
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
        // Feature tests need a valid schedule, not the best one.
        'tournament.generator.samples_per_round' => 100,
        'tournament.generator.restarts' => 2,
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

/**
 * Players with a frozen seeding (seed rank = order of creation) and a
 * generated schedule, ready to be published.
 *
 * @return Collection<int, Player>
 */
function seededTournament(int $players = 12, int $rounds = 5): Collection
{
    $created = Player::factory()->count($players)->create();

    foreach ($created as $index => $player) {
        $player->forceFill(['seed_rank' => $index + 1, 'seed_score' => $index + 1.0])->save();
    }

    setPhase(Phase::ScheduleReview);
    app(Schedule::class)->generate($rounds);

    return $created;
}
