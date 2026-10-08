<?php

namespace App\Domain\Standings;

final readonly class StandingRow
{
    public function __construct(
        public int $playerId,
        public ?int $position,
        public float $points,
        public int $wins,
        public int $losses,
        public int $sits,
        public int $killDiff,
        public bool $active,
        public bool $qualified,
    ) {}
}
