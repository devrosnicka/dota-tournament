<?php

namespace App\Http\Controllers\Player;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Tournament\OverallView;
use App\Tournament\TournamentSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultsController extends Controller
{
    public function __invoke(Request $request, OverallView $view, TournamentSettings $settings): Response
    {
        return Inertia::render('results', [
            'overall' => $settings->phase()->isAtLeast(Phase::Final) ? $view->state() : null,
            'final' => $settings->phase() === Phase::Finished,
            'me' => $this->player($request)->id,
        ]);
    }
}
