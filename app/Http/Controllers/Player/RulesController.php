<?php

namespace App\Http\Controllers\Player;

use App\Domain\Schedule\FormatResolver;
use App\Domain\Standings\GroupStandings;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Tournament\Schedule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Tournament flow, rules and FAQ for players, with the numbers of this
 * tournament filled in.
 */
class RulesController extends Controller
{
    public function __invoke(Schedule $schedule, FormatResolver $formats): Response
    {
        $players = Player::query()->active()->count();

        try {
            $format = $formats->resolve($players);
            $current = ['name' => $format->name(), 'matches' => $format->matches, 'sitting' => $format->sitting()];
        } catch (InvalidArgumentException) {
            $current = null;
        }

        return Inertia::render('rules', [
            'rounds' => $schedule->roundsSetting(),
            'players' => $players,
            'format' => $current,
            'finalists' => GroupStandings::FINALISTS,
        ]);
    }
}
