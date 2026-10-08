<?php

use App\Domain\Schedule\FormatResolver;
use App\Domain\Schedule\GeneratorConfig;
use App\Domain\Schedule\History;
use App\Domain\Schedule\ScheduleGenerator;

function quickGenerator(): ScheduleGenerator
{
    return new ScheduleGenerator(new GeneratorConfig(samplesPerRound: 400, restarts: 3));
}

it('puts every active player in every round exactly once', function (int $players) {
    $schedule = quickGenerator()->generate(range(1, $players), 5, new History, 11);
    $format = (new FormatResolver)->resolve($players);

    expect($schedule->rounds)->toHaveCount(5);

    foreach ($schedule->rounds as $round) {
        $everyone = $round->sitters;

        expect($round->matches)->toHaveCount($format->matches)
            ->and($round->sitters)->toHaveCount($format->sitting());

        foreach ($round->matches as $match) {
            expect($match->teamA)->toHaveCount($format->teamSize)
                ->and($match->teamB)->toHaveCount($format->teamSize);
            $everyone = [...$everyone, ...$match->teamA, ...$match->teamB];
        }

        sort($everyone);
        expect($everyone)->toBe(range(1, $players));
    }
})->with(range(10, 16));

it('keeps the top three seeds apart', function (int $players) {
    // With a single 5v5 there are only two teams, so two of the top three
    // always share one; with two matches all three can be apart.
    $allowed = $players <= 11 ? 2 : 1;

    foreach ([1, 2, 3] as $seed) {
        $schedule = quickGenerator()->generate(range(1, $players), 5, new History, $seed);

        foreach ($schedule->rounds as $round) {
            foreach ($round->matches as $match) {
                foreach ([$match->teamA, $match->teamB] as $team) {
                    expect(count(array_intersect($team, [1, 2, 3])))->toBeLessThanOrEqual($allowed);
                }
            }
        }
    }
})->with(range(10, 16));

it('reproduces the same schedule from the same seed', function () {
    $first = quickGenerator()->generate(range(1, 13), 5, new History, 99);
    $again = quickGenerator()->generate(range(1, 13), 5, new History, 99);
    $other = quickGenerator()->generate(range(1, 13), 5, new History, 100);

    expect($again)->toEqual($first)
        ->and($other->rounds)->not->toEqual($first->rounds);
});

it('avoids repeating teammates when it can', function (int $seed) {
    // 15 players in 3v3: five rounds fit without anyone meeting a teammate twice.
    $schedule = (new ScheduleGenerator(new GeneratorConfig))->generate(range(1, 15), 5, new History, $seed);
    $history = new History;

    foreach ($schedule->rounds as $round) {
        $history->recordRound($round);
    }

    expect($history->maxTeammates())->toBe(1);
})->with([1, 2, 3]);

it('keeps the average seeds of the teams close', function () {
    $schedule = (new ScheduleGenerator(new GeneratorConfig))->generate(range(1, 12), 5, new History, 8);

    foreach ($schedule->rounds as $round) {
        foreach ($round->matches as $match) {
            $diff = abs(array_sum($match->teamA) / 3 - array_sum($match->teamB) / 3);
            expect($diff)->toBeLessThanOrEqual(2.0);
        }
    }
});

it('continues from earlier rounds', function () {
    $generator = quickGenerator();
    $earlier = $generator->generate(range(1, 11), 2, new History, 4);
    $history = new History;

    foreach ($earlier->rounds as $round) {
        $history->recordRound($round);
    }

    $later = $generator->generate(range(1, 11), 3, $history, 4);
    $sat = array_merge(...array_map(fn ($round) => $round->sitters, [...$earlier->rounds, ...$later->rounds]));

    // Eleven players, one sitter per round: five different sitters.
    expect(array_unique($sat))->toHaveCount(5)
        ->and($later->rounds[0]->sitters)->not->toBe($earlier->rounds[1]->sitters);
});

it('generates a full default schedule fast enough', function () {
    $start = microtime(true);

    (new ScheduleGenerator(new GeneratorConfig))->generate(range(1, 16), 8, new History, 1);

    expect(microtime(true) - $start)->toBeLessThan(20.0);
});
