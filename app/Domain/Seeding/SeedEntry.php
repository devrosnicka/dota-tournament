<?php

namespace App\Domain\Seeding;

final readonly class SeedEntry
{
    public function __construct(
        public int $playerId,
        public int $rank,
        public float $score,
        public float $simpleMean,
        public int $ratings,
    ) {}
}
