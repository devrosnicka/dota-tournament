<?php

use App\Enums\Advantage;
use App\Enums\DraftStage;
use App\Enums\Phase;
use App\Enums\Side;
use App\Models\FinalPick;
use App\Models\FinalRole;
use App\Models\Player;
use App\Tournament\FinalStage;
use App\Tournament\Standings;
use App\Tournament\TournamentSettings;
use Inertia\Testing\AssertableInertia as Assert;

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

function finalStage(): FinalStage
{
    return app(FinalStage::class);
}

function playerById(int $id): Player
{
    return Player::findOrFail($id);
}

beforeEach(function () {
    startFinal();
    $this->table = app(Standings::class)->group();
    $this->first = $this->table->rows[0]->playerId;
    $this->second = $this->table->rows[1]->playerId;
});

it('starts the draft with the top two as captains', function () {
    $setup = finalStage()->setup();

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::FinalDraft)
        ->and($setup->captain1_id)->toBe($this->first)
        ->and($setup->captain2_id)->toBe($this->second)
        ->and($setup->finalists)->toHaveCount(10)
        ->and(finalStage()->draft()->stage())->toBe(DraftStage::Advantage);
});

it('lets only the first captain choose the advantage', function () {
    $this->actingAs(playerById($this->second))
        ->post('/final/advantage', ['advantage' => 'player_pick'])
        ->assertSessionHasErrors('draft');

    $this->actingAs(playerById($this->first))
        ->post('/final/advantage', ['advantage' => 'side_pick'])
        ->assertRedirect('/final');

    expect(finalStage()->setup()->advantage_choice)->toBe(Advantage::SidePick)
        // Captain 2 got the first player pick.
        ->and(finalStage()->draft()->pickingSide())->toBe(Side::B);
});

it('lets only the captain on turn pick, in snake order', function () {
    finalStage()->chooseAdvantage(Advantage::PlayerPick, null);
    $pool = finalStage()->draft()->available();

    $this->actingAs(playerById($this->second))
        ->post('/final/pick', ['player' => $pool[0]])
        ->assertSessionHasErrors('draft');

    $captains = [Side::A->value => $this->first, Side::B->value => $this->second];

    foreach (finalStage()->draft()->pickOrder() as $index => $side) {
        $this->actingAs(playerById($captains[$side->value]))
            ->post('/final/pick', ['player' => $pool[$index]])
            ->assertSessionHasNoErrors();
    }

    expect(FinalPick::query()->count())->toBe(8)
        ->and(finalStage()->draft()->stage())->toBe(DraftStage::Roles)
        ->and(finalStage()->draft()->team(Side::A))->toHaveCount(5);
});

it('refuses a player who was already picked', function () {
    finalStage()->chooseAdvantage(Advantage::PlayerPick, null);
    $pool = finalStage()->draft()->available();
    finalStage()->pick($pool[0], null);

    $this->actingAs(playerById($this->second))
        ->post('/final/pick', ['player' => $pool[0]])
        ->assertSessionHasErrors('draft');

    expect(FinalPick::query()->count())->toBe(1);
});

it('lets players choose roles in placement order, each role once per team', function () {
    finalStage()->chooseAdvantage(Advantage::PlayerPick, null);

    foreach (finalStage()->draft()->available() as $player) {
        finalStage()->pick($player, null);
    }

    $order = finalStage()->draft()->roleOrder(Side::A);

    // The second in line cannot choose before the first.
    $this->actingAs(playerById($order[1]))->post('/final/role', ['role' => 1])->assertSessionHasErrors('draft');

    $this->actingAs(playerById($order[0]))->post('/final/role', ['role' => 1])->assertSessionHasNoErrors();
    $this->actingAs(playerById($order[1]))->post('/final/role', ['role' => 1])->assertSessionHasErrors('draft');
    $this->actingAs(playerById($order[1]))->post('/final/role', ['role' => 2])->assertSessionHasNoErrors();

    expect(FinalRole::query()->pluck('position', 'player_id')->all())->toBe([$order[0] => 1, $order[1] => 2]);
});

it('lets the admin take back moves one by one', function () {
    finalStage()->chooseAdvantage(Advantage::PlayerPick, null);
    $pool = finalStage()->draft()->available();
    finalStage()->pick($pool[0], null);

    asAdmin()->post('/admin/final/undo');
    expect(FinalPick::query()->count())->toBe(0);

    asAdmin()->post('/admin/final/undo');
    expect(finalStage()->setup()->advantage_choice)->toBeNull();
});

it('needs a finished draft for the final and a decided series to finish', function () {
    asAdmin()->post('/admin/phase/advance');
    expect(app(TournamentSettings::class)->phase())->toBe(Phase::FinalDraft);

    completeDraft();
    asAdmin()->post('/admin/phase/advance');
    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Final);

    asAdmin()->post('/admin/final/maps', ['winner' => 'A', 'kills_a' => 30, 'kills_b' => 20, 'radiant' => 'A', 'first_pick' => 'B']);
    asAdmin()->post('/admin/phase/advance');
    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Final);

    asAdmin()->post('/admin/final/maps', ['winner' => 'A']);
    asAdmin()->post('/admin/final/maps', ['winner' => 'B'])->assertSessionHasErrors('winner');

    expect(finalStage()->series()->winner())->toBe(Side::A)
        ->and(finalStage()->maps())->toHaveCount(2)
        ->and(finalStage()->maps()->first()->radiant_side)->toBe(Side::A);
});

it('gives 3 and 1 final points for a 2:1 series', function () {
    completeDraft();
    setPhase(Phase::Final);

    foreach (['B', 'A', 'A'] as $winner) {
        asAdmin()->post('/admin/final/maps', ['winner' => $winner]);
    }

    $points = finalStage()->points();
    $draft = finalStage()->draft();

    expect($points[$draft->captainA])->toBe(3)
        ->and($points[$draft->captainB])->toBe(1)
        ->and($points)->toHaveCount(10);
});

it('shows the draft to players and on the TV', function () {
    finalStage()->chooseAdvantage(Advantage::PlayerPick, null);

    $this->actingAs(playerById($this->first))->get('/final')->assertInertia(fn (Assert $page) => $page
        ->component('final')
        ->where('final.stage', 'picks')
        ->where('final.you.canPick', true)
        ->has('final.pool', 8));

    $this->get('/tv?key=tv-key')->assertInertia(fn (Assert $page) => $page
        ->where('final.pickingSide', 'A')
        ->where('final.you.canPick', false));
});

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
