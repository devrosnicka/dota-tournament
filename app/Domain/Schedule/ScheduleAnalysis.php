<?php

namespace App\Domain\Schedule;

final readonly class ScheduleAnalysis
{
    /**
     * @param  array<int, int>  $sits  player id => number of sits
     * @param  list<list<float>>  $seedDiffs  per round, per match: difference of the teams' average seed
     * @param  list<int>  $unevenAfterRounds  round numbers after which sit counts differ by more than one
     * @param  list<array{round: int, player: int}>  $consecutiveSits  player sat in this round and the one before
     */
    public function __construct(
        public int $maxTeammates,
        public array $sits,
        public array $seedDiffs,
        public array $unevenAfterRounds,
        public array $consecutiveSits,
    ) {}
}
