<?php

namespace App\Domain\Schedule;

final readonly class GeneratedSchedule
{
    /**
     * @param  list<RoundPlan>  $rounds
     */
    public function __construct(
        public array $rounds,
        public float $cost,
        public int $seed,
    ) {}
}
