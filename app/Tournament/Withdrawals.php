<?php

namespace App\Tournament;

use App\Domain\Schedule\FormatResolver;
use App\Domain\Schedule\History;
use App\Enums\Phase;
use App\Enums\PlayerStatus;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A player leaving the tournament from round X (SPEC §1.8, PLAN §3).
 *
 * Rounds from X on are generated again for the remaining players, with the
 * format following the new player count. Earlier rounds stay as they are
 * and count as history. X must not have any result yet, so a played or
 * running round never changes. The player keeps their points but does not
 * reach the final.
 */
final class Withdrawals
{
    public function __construct(
        private readonly TournamentSettings $settings,
        private readonly Schedule $schedule,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * The earliest round a player can withdraw from: the first one after
     * the last round with any result.
     */
    public function firstAllowedRound(): int
    {
        $lastWithResults = GameMatch::query()
            ->join('rounds', 'rounds.id', '=', 'matches.round_id')
            ->whereNotNull('matches.winner')
            ->max('rounds.number');

        return (int) $lastWithResults + 1;
    }

    public function lastRound(): int
    {
        return (int) Round::query()->max('number');
    }

    public function canWithdraw(): bool
    {
        return in_array($this->settings->phase(), [Phase::ScheduleReview, Phase::GroupStage], true)
            && $this->schedule->exists();
    }

    public function withdraw(Player $player, int $fromRound): void
    {
        if (! $this->canWithdraw()) {
            throw ValidationException::withMessages(['round' => 'Odstoupení lze zadat jen během přípravy rozpisu a základní části.']);
        }

        if ($player->status !== PlayerStatus::Active) {
            throw ValidationException::withMessages(['round' => "{$player->nick} už odstoupil."]);
        }

        $first = $this->firstAllowedRound();
        $last = $this->lastRound() + 1;

        if ($fromRound < $first || $fromRound > $last) {
            throw ValidationException::withMessages(['round' => "Odstoupit lze od {$first}. kola, kola s výsledky už se nemění."]);
        }

        if (Player::query()->active()->count() - 1 < FormatResolver::MIN_PLAYERS) {
            throw ValidationException::withMessages(['round' => 'Po odstoupení by zbylo méně než '.FormatResolver::MIN_PLAYERS.' hráčů.']);
        }

        DB::transaction(function () use ($player, $fromRound, $last): void {
            $player->forceFill([
                'status' => PlayerStatus::Withdrawn,
                'withdrawn_from_round' => $fromRound,
            ])->save();

            if ($fromRound < $last) {
                $this->regenerateFrom($fromRound);
            }
        });

        $this->audit->admin('player.withdrawn', ['player_id' => $player->id, 'from_round' => $fromRound]);
    }

    /**
     * Generate rounds from the given number on again for the active players.
     */
    private function regenerateFrom(int $fromRound): void
    {
        $count = $this->lastRound() - $fromRound + 1;
        $earlier = $this->schedule->rounds()->filter(fn (Round $round) => $round->number < $fromRound)->values();
        $history = new History;

        foreach ($this->schedule->plans($earlier) as $plan) {
            $history->recordRound($plan);
        }

        $seed = random_int(1, PHP_INT_MAX);
        $generated = $this->schedule->generator()->generate($this->schedule->seededPlayers(), $count, $history, $seed);

        Round::query()->where('number', '>=', $fromRound)->delete();
        $this->schedule->persist($generated->rounds, $fromRound);

        $this->audit->system('schedule.regenerated', ['from_round' => $fromRound, 'rounds' => $count, 'seed' => $seed]);
    }
}
