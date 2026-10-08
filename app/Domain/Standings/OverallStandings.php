<?php

namespace App\Domain\Standings;

/**
 * Overall standings (SPEC §1.11): group stage points plus final points.
 *
 * A tie for 1st place is broken by final points, then by a 1v1 Shadow Fiend
 * shootout (a bracket for three or more) whose winner the admin enters.
 * Every other tie is shared.
 */
final class OverallStandings
{
    /**
     * @param  array<int, float>  $groupPoints  player id => group stage points
     * @param  array<int, int>  $finalPoints  finalist id => final points
     * @param  list<int>|null  $shootout  stored champion shootout, winner first
     */
    public function compute(array $groupPoints, array $finalPoints, ?array $shootout = null): OverallTable
    {
        $entries = [];

        foreach ($groupPoints as $player => $points) {
            $final = $finalPoints[$player] ?? 0;
            $entries[] = ['id' => $player, 'group' => $points, 'final' => $final, 'total' => $points + $final];
        }

        usort($entries, fn (array $a, array $b) => $b['total'] <=> $a['total'] ?: $b['final'] <=> $a['final'] ?: $a['id'] <=> $b['id']);

        if ($entries === []) {
            return new OverallTable([], null, [], false);
        }

        $top = $entries[0];
        $tied = array_values(array_map(
            fn (array $e) => $e['id'],
            array_filter($entries, fn (array $e) => $e['total'] === $top['total'] && $e['final'] === $top['final']),
        ));

        $resolved = count($tied) === 1;
        $champion = $resolved ? $tied[0] : null;

        if (! $resolved && $shootout !== null && self::sameSet($shootout, $tied)) {
            $resolved = true;
            $champion = $shootout[0];
        }

        // The champion goes first; everyone else shares positions by total.
        if ($champion !== null) {
            $index = array_search($champion, array_column($entries, 'id'), true);
            $entry = $entries[$index];
            unset($entries[$index]);
            $entries = [$entry, ...array_values($entries)];
        }

        $rows = [];
        $position = 0;
        $previous = null;

        foreach ($entries as $index => $entry) {
            $isChampion = $entry['id'] === $champion;

            if ($isChampion || $previous === null || $entry['total'] !== $previous || $index === 1 && $champion !== null) {
                $position = $index + 1;
            }

            $rows[] = new OverallRow($entry['id'], $entry['group'], $entry['final'], $entry['total'], $position, $isChampion);
            $previous = $entry['total'];
        }

        $tie = count($tied) > 1 ? $tied : [];

        return new OverallTable($rows, $champion, $tie, $resolved && $tie !== []);
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
