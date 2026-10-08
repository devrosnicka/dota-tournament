<?php

namespace App\Tournament;

use App\Domain\Standings\GroupStandings;
use App\Domain\Standings\GroupTable;
use App\Domain\Standings\MatchResult;
use App\Domain\Standings\StandingPlayer;
use App\Enums\MatchStage;
use App\Enums\PlayerStatus;
use App\Enums\RoundStatus;
use App\Enums\Side;
use App\Enums\TiebreakContext;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\TiebreakOrder;
use Illuminate\Support\Facades\DB;

/**
 * Loads results from the database and computes the tables. Points and
 * positions are never stored (SPEC §2.1), so a corrected result shows up
 * everywhere immediately.
 */
final class Standings
{
    private ?GroupTable $group = null;

    public function group(): GroupTable
    {
        return $this->group ??= (new GroupStandings)->compute(
            $this->players(),
            $this->groupResults(),
            $this->sits(),
            TiebreakOrder::orderFor(TiebreakContext::Qualification),
        );
    }

    public function forget(): void
    {
        $this->group = null;
    }

    /**
     * @return list<StandingPlayer>
     */
    private function players(): array
    {
        return array_values(Player::query()->orderBy('id')->get()->map(fn (Player $player) => new StandingPlayer(
            $player->id,
            $player->seed_rank ?? PHP_INT_MAX,
            $player->status === PlayerStatus::Active,
        ))->all());
    }

    /**
     * @return list<MatchResult>
     */
    private function groupResults(): array
    {
        return array_values(GameMatch::query()
            ->where('stage', MatchStage::Group)
            ->whereNotNull('winner')
            ->with('players')
            ->get()
            ->map(fn (GameMatch $match) => new MatchResult(
                $match->team(Side::A),
                $match->team(Side::B),
                $match->winner ?? Side::A,
                $match->kills_a ?? 0,
                $match->kills_b ?? 0,
            ))
            ->all());
    }

    /**
     * Sits count once their round has started.
     *
     * @return array<int, int>
     */
    private function sits(): array
    {
        return DB::table('round_sits')
            ->join('rounds', 'rounds.id', '=', 'round_sits.round_id')
            ->where('rounds.status', '!=', RoundStatus::Planned->value)
            ->groupBy('round_sits.player_id')
            ->selectRaw('round_sits.player_id, count(*) as sits')
            ->pluck('sits', 'player_id')
            ->map(fn (mixed $count) => (int) $count)
            ->all();
    }
}
