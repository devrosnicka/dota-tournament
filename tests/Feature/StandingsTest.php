<?php

use App\Enums\Phase;
use App\Enums\Side;
use App\Models\GameMatch;
use App\Models\Round;
use App\Models\TiebreakOrder;
use App\Tournament\Standings;
use App\Tournament\TournamentSettings;
use Inertia\Testing\AssertableInertia as Assert;

it('counts points from results and sits of started rounds', function () {
    seededTournament(13, 2);
    setPhase(Phase::GroupStage);
    playRounds([1], fn (GameMatch $match) => [Side::A, 25, 20]);

    $round = Round::query()->where('number', 1)->with(['matches.players', 'sitters'])->first();
    $winner = $round->matches->first()->team(Side::A)[0];
    $loser = $round->matches->first()->team(Side::B)[0];
    $sitter = $round->sitters->first()->id;
    $secondRoundSitter = Round::query()->where('number', 2)->first()->sitters()->first()->id;
    $table = app(Standings::class)->group();

    expect($table->row($winner)->points)->toBe(1.0)
        ->and($table->row($winner)->killDiff)->toBe(5)
        ->and($table->row($loser)->points)->toBe(0.0)
        ->and($table->row($sitter)->points)->toBe(0.5)
        // Round 2 has not started, so its sitter has nothing yet.
        ->and($table->row($secondRoundSitter)->sits)->toBe(0);
});

it('shows the table to players from the group stage on', function () {
    $players = seededTournament(12, 2);

    $this->actingAs($players[0])->get('/standings')
        ->assertInertia(fn (Assert $page) => $page->component('standings')->where('standings', null));

    setPhase(Phase::GroupStage);

    $this->actingAs($players[0])->get('/standings')
        ->assertInertia(fn (Assert $page) => $page
            ->has('standings.rows', 12)
            ->where('standings.rows.0.position', 1)
            ->missing('standings.rows.0.seed'));
});

it('blocks the final draft until all rounds are closed and the shootout is decided', function () {
    seededTournament(12, 1);
    setPhase(Phase::GroupStage);

    asAdmin()->post('/admin/phase/advance');
    expect(app(TournamentSettings::class)->phase())->toBe(Phase::GroupStage);

    // One round, both matches won by side A 10:10: six players have a win,
    // six are level without one, so places 7-12 tie across the line.
    playRounds(null, fn () => [Side::A, 10, 10]);
    $tie = app(Standings::class)->group()->qualificationTie;

    expect($tie->players)->toHaveCount(6)
        ->and($tie->spots)->toBe(4)
        ->and(app(Standings::class)->group()->needsShootout())->toBeTrue();

    asAdmin()->get('/admin')->assertInertia(fn (Assert $page) => $page
        ->where('blockers', ['Shoda na hranici postupu čeká na výsledek rozstřelu.']));

    asAdmin()->post('/admin/tiebreaks/qualification', ['order' => array_reverse($tie->players)])
        ->assertRedirect('/admin/tiebreaks');

    expect(TiebreakOrder::query()->count())->toBe(1);

    asAdmin()->get('/admin')->assertInertia(fn (Assert $page) => $page
        ->where('blockers', fn ($blockers) => ! collect($blockers)->contains(fn ($b) => str_contains($b, 'rozstřel'))));
});

it('rejects a shootout order with other players than the tied ones', function () {
    $players = seededTournament(12, 1);
    setPhase(Phase::GroupStage);
    playRounds(null, fn () => [Side::A, 10, 10]);

    asAdmin()->post('/admin/tiebreaks/qualification', ['order' => [$players[0]->id]])
        ->assertSessionHasErrors('order');

    expect(TiebreakOrder::query()->count())->toBe(0);
});

it('lists withdrawn players without a position', function () {
    $players = seededTournament(12, 1);
    setPhase(Phase::GroupStage);
    $players[0]->forceFill(['status' => 'withdrawn', 'withdrawn_from_round' => 2])->save();

    $rows = collect(app(Standings::class)->group()->rows);

    expect($rows->last()->playerId)->toBe($players[0]->id)
        ->and($rows->last()->position)->toBeNull()
        ->and($rows->where('active', true))->toHaveCount(11);
});
