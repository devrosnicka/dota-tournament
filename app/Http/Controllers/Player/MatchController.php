<?php

namespace App\Http\Controllers\Player;

use App\Enums\MatchStage;
use App\Enums\Side;
use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Player;
use App\Tournament\Results;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function show(Request $request, GameMatch $match, Results $results): Response
    {
        abort_unless($match->stage === MatchStage::Group, 404);

        $match->load(['players', 'round']);
        $team = fn (Side $side) => $match->players
            ->filter(fn (Player $p) => $p->getRelationValue('pivot')?->getAttribute('side') === $side->value)
            ->sortBy('nick')
            ->map(fn (Player $p) => ['id' => $p->id, 'nick' => $p->nick])
            ->values()
            ->all();

        return Inertia::render('match', [
            'match' => [
                'id' => $match->id,
                'round' => $match->round?->number,
                'lobby' => $match->lobby,
                'teamA' => $team(Side::A),
                'teamB' => $team(Side::B),
                'winner' => $match->winner?->value,
                'killsA' => $match->kills_a,
                'killsB' => $match->kills_b,
                'reportedAt' => $match->reported_at?->toIso8601String(),
                'reportedBy' => $match->reported_by === null ? null : Player::query()->whereKey($match->reported_by)->value('nick'),
            ],
            'me' => $this->player($request)->id,
            'canReport' => $results->canReport($this->player($request), $match),
        ]);
    }

    public function report(Request $request, GameMatch $match, Results $results): RedirectResponse
    {
        $player = $this->player($request);

        abort_unless($match->players()->whereKey($player->id)->exists(), 403);

        $validated = $request->validate(self::rules());
        $results->report($match, Side::from($validated['winner']), (int) $validated['kills_a'], (int) $validated['kills_b'], $player);

        $this->toast('Výsledek je uložený.');

        return to_route('matches.show', $match);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'winner' => ['required', Rule::enum(Side::class)],
            'kills_a' => ['required', 'integer', 'min:0', 'max:300'],
            'kills_b' => ['required', 'integer', 'min:0', 'max:300'],
        ];
    }
}
