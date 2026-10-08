<?php

use App\Enums\Phase;
use App\Enums\PlayerStatus;
use App\Enums\Side;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use App\Tournament\Results;
use App\Tournament\Schedule;
use App\Tournament\Standings;

/**
 * Composition of a round: sitters and teams, comparable across reloads.
 *
 * @return array{sitters: list<int>, matches: list<array{0: list<int>, 1: list<int>}>}
 */
function snapshotRound(int $number): array
{
    $round = Round::query()->where('number', $number)->with(['matches.players', 'sitters'])->first();

    return [
        'sitters' => $round->sitters->pluck('id')->sort()->values()->all(),
        'matches' => $round->matches->map(fn (GameMatch $m) => [
            collect($m->team(Side::A))->sort()->values()->all(),
            collect($m->team(Side::B))->sort()->values()->all(),
        ])->all(),
    ];
}

beforeEach(function () {
    $this->players = seededTournament(12, 5);
    setPhase(Phase::GroupStage);
});

it('regenerates only the rounds without results and changes the format', function () {
    playRounds([1, 2]);
    $before = [1 => snapshotRound(1), 2 => snapshotRound(2)];
    $leaver = $this->players[3];

    asAdmin()->post("/admin/players/{$leaver->id}/withdraw", ['round' => 3])->assertRedirect('/admin/players');

    expect($leaver->fresh()->status)->toBe(PlayerStatus::Withdrawn)
        ->and($leaver->fresh()->withdrawn_from_round)->toBe(3)
        ->and(snapshotRound(1))->toBe($before[1])
        ->and(snapshotRound(2))->toBe($before[2]);

    $rounds = app(Schedule::class)->rounds();

    expect($rounds->pluck('number')->all())->toBe([1, 2, 3, 4, 5])
        ->and($rounds->where('number', '<=', 2)->pluck('format')->unique()->all())->toBe(['3v3'])
        ->and($rounds->where('number', '>=', 3)->pluck('format')->unique()->values()->all())->toBe(['5v5']);

    // 11 players from round 3 on: one sitter per round, never the leaver,
    // and every remaining player plays or sits exactly once per round.
    $remaining = $this->players->reject(fn ($p) => $p->is($leaver))->pluck('id')->sort()->values()->all();

    foreach ([3, 4, 5] as $number) {
        $round = snapshotRound($number);
        $everyone = [...$round['sitters'], ...collect($round['matches'])->flatten()->all()];
        sort($everyone);

        expect($round['sitters'])->toHaveCount(1)
            ->and($everyone)->toBe($remaining);
    }
});

it('keeps the sit counts even across the regenerated rounds', function () {
    playRounds([1]);
    asAdmin()->post("/admin/players/{$this->players[0]->id}/withdraw", ['round' => 2]);

    $sits = [];

    foreach (app(Schedule::class)->rounds() as $round) {
        foreach ($round->sitters as $sitter) {
            $sits[$sitter->id] = ($sits[$sitter->id] ?? 0) + 1;
        }
    }

    // 11 players, 4 sitters over rounds 2-5: nobody sits twice.
    expect(max($sits))->toBe(1)->and(array_sum($sits))->toBe(4);
});

it('refuses a round that already has a result', function () {
    $match = Round::query()->where('number', 3)->first()->matches()->first();
    app(Results::class)->report($match, Side::A, 1, 0, null);

    asAdmin()->post("/admin/players/{$this->players[0]->id}/withdraw", ['round' => 2])->assertSessionHasErrors('round');
    asAdmin()->post("/admin/players/{$this->players[0]->id}/withdraw", ['round' => 3])->assertSessionHasErrors('round');

    expect($this->players[0]->fresh()->status)->toBe(PlayerStatus::Active);
});

it('refuses to go below ten players', function () {
    asAdmin()->post("/admin/players/{$this->players[0]->id}/withdraw", ['round' => 1]);
    asAdmin()->post("/admin/players/{$this->players[1]->id}/withdraw", ['round' => 1]);
    asAdmin()->post("/admin/players/{$this->players[2]->id}/withdraw", ['round' => 1])->assertSessionHasErrors('round');

    expect(Player::query()->active()->count())->toBe(10);
});

it('can withdraw after the last round without regenerating', function () {
    playRounds();
    $before = snapshotRound(5);

    asAdmin()->post("/admin/players/{$this->players[0]->id}/withdraw", ['round' => 6])->assertRedirect();

    expect($this->players[0]->fresh()->status)->toBe(PlayerStatus::Withdrawn)
        ->and(snapshotRound(5))->toBe($before);
});

it('keeps the points of a withdrawn player but not the final', function () {
    playRounds([1, 2], fn () => [Side::A, 20, 10]);
    $winner = Round::query()->where('number', 1)->with('matches.players')->first()->matches->first()->team(Side::A)[0];
    $points = app(Standings::class)->group()->row($winner)->points;

    asAdmin()->post("/admin/players/{$winner}/withdraw", ['round' => 3]);
    app(Standings::class)->forget();
    $row = app(Standings::class)->group()->row($winner);

    expect($row->points)->toBe($points)
        ->and($row->position)->toBeNull()
        ->and($row->qualified)->toBeFalse()
        ->and(app(Standings::class)->group()->qualified())->not->toContain($winner);
});
