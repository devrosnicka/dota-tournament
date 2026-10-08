<?php

use App\Enums\Phase;
use App\Enums\Side;
use App\Enums\TiebreakContext;
use App\Models\Player;
use App\Models\TiebreakOrder;
use App\Tournament\FinalStage;
use App\Tournament\Overall;
use App\Tournament\Standings;
use App\Tournament\TournamentSettings;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Setup: ten players, one 5v5 round won by the group's top five. The draft
| goes by placement, so team A of the final is positions 1, 3, 6, 7, 10:
| positions 1 and 3 bring a group point each. Team A wins the final 2:0,
| so positions 1 and 3 tie for the overall lead on 4 points with equal
| final points: a shootout decides the champion.
*/
beforeEach(function () {
    startFinal();
    completeDraft();
    setPhase(Phase::Final);

    $rows = app(Standings::class)->group()->rows;
    $this->first = $rows[0]->playerId;
    $this->third = $rows[2]->playerId;
});

function winFinal(string ...$winners): void
{
    foreach ($winners as $winner) {
        asAdmin()->post('/admin/final/maps', ['winner' => $winner]);
    }
}

it('adds final points and finds the tie for the champion', function () {
    winFinal('A', 'A');

    $table = app(Overall::class)->table();

    expect($table->rows[0]->total)->toBe(4.0)
        ->and($table->championTie)->toEqualCanonicalizing([$this->first, $this->third])
        ->and($table->needsShootout())->toBeTrue()
        ->and($table->champion)->toBeNull();
});

it('finishes only once the series and the champion are decided', function () {
    asAdmin()->post('/admin/phase/advance');
    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Final);

    winFinal('A', 'A');
    asAdmin()->get('/admin')->assertInertia(fn (Assert $page) => $page
        ->where('blockers', ['Shoda o celkového šampiona čeká na výsledek rozstřelu.']));

    asAdmin()->post('/admin/tiebreaks/champion', ['winner' => $this->third])->assertRedirect('/admin/tiebreaks');
    asAdmin()->post('/admin/phase/advance');

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Finished)
        ->and(app(Overall::class)->table()->champion)->toBe($this->third)
        ->and(TiebreakOrder::orderFor(TiebreakContext::Champion)[0])->toBe($this->third);
});

it('rejects a champion who is not in the tie', function () {
    winFinal('A', 'A');
    $outsider = app(FinalStage::class)->draft()->team(Side::B)[0];

    asAdmin()->post('/admin/tiebreaks/champion', ['winner' => $outsider])->assertSessionHasErrors('winner');
});

it('shows the admin the champion bracket', function () {
    winFinal('A', 'A');

    asAdmin()->get('/admin/tiebreaks')->assertInertia(fn (Assert $page) => $page
        ->has('champion.bracket', 2)
        ->where('champion.resolved', false));
});

it('announces both trophies on the results page and the TV', function () {
    winFinal('A', 'B', 'A');
    asAdmin()->post('/admin/tiebreaks/champion', ['winner' => $this->first]);
    asAdmin()->post('/admin/phase/advance');

    $teamA = app(FinalStage::class)->draft()->team(Side::A);

    $this->actingAs(Player::find($this->third))->get('/results')->assertInertia(fn (Assert $page) => $page
        ->component('results')
        ->where('final', true)
        ->where('overall.champion.id', $this->first)
        ->where('overall.winningTeam', fn ($team) => collect($team)->pluck('id')->sort()->values()->all() === collect($teamA)->sort()->values()->all())
        ->where('overall.rows.0.playerId', $this->first)
        ->where('overall.rows.0.finalPoints', 3)
        ->where('overall.rows.1.position', 2));

    $this->get('/tv?key=tv-key')->assertInertia(fn (Assert $page) => $page
        ->where('overall.champion.id', $this->first)
        ->missing('final'));
});

it('shows no overall standings before the final', function () {
    setPhase(Phase::FinalDraft);

    $this->actingAs(Player::find($this->first))->get('/results')
        ->assertInertia(fn (Assert $page) => $page->where('overall', null));
});
