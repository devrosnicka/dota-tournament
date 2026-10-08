<?php

namespace App\Domain\Schedule;

final readonly class MatchPlan
{
    /**
     * @param  list<int>  $teamA
     * @param  list<int>  $teamB
     */
    public function __construct(
        public array $teamA,
        public array $teamB,
    ) {}
}
