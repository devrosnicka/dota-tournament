<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Advantage;
use App\Enums\Side;
use App\Http\Controllers\Controller;
use App\Tournament\FinalStage;
use App\Tournament\FinalView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin can make any draft move on behalf of whoever is on turn, take
 * back the last move and enter the map results.
 */
class FinalController extends Controller
{
    public function index(FinalView $view): Response
    {
        return Inertia::render('admin/final', ['final' => $view->state()]);
    }

    public function advantage(Request $request, FinalStage $final): RedirectResponse
    {
        $validated = $request->validate(['advantage' => ['required', Rule::enum(Advantage::class)]]);
        $final->chooseAdvantage(Advantage::from($validated['advantage']), null);

        return to_route('admin.final');
    }

    public function pick(Request $request, FinalStage $final): RedirectResponse
    {
        $validated = $request->validate(['player' => ['required', 'integer']]);
        $final->pick((int) $validated['player'], null);

        return to_route('admin.final');
    }

    public function role(Request $request, FinalStage $final): RedirectResponse
    {
        $validated = $request->validate([
            'player' => ['required', 'integer'],
            'role' => ['required', 'integer', 'between:1,5'],
        ]);
        $final->chooseRole((int) $validated['player'], (int) $validated['role'], null);

        return to_route('admin.final');
    }

    public function undo(FinalStage $final): RedirectResponse
    {
        $final->undo();
        $this->toast('Poslední tah je vrácený.');

        return to_route('admin.final');
    }

    public function storeMap(Request $request, FinalStage $final): RedirectResponse
    {
        $validated = $request->validate([
            'winner' => ['required', Rule::enum(Side::class)],
            'kills_a' => ['nullable', 'integer', 'min:0', 'max:300'],
            'kills_b' => ['nullable', 'integer', 'min:0', 'max:300'],
            'radiant' => ['nullable', Rule::enum(Side::class)],
            'first_pick' => ['nullable', Rule::enum(Side::class)],
        ]);

        $final->recordMap(
            Side::from($validated['winner']),
            isset($validated['kills_a']) ? (int) $validated['kills_a'] : null,
            isset($validated['kills_b']) ? (int) $validated['kills_b'] : null,
            isset($validated['radiant']) ? Side::from($validated['radiant']) : null,
            isset($validated['first_pick']) ? Side::from($validated['first_pick']) : null,
        );
        $this->toast('Mapa je zapsaná.');

        return to_route('admin.final');
    }

    public function destroyMap(FinalStage $final): RedirectResponse
    {
        $final->deleteLastMap();
        $this->toast('Poslední mapa je smazaná.');

        return to_route('admin.final');
    }
}
