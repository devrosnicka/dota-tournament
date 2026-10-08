<?php

use App\Domain\Final\FinalDraft;
use App\Enums\Advantage;
use App\Enums\DraftStage;
use App\Enums\Side;

/**
 * Finalists 1..10 placed by id; 1 and 2 are the captains.
 */
function draft(?Advantage $advantage = null): FinalDraft
{
    return new FinalDraft(1, 2, range(3, 10), array_combine(range(1, 10), range(1, 10)), $advantage);
}

function pickAll(FinalDraft $draft): FinalDraft
{
    foreach (range(3, 10) as $player) {
        $draft = $draft->withPick($player);
    }

    return $draft;
}

it('waits for the advantage choice first', function () {
    expect(draft()->stage())->toBe(DraftStage::Advantage)
        ->and(draft()->pickingSide())->toBeNull()
        ->and(fn () => draft()->withPick(3))->toThrow(InvalidArgumentException::class);
});

it('picks in snake order starting with the player pick advantage', function () {
    $sides = fn (FinalDraft $d) => array_map(fn (Side $s) => $s->value, $d->pickOrder());

    expect($sides(draft(Advantage::PlayerPick)))->toBe(['A', 'B', 'B', 'A', 'A', 'B', 'B', 'A'])
        // Captain 1 took the side pick, so captain 2 picks players first.
        ->and($sides(draft(Advantage::SidePick)))->toBe(['B', 'A', 'A', 'B', 'B', 'A', 'A', 'B'])
        ->and(draft(Advantage::SidePick)->firstPickSide())->toBe(Side::B);
});

it('builds two teams of five', function () {
    $draft = pickAll(draft(Advantage::PlayerPick));

    // A picks 3, 6, 7, 10; B picks 4, 5, 8, 9.
    expect($draft->team(Side::A))->toBe([1, 3, 6, 7, 10])
        ->and($draft->team(Side::B))->toBe([2, 4, 5, 8, 9])
        ->and($draft->stage())->toBe(DraftStage::Roles)
        ->and($draft->available())->toBe([]);
});

it('does not pick a player twice or a captain', function (int $player) {
    draft(Advantage::PlayerPick)->withPick(3)->withPick($player);
})->with([3, 1, 2, 11])->throws(InvalidArgumentException::class);

it('lets players choose roles in order of their placement', function () {
    $draft = pickAll(draft(Advantage::PlayerPick));

    expect($draft->roleOrder(Side::A))->toBe([1, 3, 6, 7, 10])
        ->and($draft->roleTurn(Side::A))->toBe(1)
        ->and($draft->roleTurn(Side::B))->toBe(2);

    $draft = $draft->withRole(1, 2)->withRole(2, 2);

    expect($draft->roleTurn(Side::A))->toBe(3)
        ->and($draft->freeRoles(Side::A))->toBe([1, 3, 4, 5])
        // The other team's choices do not take roles away.
        ->and($draft->freeRoles(Side::B))->toBe([1, 3, 4, 5]);
});

it('keeps every role unique within a team', function () {
    pickAll(draft(Advantage::PlayerPick))->withRole(1, 2)->withRole(3, 2);
})->throws(InvalidArgumentException::class);

it('does not let a player choose out of turn', function () {
    pickAll(draft(Advantage::PlayerPick))->withRole(3, 1);
})->throws(InvalidArgumentException::class);

it('finishes when all ten have a role', function () {
    $draft = pickAll(draft(Advantage::PlayerPick));

    foreach ([Side::A, Side::B] as $side) {
        foreach ($draft->roleOrder($side) as $index => $player) {
            $draft = $draft->withRole($player, $index + 1);
        }
    }

    expect($draft->stage())->toBe(DraftStage::Done)
        ->and($draft->roleTurn(Side::A))->toBeNull();
});
