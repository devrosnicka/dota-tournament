<?php

namespace App\Http\Controllers\Player;

use App\Enums\Phase;
use App\Enums\Side;
use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Player;
use App\Tournament\Rankings;
use App\Tournament\Results;
use App\Tournament\Standings;
use App\Tournament\TournamentSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        TournamentSettings $settings,
        Rankings $rankings,
        Results $results,
        Standings $standings,
    ): Response {
        $player = $this->player($request);
        $phase = $settings->phase();

        return Inertia::render('home', [
            'ranking' => $phase === Phase::Ranking
                ? ['submitted' => $rankings->savedOrder($player) !== null]
                : null,
            'groupStage' => $phase === Phase::GroupStage ? $this->groupStage($player, $results, $standings) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function groupStage(Player $player, Results $results, Standings $standings): array
    {
        $round = $results->currentRound();
        $match = $round === null ? null : GameMatch::query()
            ->where('round_id', $round->id)
            ->whereHas('players', fn ($query) => $query->whereKey($player->id))
            ->with('players')
            ->first();
        $row = $standings->group()->row($player->id);
        $nicks = fn (array $ids) => Player::query()->whereIn('id', $ids)->orderBy('nick')->pluck('nick')->all();

        return [
            'round' => $round?->number,
            'sitting' => $round !== null && $match === null,
            'match' => $match === null ? null : [
                'id' => $match->id,
                'lobby' => $match->lobby,
                'side' => $match->players->firstWhere('id', $player->id)?->getRelationValue('pivot')?->getAttribute('side'),
                'teamA' => $nicks($match->team(Side::A)),
                'teamB' => $nicks($match->team(Side::B)),
                'winner' => $match->winner?->value,
                'killsA' => $match->kills_a,
                'killsB' => $match->kills_b,
            ],
            'standing' => $row === null ? null : ['position' => $row->position, 'points' => $row->points],
        ];
    }
}
