<?php

use App\Domain\Standings\OverallRow;
use App\Domain\Standings\OverallStandings;

/**
 * @return array<int, array{int, int, bool}> player => [position, total, champion]
 */
function overall(array $group, array $final, ?array $shootout = null): array
{
    $table = (new OverallStandings)->compute($group, $final, $shootout);

    return collect($table->rows)->mapWithKeys(fn (OverallRow $row) => [
        $row->playerId => [$row->position, $row->total, $row->champion],
    ])->all();
}

it('adds final points to group points', function () {
    // 2:1 final: winners get 3, losers 1.
    $rows = overall([1 => 4.0, 2 => 3.5, 3 => 4.5], [1 => 3, 2 => 1]);

    // Players 2 and 3 are level on 4.5 and share 2nd place.
    expect($rows[1])->toBe([1, 7.0, true])
        ->and($rows[3])->toBe([2, 4.5, false])
        ->and($rows[2])->toBe([2, 4.5, false]);
});

it('shares every tie except for the champion', function () {
    $rows = overall([1 => 5.0, 2 => 4.0, 3 => 4.0, 4 => 3.0], []);

    expect($rows[1][0])->toBe(1)
        ->and($rows[2][0])->toBe(2)
        ->and($rows[3][0])->toBe(2)
        ->and($rows[4][0])->toBe(4);
});

it('breaks a tie for first by final points', function () {
    // Both on 7: player 2 got there with more final points.
    $table = (new OverallStandings)->compute([1 => 6.0, 2 => 4.0], [1 => 1, 2 => 3]);

    expect($table->champion)->toBe(2)
        ->and($table->needsShootout())->toBeFalse()
        ->and($table->rows[1]->position)->toBe(2);
});

it('needs a shootout when final points are level too', function () {
    $table = (new OverallStandings)->compute([1 => 4.0, 2 => 4.0, 3 => 4.0, 4 => 1.0], [1 => 3, 2 => 3, 3 => 3]);

    expect($table->champion)->toBeNull()
        ->and($table->needsShootout())->toBeTrue()
        ->and($table->championTie)->toEqualCanonicalizing([1, 2, 3])
        ->and(collect($table->rows)->take(3)->pluck('position')->all())->toBe([1, 1, 1]);
});

it('crowns the shootout winner and shares second place among the rest', function () {
    $table = (new OverallStandings)->compute([1 => 4.0, 2 => 4.0, 3 => 4.0], [1 => 3, 2 => 3, 3 => 3], shootout: [2, 3, 1]);

    expect($table->champion)->toBe(2)
        ->and($table->championTieResolved)->toBeTrue()
        ->and(collect($table->rows)->pluck('position', 'playerId')->all())->toBe([2 => 1, 1 => 2, 3 => 2]);
});

it('ignores a shootout for other players', function () {
    $table = (new OverallStandings)->compute([1 => 4.0, 2 => 4.0], [1 => 3, 2 => 3], shootout: [1, 5]);

    expect($table->needsShootout())->toBeTrue();
});
