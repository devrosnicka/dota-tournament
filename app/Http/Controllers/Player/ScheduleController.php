<?php

namespace App\Http\Controllers\Player;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Tournament\ScheduleView;
use App\Tournament\TournamentSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __invoke(Request $request, ScheduleView $view, TournamentSettings $settings): Response
    {
        $published = $settings->phase()->isAtLeast(Phase::GroupStage);

        return Inertia::render('schedule', [
            'rounds' => $published ? $view->rounds(withSeeds: false) : null,
            'me' => $this->player($request)->id,
        ]);
    }
}
