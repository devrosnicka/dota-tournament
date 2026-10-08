<?php

namespace App\Tournament;

use App\Domain\Standings\StandingRow;
use App\Models\Player;

/**
 * Group table as page props.
 */
final class StandingsView
{
    public function __construct(private readonly Standings $standings) {}

    /**
     * @return array{rows: list<array<string, mixed>>, tie: array{players: list<string>, spots: int, resolved: bool}|null}
     */
    public function group(): array
    {
        $table = $this->standings->group();
        $nicks = Player::query()->pluck('nick', 'id');
        $tied = $table->qualificationTie->players ?? [];

        return [
            'rows' => array_map(fn (StandingRow $row) => [
                'playerId' => $row->playerId,
                'nick' => (string) $nicks[$row->playerId],
                'position' => $row->position,
                'points' => $row->points,
                'wins' => $row->wins,
                'losses' => $row->losses,
                'sits' => $row->sits,
                'killDiff' => $row->killDiff,
                'active' => $row->active,
                'qualified' => $row->qualified,
                'tied' => in_array($row->playerId, $tied, true),
            ], $table->rows),
            'tie' => $table->qualificationTie === null ? null : [
                'players' => array_map(fn (int $id) => (string) $nicks[$id], $table->qualificationTie->players),
                'spots' => $table->qualificationTie->spots,
                'resolved' => $table->qualificationTie->resolved,
            ],
        ];
    }
}
