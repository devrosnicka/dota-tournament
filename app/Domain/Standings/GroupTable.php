<?php

namespace App\Domain\Standings;

final readonly class GroupTable
{
    /**
     * @param  list<StandingRow>  $rows  active players by position, then withdrawn ones
     */
    public function __construct(
        public array $rows,
        public ?TieGroup $qualificationTie,
    ) {}

    /**
     * @return list<int>
     */
    public function qualified(): array
    {
        return array_values(array_map(
            fn (StandingRow $row) => $row->playerId,
            array_filter($this->rows, fn (StandingRow $row) => $row->qualified),
        ));
    }

    public function row(int $playerId): ?StandingRow
    {
        foreach ($this->rows as $row) {
            if ($row->playerId === $playerId) {
                return $row;
            }
        }

        return null;
    }

    public function needsShootout(): bool
    {
        return $this->qualificationTie !== null && ! $this->qualificationTie->resolved;
    }
}
