<?php

use App\Domain\Standings\GroupStandings;
use App\Domain\Standings\MatchResult;
use App\Domain\Standings\StandingPlayer;
use App\Domain\Standings\StandingRow;
use App\Enums\Side;

/**
 * Players 1..n seeded by id.
 *
 * @return list<StandingPlayer>
 */
function standingPlayers(int $count, array $withdrawn = []): array
{
    return array_map(fn (int $id) => new StandingPlayer($id, $id, ! in_array($id, $withdrawn, true)), range(1, $count));
}

/**
 * @return list<int>
 */
function order(array $rows): array
{
    return array_map(fn (StandingRow $row) => $row->playerId, $rows);
}

it('scores wins, losses, sits and kill difference', function () {
    $table = (new GroupStandings)->compute(standingPlayers(11), [
        new MatchResult([1, 2, 3, 4, 5], [6, 7, 8, 9, 10], Side::A, 40, 25),
        new MatchResult([1, 6, 7, 8, 11], [2, 3, 4, 5, 9], Side::B, 30, 31),
    ], [11 => 1, 10 => 1]);

    $one = $table->row(1);
    $ten = $table->row(10);
    $eleven = $table->row(11);

    expect([$one->points, $one->wins, $one->losses, $one->killDiff])->toBe([1.0, 1, 1, 14])
        ->and([$ten->points, $ten->wins, $ten->losses, $ten->sits, $ten->killDiff])->toBe([0.5, 0, 1, 1, -15])
        ->and([$eleven->points, $eleven->killDiff])->toBe([0.5, -1]);
});

it('orders by points, kill difference and then the seeding', function () {
    $table = (new GroupStandings)->compute(standingPlayers(10), [
        new MatchResult([6, 7, 8, 9, 10], [1, 2, 3, 4, 5], Side::A, 30, 20),
        new MatchResult([2, 4, 6, 8, 10], [1, 3, 5, 7, 9], Side::A, 25, 20),
    ], []);

    // 6, 8, 10: 2 wins, +15. 2, 4: 1 win, -5; 7, 9: 1 win, +5. 1, 3, 5: 0 wins, -15.
    expect(order($table->rows))->toBe([6, 8, 10, 7, 9, 2, 4, 1, 3, 5])
        ->and($table->rows[0]->position)->toBe(1)
        ->and($table->qualificationTie)->toBeNull();
});

it('decides a tie for the captains by the seeding, without a shootout', function () {
    // Nobody played: everyone is level, the seeding decides everything.
    $table = (new GroupStandings)->compute(standingPlayers(10), [], []);

    expect(order($table->rows))->toBe(range(1, 10))
        ->and($table->needsShootout())->toBeFalse();
});

it('detects a tie across the qualification line', function () {
    // 12 players, nobody played: all level. Positions 1-10 qualify.
    $table = (new GroupStandings)->compute(standingPlayers(12), [], []);

    expect($table->needsShootout())->toBeTrue()
        ->and($table->qualificationTie->players)->toBe(range(1, 12))
        ->and($table->qualificationTie->spots)->toBe(10);
});

it('limits the tie group to players level with the 10th', function () {
    $results = [
        // 1-6 win twice, 7-12 lose twice except 7, 8, 9 who also beat 10-12 once.
        new MatchResult([1, 2, 3], [7, 8, 9], Side::A, 10, 5),
        new MatchResult([4, 5, 6], [10, 11, 12], Side::A, 10, 5),
        new MatchResult([1, 2, 3], [10, 11, 12], Side::A, 10, 5),
        new MatchResult([4, 5, 6], [7, 8, 9], Side::A, 10, 5),
        new MatchResult([7, 8, 9], [10, 11, 12], Side::A, 10, 10),
    ];

    $table = (new GroupStandings)->compute(standingPlayers(12), $results, []);

    // 7-9: 1 win, -10. 10-12: 0 wins, -10. 10th place is player 10.
    expect(order($table->rows))->toBe(range(1, 12))
        ->and($table->qualificationTie->players)->toBe([10, 11, 12])
        ->and($table->qualificationTie->spots)->toBe(1);
});

it('applies a shootout result to who qualifies, keeping the seed order', function () {
    $table = (new GroupStandings)->compute(standingPlayers(12), [], [], shootout: [12, 3, 5, 1, 2, 4, 6, 7, 8, 9, 10, 11]);

    // Ten winners qualify; 10 and 11 lost the shootout.
    expect($table->needsShootout())->toBeFalse()
        ->and($table->qualificationTie->resolved)->toBeTrue()
        ->and(order($table->rows))->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9, 12, 10, 11])
        ->and($table->qualified())->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9, 12]);
});

it('ignores a shootout result for a different group of players', function () {
    $table = (new GroupStandings)->compute(standingPlayers(12), [], [], shootout: [12, 11]);

    expect($table->needsShootout())->toBeTrue();
});

it('keeps withdrawn players with their points but without a position', function () {
    $table = (new GroupStandings)->compute(standingPlayers(12, withdrawn: [1, 2]), [
        new MatchResult([1, 2, 3], [4, 5, 6], Side::A, 10, 5),
    ], []);

    $last = array_slice($table->rows, -2);

    expect(order($last))->toBe([1, 2])
        ->and($last[0]->position)->toBeNull()
        ->and($last[0]->points)->toBe(1.0)
        ->and($last[0]->qualified)->toBeFalse()
        ->and($table->rows[0]->playerId)->toBe(3)
        ->and($table->qualified())->toHaveCount(10)
        // Exactly ten active players: everyone qualifies, no shootout.
        ->and($table->qualificationTie)->toBeNull();
});
