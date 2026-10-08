<?php

namespace App\Domain\Schedule;

use App\Domain\Support\Rng;

/**
 * Decides who sits out in each round, for all rounds at once (SPEC §1.5).
 *
 * Priorities, strongest first:
 *  1. after every round the sit counts differ by at most one (hard rule),
 *  2. nobody sits in two consecutive rounds if it can be avoided,
 *  3. sitters come evenly from the top and bottom half of the seeding,
 *     with the half giving the extra sitter alternating for odd counts,
 *  4. otherwise chance, never the seeding.
 *
 * The suggested algorithm in the spec (fill from each half by lowest count)
 * can break rule 1, so the hard rule is resolved first: players below the
 * threshold count must sit, players at it form the pool for rules 2-4.
 */
final class SitPlanner
{
    /**
     * @param  list<int>  $players  active players from the strongest seed
     * @param  list<int>  $sittersPerRound  number of sitters in each planned round
     * @param  array<int, int>  $previousSits  sits per player in earlier, fixed rounds
     * @param  list<int>  $lastSitters  who sat in the round right before the first planned one
     * @return list<list<int>> sitters of each planned round
     */
    public function plan(array $players, array $sittersPerRound, array $previousSits, array $lastSitters, Rng $rng): array
    {
        $topHalf = array_fill_keys(array_slice($players, 0, (int) ceil(count($players) / 2)), true);
        $sits = [];

        foreach ($players as $player) {
            $sits[$player] = $previousSits[$player] ?? 0;
        }

        $previous = array_fill_keys($lastSitters, true);
        $topGivesExtra = $rng->bool();
        $plan = [];

        foreach ($sittersPerRound as $count) {
            $sitters = $count > 0 ? $this->pick($players, $count, $sits, $previous, $topHalf, $topGivesExtra, $rng) : [];

            foreach ($sitters as $player) {
                $sits[$player]++;
            }

            if ($count % 2 === 1) {
                $topGivesExtra = ! $topGivesExtra;
            }

            $previous = array_fill_keys($sitters, true);
            $plan[] = $sitters;
        }

        return $plan;
    }

    /**
     * @param  list<int>  $players
     * @param  array<int, int>  $sits
     * @param  array<int, true>  $previous
     * @param  array<int, true>  $topHalf
     * @return list<int>
     */
    private function pick(array $players, int $count, array $sits, array $previous, array $topHalf, bool $topGivesExtra, Rng $rng): array
    {
        // Random order first; the stable sort keeps it among equal counts.
        $candidates = $rng->shuffle($players);
        usort($candidates, fn (int $a, int $b) => $sits[$a] <=> $sits[$b]);

        $threshold = $sits[$candidates[$count - 1]];
        $chosen = array_values(array_filter($candidates, fn (int $p) => $sits[$p] < $threshold));
        $pool = array_values(array_filter($candidates, fn (int $p) => $sits[$p] === $threshold));

        $topTarget = intdiv($count, 2) + ($count % 2 === 1 && $topGivesExtra ? 1 : 0);
        $bottomTarget = $count - $topTarget;

        while (count($chosen) < $count) {
            $tops = count(array_filter($chosen, fn (int $p) => isset($topHalf[$p])));
            $bottoms = count($chosen) - $tops;
            $best = null;
            $bestKey = null;

            foreach ($pool as $index => $player) {
                $halfFull = isset($topHalf[$player]) ? $tops >= $topTarget : $bottoms >= $bottomTarget;
                $key = [isset($previous[$player]) ? 1 : 0, $halfFull ? 1 : 0];

                if ($bestKey === null || $key < $bestKey) {
                    [$best, $bestKey] = [$index, $key];
                }
            }

            assert($best !== null, 'The pool always holds enough players.');
            $chosen[] = $pool[$best];
            unset($pool[$best]);
        }

        return $chosen;
    }
}
