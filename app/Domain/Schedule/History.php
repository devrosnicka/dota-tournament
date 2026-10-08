<?php

namespace App\Domain\Schedule;

/**
 * Who played with and against whom, and who sat how often. Carried from one
 * round to the next while generating (SPEC §1.6).
 */
final class History
{
    /** @var array<int, int> */
    private array $teammates = [];

    /** @var array<int, int> */
    private array $opponents = [];

    /** @var array<int, int> */
    public array $sits = [];

    /** @var list<int> */
    public array $lastSitters = [];

    public static function pair(int $a, int $b): int
    {
        return $a < $b ? $a * 1_000_000 + $b : $b * 1_000_000 + $a;
    }

    public function teammates(int $a, int $b): int
    {
        return $this->teammates[self::pair($a, $b)] ?? 0;
    }

    public function opponents(int $a, int $b): int
    {
        return $this->opponents[self::pair($a, $b)] ?? 0;
    }

    public function maxTeammates(): int
    {
        return $this->teammates === [] ? 0 : max($this->teammates);
    }

    public function recordRound(RoundPlan $round): void
    {
        foreach ($round->matches as $match) {
            $this->recordMatch($match);
        }

        foreach ($round->sitters as $player) {
            $this->sits[$player] = ($this->sits[$player] ?? 0) + 1;
        }

        $this->lastSitters = $round->sitters;
    }

    public function recordMatch(MatchPlan $match): void
    {
        foreach ([$match->teamA, $match->teamB] as $team) {
            foreach ($team as $i => $a) {
                foreach (array_slice($team, $i + 1) as $b) {
                    $key = self::pair($a, $b);
                    $this->teammates[$key] = ($this->teammates[$key] ?? 0) + 1;
                }
            }
        }

        foreach ($match->teamA as $a) {
            foreach ($match->teamB as $b) {
                $key = self::pair($a, $b);
                $this->opponents[$key] = ($this->opponents[$key] ?? 0) + 1;
            }
        }
    }
}
