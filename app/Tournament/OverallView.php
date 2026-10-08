<?php

namespace App\Tournament;

use App\Domain\Standings\OverallRow;
use App\Models\Player;

/**
 * Overall standings and trophies as page props.
 */
final class OverallView
{
    public function __construct(private readonly Overall $overall) {}

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        $table = $this->overall->table();
        $nicks = Player::query()->pluck('nick', 'id');
        $player = fn (int $id) => ['id' => $id, 'nick' => (string) $nicks[$id]];
        $team = $this->overall->winningTeam();

        return [
            'rows' => array_map(fn (OverallRow $row) => [
                'playerId' => $row->playerId,
                'nick' => (string) $nicks[$row->playerId],
                'position' => $row->position,
                'groupPoints' => $row->groupPoints,
                'finalPoints' => $row->finalPoints,
                'total' => $row->total,
                'champion' => $row->champion,
            ], $table->rows),
            'champion' => $table->champion === null ? null : $player($table->champion),
            'winningTeam' => $team === null ? null : array_map($player, $team),
            'championTie' => $table->championTie === [] ? null : [
                'players' => array_map($player, $table->championTie),
                'resolved' => $table->championTieResolved,
            ],
        ];
    }
}
