<?php

namespace App\Domain\Standings;

use App\Enums\Side;

final readonly class MatchResult
{
    /**
     * @param  list<int>  $teamA
     * @param  list<int>  $teamB
     */
    public function __construct(
        public array $teamA,
        public array $teamB,
        public Side $winner,
        public int $killsA,
        public int $killsB,
    ) {}
}
