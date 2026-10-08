<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Tournament\Overall;
use App\Tournament\Standings;
use App\Tournament\Tiebreaks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TiebreakController extends Controller
{
    public function index(Standings $standings, Overall $overall): Response
    {
        $tie = $standings->group()->qualificationTie;
        $nicks = Player::query()->pluck('nick', 'id');
        $player = fn (int $id) => ['id' => $id, 'nick' => (string) $nicks[$id]];
        $overallTable = $overall->seriesDecided() ? $overall->table() : null;

        return Inertia::render('admin/tiebreaks', [
            'qualification' => $tie === null ? null : [
                'players' => array_map($player, $tie->players),
                'spots' => $tie->spots,
                'resolved' => $tie->resolved,
            ],
            'champion' => $overallTable === null || $overallTable->championTie === [] ? null : [
                'bracket' => array_map($player, $overall->championBracket()),
                'resolved' => $overallTable->championTieResolved,
                'winner' => $overallTable->champion,
            ],
        ]);
    }

    public function storeChampion(Request $request, Tiebreaks $tiebreaks): RedirectResponse
    {
        $validated = $request->validate(['winner' => ['required', 'integer']]);
        $tiebreaks->storeChampion((int) $validated['winner']);

        $this->toast('Vítěz rozstřelu o šampiona je uložený.');

        return to_route('admin.tiebreaks');
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
