<?php

namespace App\Http\Controllers\Player;

use App\Domain\Standings\GroupStandings;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Tournament\Schedule;
use App\Tournament\ScheduleView;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tournament flow, rules and FAQ for players, with the numbers of this
 * tournament filled in.
 */
class RulesController extends Controller
{
    public function __invoke(Schedule $schedule, ScheduleView $view): Response
    {
        $players = Player::query()->active()->count();

        return Inertia::render('rules', [
            'rounds' => $schedule->roundsSetting(),
            'players' => $players,
            'format' => $view->format($players),
            'finalists' => GroupStandings::FINALISTS,
        ]);
    }
}
