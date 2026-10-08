<?php

use App\Domain\Seeding\SeedEntry;
use App\Domain\Seeding\SeedingService;
use App\Domain\Support\Lottery;

/**
 * @param  list<SeedEntry>  $entries
 * @return array<int, SeedEntry>
 */
function byPlayer(array $entries): array
{
    return array_column(array_map(fn (SeedEntry $e) => ['id' => $e->playerId, 'entry' => $e], $entries), 'entry', 'id');
}

it('uses a trimmed mean from five ratings on', function () {
    expect(SeedingService::trimmedMean([1, 2, 3, 4, 10]))->toBe(3.0)
        ->and(SeedingService::trimmedMean([10, 1, 4, 3, 2, 3]))->toBe(3.0);
});

it('uses a simple mean below five ratings', function () {
    expect(SeedingService::trimmedMean([1, 2, 9]))->toBe(4.0)
        ->and(SeedingService::trimmedMean([1, 2, 3, 10]))->toBe(4.0);
});

it('seeds from the positions given by the other players', function () {
    // Everyone agrees: 1 > 2 > 3 > 4 > 5 > 6.
    $players = [1, 2, 3, 4, 5, 6];
    $rankings = [];

    foreach ($players as $rater) {
        $rankings[$rater] = array_values(array_diff($players, [$rater]));
    }

    $seeding = (new SeedingService)->seed($players, $rankings, new Lottery(1));

    expect(array_map(fn (SeedEntry $e) => $e->playerId, $seeding))->toBe([1, 2, 3, 4, 5, 6])
        ->and(array_map(fn (SeedEntry $e) => $e->rank, $seeding))->toBe([1, 2, 3, 4, 5, 6])
        ->and($seeding[0]->ratings)->toBe(5)
        // Player 1 is first for everyone else: trimmed mean of five 1s.
        ->and($seeding[0]->score)->toBe(1.0);
});

it('drops the single best and worst rating from five ratings on', function () {
    $players = [1, 2, 3, 4, 5, 6];
    // Player 6 rates player 1 last; the other four put player 1 first.
    $rankings = [
        2 => [1, 3, 4, 5, 6],
        3 => [1, 2, 4, 5, 6],
        4 => [1, 2, 3, 5, 6],
        5 => [1, 2, 3, 4, 6],
        6 => [2, 3, 4, 5, 1],
    ];

    $entry = byPlayer((new SeedingService)->seed($players, $rankings, new Lottery(1)))[1];

    expect($entry->score)->toBe(1.0)
        ->and($entry->simpleMean)->toBe(1.8)
        ->and($entry->rank)->toBe(1);
});

it('breaks a tie on the trimmed mean by the simple mean', function () {
    // Positions for player 1: [1, 4, 5, 6, 6] -> trimmed 5, simple 4.4
    // Positions for player 2: [4, 6, 6, 5, 2] -> trimmed 5, simple 4.6
    $service = new SeedingService;
    $players = [1, 2, 3, 4, 5, 6, 7];
    $rankings = [
        3 => [1, 4, 5, 2, 6, 7],
        4 => [3, 5, 6, 1, 7, 2],
        5 => [3, 4, 6, 7, 1, 2],
        6 => [3, 4, 5, 7, 2, 1],
        7 => [3, 2, 4, 5, 6, 1],
    ];

    $entries = byPlayer($service->seed($players, $rankings, new Lottery(1)));

    expect($entries[1]->score)->toBe($entries[2]->score)
        ->and($entries[1]->simpleMean)->toBeLessThan($entries[2]->simpleMean)
        ->and($entries[1]->rank)->toBeLessThan($entries[2]->rank);
});

it('breaks a full tie by a deterministic lottery', function () {
    $players = range(1, 12);

    $first = (new SeedingService)->seed($players, [], new Lottery(42));
    $again = (new SeedingService)->seed($players, [], new Lottery(42));

    $order = fn (array $entries) => array_map(fn (SeedEntry $e) => $e->playerId, $entries);

    expect($order($first))->toBe($order($again))
        ->and($order($first))->toBe((new Lottery(42))->order('seeding', $players));
});

it('gives players nobody rated a neutral score', function () {
    $players = range(1, 11);
    // Only player 1 submitted, so nobody rated player 1.
    $rankings = [1 => range(2, 11)];

    $entries = byPlayer((new SeedingService)->seed($players, $rankings, new Lottery(1)));

    expect($entries[1]->score)->toBe(5.5)
        ->and($entries[1]->ratings)->toBe(0)
        ->and($entries[2]->score)->toBe(1.0)
        ->and($entries[1]->rank)->toBe(6);
});

it('renumbers positions among the current players only', function () {
    // Rater 1 ranked a since deleted player 99 first and missed new player 4.
    $players = [1, 2, 3, 4];
    $rankings = [1 => [99, 3, 2, 1]];

    $entries = byPlayer((new SeedingService)->seed($players, $rankings, new Lottery(1)));

    expect($entries[3]->score)->toBe(1.0)
        ->and($entries[2]->score)->toBe(2.0)
        ->and($entries[4]->ratings)->toBe(0);
});

it('ignores rankings of players who are no longer seeded', function () {
    $entries = byPlayer((new SeedingService)->seed([1, 2, 3], [99 => [3, 2, 1]], new Lottery(1)));

    expect($entries[1]->ratings + $entries[2]->ratings + $entries[3]->ratings)->toBe(0);
});
