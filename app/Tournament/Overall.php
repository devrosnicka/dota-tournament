<?php

namespace App\Tournament;

use App\Domain\Standings\OverallStandings;
use App\Domain\Standings\OverallTable;
use App\Domain\Standings\StandingRow;
use App\Enums\Side;
use App\Enums\TiebreakContext;
use App\Models\TiebreakOrder;

/**
 * Overall standings and trophies (SPEC §1.11), computed on every request.
 */
final class Overall
{
    public function __construct(
        private readonly Standings $standings,
        private readonly FinalStage $final,
        private readonly TournamentSettings $settings,
    ) {}

    public function table(): OverallTable
    {
        $groupPoints = [];

        foreach ($this->standings->group()->rows as $row) {
            /** @var StandingRow $row */
            $groupPoints[$row->playerId] = $row->points;
        }

        return (new OverallStandings)->compute(
            $groupPoints,
            $this->final->points(),
            TiebreakOrder::orderFor(TiebreakContext::Champion),
        );
    }

    /**
     * Players of the team that won the final, or null before it is decided.
     *
     * @return list<int>|null
     */
    public function winningTeam(): ?array
    {
        $winner = $this->final->series()->winner();
        $draft = $this->final->draft();

        return $winner === null || $draft === null ? null : $draft->team($winner);
    }

    /**
     * Tied players for the champion shootout in a drawn bracket order.
     *
     * @return list<int>
     */
    public function championBracket(): array
    {
        return $this->settings->lottery()->order('champion', $this->table()->championTie);
    }

    public function seriesDecided(): bool
    {
        return $this->final->series()->winner() instanceof Side;
    }
}
