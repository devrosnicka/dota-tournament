<?php

use App\Domain\Schedule\FormatResolver;
use App\Domain\Schedule\SitPlanner;
use App\Domain\Support\Rng;

/**
 * @return list<list<int>>
 */
function planSits(int $players, int $rounds, int $seed): array
{
    $sitting = (new FormatResolver)->resolve($players)->sitting();

    return (new SitPlanner)->plan(range(1, $players), array_fill(0, $rounds, $sitting), [], [], new Rng($seed));
}

dataset('sit plans', function () {
    foreach (range(10, 16) as $players) {
        foreach (range(1, 8) as $rounds) {
            yield "{$players} players, {$rounds} rounds" => [$players, $rounds];
        }
    }
});

it('holds the sit rules for every player count, round count and seed', function (int $players, int $rounds) {
    $sitting = (new FormatResolver)->resolve($players)->sitting();
    $topHalf = range(1, (int) ceil($players / 2));

    foreach (range(1, 25) as $seed) {
        $plan = planSits($players, $rounds, $seed);
        $sits = array_fill(1, $players, 0);

        expect($plan)->toHaveCount($rounds);

        foreach ($plan as $round => $sitters) {
            // Right number of distinct sitters.
            expect($sitters)->toHaveCount($sitting)
                ->and(array_unique($sitters))->toHaveCount($sitting);

            foreach ($sitters as $player) {
                $sits[$player]++;
            }

            // Sit counts never differ by more than one.
            expect(max($sits) - min($sits))->toBeLessThanOrEqual(1);

            // Sitters come from both halves of the seeding.
            if ($sitting >= 2) {
                $fromTop = count(array_intersect($sitters, $topHalf));
                expect($fromTop)->toBeGreaterThan(0)->toBeLessThan($sitting);
            }

            // Nobody sits twice in a row.
            if ($round > 0) {
                expect(array_intersect($sitters, $plan[$round - 1]))->toBe([]);
            }
        }
    }
})->with('sit plans');

it('gives the same plan for the same seed', function () {
    expect(planSits(15, 6, 7))->toBe(planSits(15, 6, 7))
        ->and(planSits(13, 5, 7))->not->toBe(planSits(13, 5, 8));
});

it('decides by chance, not by the seeding', function () {
    // With one sitter per round over 5 rounds of 13 players, eight players
    // never sit. Across many seeds everyone should sometimes be among them.
    $everSat = [];

    foreach (range(1, 200) as $seed) {
        foreach (planSits(13, 5, $seed) as $sitters) {
            foreach ($sitters as $player) {
                $everSat[$player] = true;
            }
        }
    }

    expect(array_keys($everSat))->toHaveCount(13);
});

it('alternates which half gives the extra sitter', function () {
    $topHalf = range(1, 8);

    foreach (range(1, 20) as $seed) {
        $tops = array_map(fn (array $sitters) => count(array_intersect($sitters, $topHalf)), planSits(15, 4, $seed));

        // Three sitters: two from one half, one from the other, alternating.
        expect([$tops[0], $tops[1]])->toEqualCanonicalizing([1, 2])
            ->and([$tops[2], $tops[3]])->toEqualCanonicalizing([1, 2])
            ->and($tops[0])->not->toBe($tops[1]);
    }
});

it('continues from earlier rounds', function () {
    // Players 1 and 2 sat already, player 2 in the last round.
    $plan = (new SitPlanner)->plan(range(1, 14), [2, 2], [1 => 1, 2 => 1], [2], new Rng(3));

    expect(array_intersect($plan[0], [1, 2]))->toBe([])
        ->and(array_intersect($plan[1], [1, 2]))->toBe([]);
});
