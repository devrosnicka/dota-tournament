<?php

namespace App\Domain\Schedule;

final readonly class RoundPlan
{
    /**
     * @param  list<int>  $sitters
     * @param  list<MatchPlan>  $matches
     */
    public function __construct(
        public RoundFormat $format,
        public array $sitters,
        public array $matches,
    ) {}
}
