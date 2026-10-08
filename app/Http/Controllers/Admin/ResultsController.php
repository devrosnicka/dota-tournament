<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Phase;
use App\Enums\Side;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Player\MatchController;
use App\Models\GameMatch;
use App\Models\Round;
use App\Tournament\Results;
use App\Tournament\ScheduleView;
use App\Tournament\TournamentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultsController extends Controller
{
    public function index(ScheduleView $view, Results $results, TournamentSettings $settings): Response
    {
        return Inertia::render('admin/results', [
            'rounds' => $view->rounds(withSeeds: false),
            'currentRound' => $results->currentRound()?->number,
            'editable' => $settings->phase() === Phase::GroupStage,
        ]);
    }

    public function report(Request $request, GameMatch $match, Results $results): RedirectResponse
    {
        $validated = $request->validate(MatchController::rules());
        $results->report($match, Side::from($validated['winner']), (int) $validated['kills_a'], (int) $validated['kills_b'], null);

        $this->toast('Výsledek je uložený.');

        return to_route('admin.results');
    }

    public function close(Round $round, Results $results): RedirectResponse
    {
        $results->close($round);
        $this->toast("{$round->number}. kolo je uzavřené.");

        return to_route('admin.results');
    }

    public function reopen(Round $round, Results $results): RedirectResponse
    {
        $results->reopen($round);
        $this->toast("{$round->number}. kolo je znovu otevřené.");

        return to_route('admin.results');
    }
}
