<?php

namespace App\Tournament;

use App\Domain\Schedule\FormatResolver;
use App\Domain\Standings\GroupStandings;
use App\Enums\DraftStage;
use App\Enums\Phase;
use App\Enums\RoundStatus;
use App\Models\Player;
use App\Models\Round;
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
        private readonly Rankings $rankings,
        private readonly Schedule $schedule,
        private readonly Standings $standings,
        private readonly FinalStage $final,
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
            Phase::Ranking => $this->playerCountBlockers(),
            Phase::ScheduleReview => $this->schedule->exists() ? [] : ['Rozpis ještě není vygenerovaný.'],
            Phase::GroupStage => $this->groupStageBlockers(),
            Phase::FinalDraft => $this->final->draft()?->stage() === DraftStage::Done ? [] : ['Draft hráčů a rolí ještě není dokončený.'],
            Phase::Final => $this->finalBlockers(),
            Phase::Finished => ['Turnaj už skončil.'],
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

            match ($from) {
                Phase::Ranking => $this->rankings->snapshot(),
                Phase::GroupStage => $this->final->start(),
                default => null,
            };

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
            Phase::ScheduleReview => Phase::Ranking,
            default => null,
        };
    }

    public function revert(): Phase
    {
        return DB::transaction(function (): Phase {
            $from = $this->current();
            $to = $this->revertTarget() ?? throw new PhaseTransitionBlocked(['Z této fáze se nelze vrátit.']);

            if ($from === Phase::ScheduleReview) {
                $this->schedule->delete();
                $this->rankings->clearSnapshot();
            }

            $this->settings->setPhase($to);
            $this->audit->admin('phase.reverted', ['from' => $from->value, 'to' => $to->value]);

            return $to;
        });
    }

    /**
     * @return list<string>
     */
    private function finalBlockers(): array
    {
        if ($this->final->series()->winner() === null) {
            return ['Série finále ještě není rozhodnutá.'];
        }

        return app(Overall::class)->table()->needsShootout()
            ? ['Shoda o celkového šampiona čeká na výsledek rozstřelu.']
            : [];
    }

    /**
     * @return list<string>
     */
    private function groupStageBlockers(): array
    {
        $blockers = [];
        $open = Round::query()->where('status', '!=', RoundStatus::Done)->orderBy('number')->pluck('number');

        if ($open->isNotEmpty()) {
            $blockers[] = 'Neuzavřená kola: '.$open->implode(', ').'.';
        }

        if (Player::query()->active()->count() < GroupStandings::FINALISTS) {
            $blockers[] = 'Finále potřebuje aspoň '.GroupStandings::FINALISTS.' aktivních hráčů.';
        }

        if ($this->standings->group()->needsShootout()) {
            $blockers[] = 'Shoda na hranici postupu čeká na výsledek rozstřelu.';
        }

        return $blockers;
    }

    /**
     * @return list<string>
     */
    private function playerCountBlockers(): array
    {
        $count = Player::query()->active()->count();

        if ($count < FormatResolver::MIN_PLAYERS || $count > FormatResolver::MAX_PLAYERS) {
            return [sprintf(
                'Turnaj potřebuje %d až %d aktivních hráčů, teď jich je %d.',
                FormatResolver::MIN_PLAYERS,
                FormatResolver::MAX_PLAYERS,
                $count,
            )];
        }

        return [];
    }
}
