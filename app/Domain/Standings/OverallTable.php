<?php

namespace App\Domain\Standings;

final readonly class OverallTable
{
    /**
     * @param  list<OverallRow>  $rows
     * @param  list<int>  $championTie  players level for 1st place after final points, empty without a tie
     */
    public function __construct(
        public array $rows,
        public ?int $champion,
        public array $championTie,
        public bool $championTieResolved,
    ) {}

    public function needsShootout(): bool
    {
        return $this->championTie !== [] && ! $this->championTieResolved;
    }
}
