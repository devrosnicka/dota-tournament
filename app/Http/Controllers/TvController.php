<?php

namespace App\Http\Controllers;

use App\Enums\Phase;
use App\Models\Player;
use App\Tournament\Rankings;
use App\Tournament\Results;
use App\Tournament\ScheduleView;
use App\Tournament\StandingsView;
use App\Tournament\TournamentSettings;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only projector view, content follows the phase (SPEC §2.5).
 */
class TvController extends Controller
{
    public function __invoke(
        TournamentSettings $settings,
        Rankings $rankings,
        Results $results,
        ScheduleView $schedule,
        StandingsView $standings,
    ): Response {
        $phase = $settings->phase();
        $props = [];

        if ($phase === Phase::Registration) {
            $props['players'] = Player::query()->orderBy('created_at')->orderBy('id')->pluck('nick')->all();
        }

        if ($phase === Phase::Ranking) {
            $props['ranking'] = [
                'submitted' => count($rankings->submittedPlayerIds()),
                'total' => Player::query()->active()->count(),
            ];
        }

        if ($phase->isAtLeast(Phase::GroupStage)) {
            $current = $results->currentRound()?->number;
            $props['round'] = collect($schedule->rounds(withSeeds: false))->firstWhere('number', $current);
            $props['standings'] = $standings->group();
        }

        return Inertia::render('tv/index', $props);
    }
}
