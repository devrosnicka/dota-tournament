<?php

namespace App\Domain\Standings;

final readonly class StandingPlayer
{
    public function __construct(
        public int $id,
        public int $seedRank,
        public bool $active = true,
    ) {}
}
