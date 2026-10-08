<?php

namespace App\Http\Controllers\Player;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Tournament\StandingsView;
use App\Tournament\TournamentSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StandingsController extends Controller
{
    public function __invoke(Request $request, StandingsView $view, TournamentSettings $settings): Response
    {
        return Inertia::render('standings', [
            'standings' => $settings->phase()->isAtLeast(Phase::GroupStage) ? $view->group() : null,
            'me' => $this->player($request)->id,
        ]);
    }
}
