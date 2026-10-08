<?php

namespace App\Tournament;

use App\Enums\Phase;
use App\Tournament\Exceptions\PhaseTransitionBlocked;
use Illuminate\Support\Facades\DB;

/**
 * Tournament state machine (SPEC §2.3). Only the admin moves it; every
 * transition first checks its preconditions and then runs its side effects
 * in the same transaction.
 */
final class PhaseManager
{
    public function __construct(
        private readonly TournamentSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function current(): Phase
    {
        return $this->settings->phase();
    }

    /**
     * Reasons why the tournament cannot move to the next phase yet.
     *
     * @return list<string>
     */
    public function advanceBlockers(): array
    {
        return match ($this->current()) {
            Phase::Registration => [],
            Phase::Finished => ['Turnaj už skončil.'],
            default => ['Tento přechod zatím není implementovaný.'],
        };
    }

    public function advance(): Phase
    {
        return DB::transaction(function (): Phase {
            $from = $this->current();
            $blockers = $this->advanceBlockers();
            $to = $from->next();

            if ($blockers !== [] || $to === null) {
                throw new PhaseTransitionBlocked($blockers ?: ['Turnaj už skončil.']);
            }

            $this->settings->setPhase($to);
            $this->audit->admin('phase.advanced', ['from' => $from->value, 'to' => $to->value]);

            return $to;
        });
    }

    /**
     * Going back is allowed only before anything has been played (PLAN §3).
     */
    public function revertTarget(): ?Phase
    {
        return match ($this->current()) {
            Phase::Ranking => Phase::Registration,
            default => null,
        };
    }

    public function revert(): Phase
    {
        return DB::transaction(function (): Phase {
            $from = $this->current();
            $to = $this->revertTarget() ?? throw new PhaseTransitionBlocked(['Z této fáze se nelze vrátit.']);

            $this->settings->setPhase($to);
            $this->audit->admin('phase.reverted', ['from' => $from->value, 'to' => $to->value]);

            return $to;
        });
    }
}
