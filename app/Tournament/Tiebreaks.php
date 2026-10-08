<?php

namespace App\Tournament;

use App\Enums\TiebreakContext;
use App\Models\TiebreakOrder;
use Illuminate\Validation\ValidationException;

/**
 * 1v1 Shadow Fiend shootouts entered by the admin (SPEC §1.9, PLAN §0).
 */
final class Tiebreaks
{
    public function __construct(
        private readonly Standings $standings,
        private readonly Overall $overall,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<int>  $order  the tied players, shootout winners first
     */
    public function storeQualification(array $order): void
    {
        $tie = $this->standings->group()->qualificationTie;
        $expected = $tie->players ?? [];
        $given = $order;
        sort($expected);
        sort($given);

        if ($tie === null || $given !== $expected) {
            throw ValidationException::withMessages(['order' => 'Pořadí musí obsahovat přesně hráče ve shodě.']);
        }

        TiebreakOrder::query()->updateOrCreate(
            ['context' => TiebreakContext::Qualification],
            ['ordered_player_ids' => $order],
        );

        $this->standings->forget();
        $this->audit->admin('tiebreak.stored', ['context' => 'qualification', 'order' => $order]);
    }

    /**
     * The winner of the champion shootout (or its bracket).
     */
    public function storeChampion(int $winner): void
    {
        $tie = $this->overall->table()->championTie;

        if (! $this->overall->seriesDecided() || ! in_array($winner, $tie, true)) {
            throw ValidationException::withMessages(['winner' => 'Vítěz musí být jeden z hráčů ve shodě o 1. místo.']);
        }

        $order = [$winner, ...array_values(array_diff($tie, [$winner]))];

        TiebreakOrder::query()->updateOrCreate(
            ['context' => TiebreakContext::Champion],
            ['ordered_player_ids' => $order],
        );

        $this->audit->admin('tiebreak.stored', ['context' => 'champion', 'order' => $order]);
    }
}
