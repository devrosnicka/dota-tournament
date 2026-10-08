<?php

use App\Domain\Schedule\FormatResolver;
use App\Domain\Schedule\MatchPlan;
use App\Domain\Schedule\RoundPlan;
use App\Domain\Schedule\ScheduleAnalyzer;

function round11(int $sitter, array $teamA): RoundPlan
{
    $players = array_values(array_diff(range(1, 11), [$sitter]));

    return new RoundPlan(
        (new FormatResolver)->resolve(11),
        [$sitter],
        [new MatchPlan($teamA, array_values(array_diff($players, $teamA)))],
    );
}

it('reports sits, teammates and seed differences', function () {
    $analysis = (new ScheduleAnalyzer)->analyze([
        round11(11, [1, 4, 5, 8, 9]),
        round11(10, [1, 4, 5, 8, 9]),
    ], array_combine(range(1, 11), range(1, 11)));

    expect($analysis->sits[11])->toBe(1)
        ->and($analysis->sits[10])->toBe(1)
        ->and($analysis->sits[1])->toBe(0)
        ->and($analysis->maxTeammates)->toBe(2)
        // Team A averages 5.4, team B (2, 3, 6, 7, 11) averages 5.8.
        ->and(round($analysis->seedDiffs[1][0], 2))->toBe(0.4)
        ->and($analysis->unevenAfterRounds)->toBe([])
        ->and($analysis->consecutiveSits)->toBe([]);
});

it('detects broken sit rules', function () {
    $analysis = (new ScheduleAnalyzer)->analyze([
        round11(11, [1, 2, 3, 4, 5]),
        round11(11, [1, 2, 3, 4, 5]),
    ], array_combine(range(1, 11), range(1, 11)));

    expect($analysis->unevenAfterRounds)->toBe([2])
        ->and($analysis->consecutiveSits)->toBe([['round' => 2, 'player' => 11]]);
});
