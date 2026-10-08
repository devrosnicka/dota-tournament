<?php

namespace App\Domain\Schedule;

/**
 * Statistics for the admin's schedule preview and checks of the sit rules,
 * which a manual swap may break (SPEC §1.7).
 */
final class ScheduleAnalyzer
{
    /**
     * @param  list<RoundPlan>  $rounds  in order, round number = index + 1
     * @param  array<int, int>  $seedPositions  player id => position among active players
     */
    public function analyze(array $rounds, array $seedPositions): ScheduleAnalysis
    {
        $cost = new TeamCost(new GeneratorConfig, $seedPositions);
        $history = new History;
        $sits = [];
        $seedDiffs = [];
        $uneven = [];
        $consecutive = [];
        $previousSitters = [];

        foreach ($rounds as $index => $round) {
            $number = $index + 1;
            $present = $round->sitters;
            $diffs = [];

            foreach ($round->matches as $match) {
                $present = [...$present, ...$match->teamA, ...$match->teamB];
                $diffs[] = abs($cost->averageSeed($match->teamA) - $cost->averageSeed($match->teamB));
            }

            foreach ($present as $player) {
                $sits[$player] ??= 0;
            }

            foreach ($round->sitters as $player) {
                $sits[$player]++;

                if (in_array($player, $previousSitters, true)) {
                    $consecutive[] = ['round' => $number, 'player' => $player];
                }
            }

            // Withdrawn players are not present any more and do not count.
            $counts = array_intersect_key($sits, array_flip($present));

            if ($counts !== [] && max($counts) - min($counts) > 1) {
                $uneven[] = $number;
            }

            $history->recordRound($round);
            $seedDiffs[] = $diffs;
            $previousSitters = $round->sitters;
        }

        return new ScheduleAnalysis($history->maxTeammates(), $sits, $seedDiffs, $uneven, $consecutive);
    }
}
