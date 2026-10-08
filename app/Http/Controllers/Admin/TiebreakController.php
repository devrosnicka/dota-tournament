<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Tournament\Standings;
use App\Tournament\Tiebreaks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TiebreakController extends Controller
{
    public function index(Standings $standings): Response
    {
        $tie = $standings->group()->qualificationTie;
        $nicks = Player::query()->pluck('nick', 'id');

        return Inertia::render('admin/tiebreaks', [
            'qualification' => $tie === null ? null : [
                'players' => array_map(fn (int $id) => ['id' => $id, 'nick' => (string) $nicks[$id]], $tie->players),
                'spots' => $tie->spots,
                'resolved' => $tie->resolved,
            ],
        ]);
    }

    public function storeQualification(Request $request, Tiebreaks $tiebreaks): RedirectResponse
    {
        $validated = $request->validate(['order' => ['required', 'array', 'list']]);

        /** @var list<int> $order */
        $order = array_map(intval(...), $validated['order']);
        $tiebreaks->storeQualification($order);

        $this->toast('Výsledek rozstřelu je uložený.');

        return to_route('admin.tiebreaks');
    }
}
