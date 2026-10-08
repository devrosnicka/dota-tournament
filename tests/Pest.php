<?php

use App\Enums\Advantage;
use App\Enums\Phase;
use App\Enums\Side;
use App\Http\AdminSession;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use App\Tournament\FinalStage;
use App\Tournament\Results;
use App\Tournament\Schedule;
use App\Tournament\Standings;
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

/**
 * Report every match of the given rounds (all by default) and close them.
 *
 * @param  (Closure(GameMatch): array{0: Side, 1: int, 2: int})|null  $result  winner and kills per match
 */
function playRounds(?array $numbers = null, ?Closure $result = null): void
{
    $rounds = Round::query()->orderBy('number')->with('matches')->get()
        ->filter(fn (Round $round) => $numbers === null || in_array($round->number, $numbers, true));

    foreach ($rounds as $round) {
        foreach ($round->matches as $match) {
            [$winner, $killsA, $killsB] = $result ? $result($match) : [Side::A, 20, 10];
            app(Results::class)->report($match, $winner, $killsA, $killsB, null);
        }

        app(Results::class)->close($round);
    }

    app(Standings::class)->forget();
}

/**
 * Ten players, one 5v5 round won by side A, final draft started.
 */
function startFinal(): void
{
    seededTournament(10, 1);
    setPhase(Phase::GroupStage);
    playRounds(null, fn () => [Side::A, 30, 10]);
    asAdmin()->post('/admin/phase/advance');
}

function completeDraft(): void
{
    $final = app(FinalStage::class);

    if ($final->draft()->advantage === null) {
        $final->chooseAdvantage(Advantage::PlayerPick, null);
    }

    foreach ($final->draft()->available() as $player) {
        $final->pick($player, null);
    }

    foreach ([Side::A, Side::B] as $side) {
        foreach ($final->draft()->roleOrder($side) as $index => $player) {
            $final->chooseRole($player, $index + 1, null);
        }
    }
}
