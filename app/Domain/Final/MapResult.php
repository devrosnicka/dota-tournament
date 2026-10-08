<?php

namespace App\Domain\Final;

use App\Enums\Side;

final readonly class MapResult
{
    public function __construct(
        public Side $winner,
        public ?int $killsA = null,
        public ?int $killsB = null,
        public ?Side $radiant = null,
        public ?Side $firstPick = null,
    ) {}
}
