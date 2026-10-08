<?php

namespace App\Http\Controllers;

use App\Domain\Standings\GroupStandings;
use App\Enums\Phase;
use App\Tournament\Schedule;
use App\Tournament\TournamentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tournament flow for anyone, so friends can see what it is about before
 * they register. Players get the full rules, which start with the same flow.
 */
class HowItWorksController extends Controller
{
    public function __invoke(Request $request, Schedule $schedule, TournamentSettings $settings): Response|RedirectResponse
    {
        if ($request->user() !== null) {
            return to_route('rules');
        }

        return Inertia::render('how-it-works', [
            'rounds' => $schedule->roundsSetting(),
            'finalists' => GroupStandings::FINALISTS,
            'registrationOpen' => $settings->phase() === Phase::Registration,
        ]);
    }
}
