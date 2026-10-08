<?php

use App\Enums\Phase;
use App\Models\Player;
use App\Tournament\Rankings;
use App\Tournament\TournamentSettings;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Everyone ranks the others by ascending id, so the seeding follows ids.
 */
function rankByIds(Collection $players): void
{
    setPhase(Phase::Ranking);

    foreach ($players as $rater) {
        app(Rankings::class)->save($rater, $players->pluck('id')->reject(fn ($id) => $id === $rater->id)->values()->all());
    }
}

it('freezes the seeding when the ranking phase closes', function () {
    $players = Player::factory()->count(10)->create();
    rankByIds($players);

    asAdmin()->post('/admin/phase/advance');

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::ScheduleReview)
        ->and($players->map(fn (Player $p) => $p->fresh()->seed_rank)->all())->toBe(range(1, 10))
        ->and($players->first()->fresh()->seed_score)->toBe(1.0);
});

it('needs 10 to 16 active players to close the ranking', function (int $count, bool $allowed) {
    Player::factory()->count($count)->create();
    setPhase(Phase::Ranking);

    asAdmin()->post('/admin/phase/advance');

    expect(app(TournamentSettings::class)->phase())->toBe($allowed ? Phase::ScheduleReview : Phase::Ranking);
})->with([
    [9, false],
    [10, true],
    [16, true],
    [17, false],
]);

it('clears the snapshot when going back to ranking', function () {
    $players = Player::factory()->count(10)->create();
    rankByIds($players);
    asAdmin()->post('/admin/phase/advance');

    asAdmin()->post('/admin/phase/revert');

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Ranking)
        ->and(Player::query()->whereNotNull('seed_rank')->count())->toBe(0);
});

it('shows the admin the live seeding during ranking', function () {
    $players = Player::factory()->count(6)->create();
    rankByIds($players);

    asAdmin()->get('/admin/seeding')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seeding')
            ->where('snapshot', false)
            ->where('submitted', 6)
            ->has('seeding', 6)
            ->where('seeding.0.playerId', $players->first()->id)
            ->where('seeding.0.rank', 1)
            ->where('seeding.0.ratings', 5));
});
