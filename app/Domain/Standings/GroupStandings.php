<?php

namespace App\Domain\Standings;

use App\Enums\Side;

/**
 * Group stage table (SPEC §1.4, §1.9 as changed in PLAN §0).
 *
 * Win 1 point, loss 0, sitting out 0.5. Kill difference sums own team kills
 * minus opponent kills over the games played. Order: points, kill
 * difference, then the seeding. The only exception is a tie across the
 * qualification line (10th/11th active player): a 1v1 shootout decides who
 * is above the line; within each side of the line the seeding still rules.
 *
 * Withdrawn players keep their points but take no position.
 */
final class GroupStandings
{
    public const FINALISTS = 10;

    /**
     * @param  list<StandingPlayer>  $players
     * @param  list<MatchResult>  $results
     * @param  array<int, int>  $sits  player id => sits in started rounds
     * @param  list<int>|null  $shootout  stored shootout result, winners first
     */
    public function compute(array $players, array $results, array $sits, ?array $shootout = null): GroupTable
    {
        $stats = [];

        foreach ($players as $player) {
            $stats[$player->id] = ['wins' => 0, 'losses' => 0, 'kills' => 0, 'sits' => $sits[$player->id] ?? 0];
        }

        foreach ($results as $result) {
            foreach ([Side::A, Side::B] as $side) {
                $team = $side === Side::A ? $result->teamA : $result->teamB;
                $diff = $side === Side::A ? $result->killsA - $result->killsB : $result->killsB - $result->killsA;
                $won = $result->winner === $side;

                foreach ($team as $id) {
                    if (isset($stats[$id])) {
                        $stats[$id][$won ? 'wins' : 'losses']++;
                        $stats[$id]['kills'] += $diff;
                    }
                }
            }
        }

        $points = fn (StandingPlayer $p) => $stats[$p->id]['wins'] + 0.5 * $stats[$p->id]['sits'];
        $compare = fn (StandingPlayer $a, StandingPlayer $b) => $points($b) <=> $points($a)
            ?: $stats[$b->id]['kills'] <=> $stats[$a->id]['kills']
            ?: $a->seedRank <=> $b->seedRank;

        $active = array_values(array_filter($players, fn (StandingPlayer $p) => $p->active));
        $withdrawn = array_values(array_filter($players, fn (StandingPlayer $p) => ! $p->active));
        usort($active, $compare);
        usort($withdrawn, $compare);

        $tie = null;

        if (count($active) > self::FINALISTS) {
            $level = fn (StandingPlayer $p) => [$points($p), $stats[$p->id]['kills']];
            $line = $level($active[self::FINALISTS - 1]);

            if ($level($active[self::FINALISTS]) === $line) {
                $group = array_values(array_filter($active, fn (StandingPlayer $p) => $level($p) === $line));
                $start = (int) array_search($group[0], $active, true);
                $spots = self::FINALISTS - $start;
                $ids = array_map(fn (StandingPlayer $p) => $p->id, $group);
                $resolved = $shootout !== null && self::sameSet($shootout, $ids);

                if ($resolved) {
                    $winners = array_slice($shootout, 0, $spots);
                    $above = array_values(array_filter($group, fn (StandingPlayer $p) => in_array($p->id, $winners, true)));
                    $below = array_values(array_filter($group, fn (StandingPlayer $p) => ! in_array($p->id, $winners, true)));
                    array_splice($active, $start, count($group), [...$above, ...$below]);
                }

                $tie = new TieGroup($ids, $spots, $resolved);
            }
        }

        $rows = [];

        foreach ([...$active, ...$withdrawn] as $index => $player) {
            $s = $stats[$player->id];
            $rows[] = new StandingRow(
                playerId: $player->id,
                position: $player->active ? $index + 1 : null,
                points: $points($player),
                wins: $s['wins'],
                losses: $s['losses'],
                sits: $s['sits'],
                killDiff: $s['kills'],
                active: $player->active,
                qualified: $player->active && $index < self::FINALISTS,
            );
        }

        return new GroupTable($rows, $tie);
    }

    /**
     * @param  list<int>  $a
     * @param  list<int>  $b
     */
    private static function sameSet(array $a, array $b): bool
    {
        sort($a);
        sort($b);

        return $a === $b;
    }
}
