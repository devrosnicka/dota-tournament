<?php

namespace App\Tournament;

use App\Enums\MatchStage;
use App\Enums\Phase;
use App\Enums\RoundStatus;
use App\Enums\Side;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Group stage results (SPEC §2.5): any player of a match may report it and
 * overwrite it until the admin closes the round; the admin may correct any
 * match during the group stage.
 */
final class Results
{
    public function __construct(
        private readonly TournamentSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function currentRound(): ?Round
    {
        return Round::query()->where('status', '!=', RoundStatus::Done)->orderBy('number')->first();
    }

    public function canReport(Player $player, GameMatch $match): bool
    {
        return $this->settings->phase() === Phase::GroupStage
            && $match->stage === MatchStage::Group
            && $match->round?->status !== RoundStatus::Done
            && $match->players()->whereKey($player->id)->exists();
    }

    /**
     * @param  Player|null  $by  null when the admin reports
     */
    public function report(GameMatch $match, Side $winner, int $killsA, int $killsB, ?Player $by): void
    {
        if ($this->settings->phase() !== Phase::GroupStage || $match->stage !== MatchStage::Group) {
            throw ValidationException::withMessages(['winner' => 'Výsledky se zadávají jen během základní části.']);
        }

        if ($by !== null && ! $this->canReport($by, $match)) {
            throw ValidationException::withMessages(['winner' => 'Výsledek tohoto zápasu už nemůžeš měnit.']);
        }

        DB::transaction(function () use ($match, $winner, $killsA, $killsB, $by): void {
            $match->update([
                'winner' => $winner,
                'kills_a' => $killsA,
                'kills_b' => $killsB,
                'reported_by' => $by?->id,
                'reported_at' => now(),
            ]);

            Round::query()
                ->whereKey($match->round_id)
                ->where('status', RoundStatus::Planned)
                ->update(['status' => RoundStatus::InProgress]);
        });

        $payload = ['match_id' => $match->id, 'winner' => $winner->value, 'kills_a' => $killsA, 'kills_b' => $killsB];
        $by === null
            ? $this->audit->admin('match.reported', $payload)
            : $this->audit->player($by, 'match.reported', $payload);
    }

    public function close(Round $round): void
    {
        if ($round->matches()->whereNull('winner')->exists()) {
            throw ValidationException::withMessages(['round' => "{$round->number}. kolo ještě nemá všechny výsledky."]);
        }

        $round->update(['status' => RoundStatus::Done]);
        $this->audit->admin('round.closed', ['round' => $round->number]);
    }

    /**
     * Reopen the last closed round, e.g. after closing it by mistake.
     */
    public function reopen(Round $round): void
    {
        $laterResults = GameMatch::query()
            ->whereIn('round_id', Round::query()->where('number', '>', $round->number)->select('id'))
            ->whereNotNull('winner')
            ->exists();

        if ($round->status !== RoundStatus::Done || $laterResults || $this->settings->phase() !== Phase::GroupStage) {
            throw ValidationException::withMessages(['round' => 'Toto kolo už nejde znovu otevřít.']);
        }

        $round->update(['status' => RoundStatus::InProgress]);
        $this->audit->admin('round.reopened', ['round' => $round->number]);
    }
}
