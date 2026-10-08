<?php

namespace App\Domain\Standings;

final readonly class OverallRow
{
    public function __construct(
        public int $playerId,
        public float $groupPoints,
        public int $finalPoints,
        public float $total,
        public int $position,
        public bool $champion,
    ) {}
}
