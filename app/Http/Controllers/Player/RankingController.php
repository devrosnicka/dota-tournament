<?php

namespace App\Http\Controllers\Player;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Tournament\Rankings;
use App\Tournament\TournamentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function show(Request $request, Rankings $rankings, TournamentSettings $settings): Response|RedirectResponse
    {
        if ($settings->phase() === Phase::Registration) {
            return to_route('home');
        }

        $player = $this->player($request);
        $ratees = $rankings->ratees($player);
        $saved = $rankings->savedOrder($player) ?? [];

        // Saved order first; anyone not ranked yet follows in a stable random
        // order, so nobody is favoured by the alphabet.
        $order = array_values(array_intersect($saved, $ratees->keys()->all()));
        $missing = array_values(array_diff($ratees->keys()->all(), $order));
        $order = [...$order, ...$settings->lottery()->order("ranking-start:{$player->id}", $missing)];

        return Inertia::render('ranking', [
            'players' => array_map(fn (int $id) => ['id' => $id, 'nick' => $ratees[$id]->nick], $order),
            'status' => match (true) {
                $saved === [] => 'none',
                $missing !== [] => 'outdated',
                default => 'saved',
            },
            'editable' => $settings->phase() === Phase::Ranking,
        ]);
    }

    public function store(Request $request, Rankings $rankings): RedirectResponse
    {
        // Rankings::save() checks that the order is exactly the other players.
        $validated = $request->validate([
            'order' => ['required', 'array', 'list'],
        ]);

        /** @var list<int> $order */
        $order = array_map(intval(...), $validated['order']);
        $rankings->save($this->player($request), $order);

        $this->toast('Pořadí je uložené.');

        return to_route('ranking');
    }
}
