<?php

namespace App\Http\Controllers\Player;

use App\Enums\Advantage;
use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Tournament\FinalStage;
use App\Tournament\FinalView;
use App\Tournament\TournamentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinalController extends Controller
{
    public function show(Request $request, FinalView $view, TournamentSettings $settings): Response
    {
        return Inertia::render('final', [
            'final' => $settings->phase()->isAtLeast(Phase::FinalDraft) ? $view->state($this->player($request)) : null,
        ]);
    }

    public function advantage(Request $request, FinalStage $final): RedirectResponse
    {
        $validated = $request->validate(['advantage' => ['required', Rule::enum(Advantage::class)]]);
        $final->chooseAdvantage(Advantage::from($validated['advantage']), $this->player($request));

        return to_route('final');
    }

    public function pick(Request $request, FinalStage $final): RedirectResponse
    {
        $validated = $request->validate(['player' => ['required', 'integer']]);
        $final->pick((int) $validated['player'], $this->player($request));

        return to_route('final');
    }

    public function role(Request $request, FinalStage $final): RedirectResponse
    {
        $validated = $request->validate(['role' => ['required', 'integer', 'between:1,5']]);
        $player = $this->player($request);
        $final->chooseRole($player->id, (int) $validated['role'], $player);

        return to_route('final');
    }
}
