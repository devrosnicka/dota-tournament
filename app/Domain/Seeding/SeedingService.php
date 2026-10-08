<?php

namespace App\Domain\Seeding;

use App\Domain\Support\Lottery;

/**
 * Aggregates the players' rankings of each other into a seeding (SPEC §1.2).
 *
 * Every rater orders the other players from best (1) to worst. A player's
 * score is the mean of the positions others gave them, with the single best
 * and worst dropped once there are at least five ratings. Lower is stronger.
 */
final class SeedingService
{
    public const TRIM_FROM = 5;

    /**
     * @param  list<int>  $playerIds  players to seed
     * @param  array<int, list<int>>  $rankings  rater id => other player ids from best to worst
     * @return list<SeedEntry> ordered from the strongest seed
     */
    public function seed(array $playerIds, array $rankings, Lottery $lottery): array
    {
        $positions = array_fill_keys($playerIds, []);

        foreach ($rankings as $raterId => $order) {
            if (! array_key_exists($raterId, $positions)) {
                continue;
            }

            // Players may have been added or removed since the rater saved:
            // positions are re-numbered among the current players only.
            $order = array_values(array_unique(array_filter(
                $order,
                fn (int $id) => $id !== $raterId && array_key_exists($id, $positions),
            )));

            foreach ($order as $index => $rateeId) {
                $positions[$rateeId][] = $index + 1;
            }
        }

        $neutral = count($playerIds) / 2;
        $entries = [];

        foreach ($positions as $playerId => $given) {
            $entries[] = [
                'id' => $playerId,
                'score' => $given === [] ? $neutral : self::trimmedMean($given),
                'simple' => $given === [] ? $neutral : array_sum($given) / count($given),
                'ratings' => count($given),
                'lot' => $lottery->key('seeding', $playerId),
            ];
        }

        usort($entries, fn (array $a, array $b) => self::compare($a['score'], $b['score'])
            ?: self::compare($a['simple'], $b['simple'])
            ?: strcmp($a['lot'], $b['lot']));

        return array_map(
            fn (array $entry, int $index) => new SeedEntry($entry['id'], $index + 1, $entry['score'], $entry['simple'], $entry['ratings']),
            $entries,
            array_keys($entries),
        );
    }

    /**
     * @param  non-empty-list<int>  $values
     */
    public static function trimmedMean(array $values): float
    {
        if (count($values) >= self::TRIM_FROM) {
            sort($values);
            $values = array_slice($values, 1, -1);
        }

        return array_sum($values) / count($values);
    }

    private static function compare(float $a, float $b): int
    {
        return abs($a - $b) < 1e-9 ? 0 : $a <=> $b;
    }
}
