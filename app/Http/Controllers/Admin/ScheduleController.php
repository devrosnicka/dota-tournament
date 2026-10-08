<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Models\Round;
use App\Tournament\Schedule;
use App\Tournament\ScheduleView;
use App\Tournament\TournamentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Schedule $schedule, ScheduleView $view, TournamentSettings $settings): Response
    {
        $exists = $schedule->exists();

        return Inertia::render('admin/schedule', [
            'rounds' => $view->rounds(withSeeds: true),
            'analysis' => $exists ? $view->analysis() : null,
            'roundsSetting' => $schedule->roundsSetting(),
            'maxRounds' => Schedule::MAX_ROUNDS,
            'editable' => $settings->phase() === Phase::ScheduleReview,
            'seed' => $settings->get('schedule_seed'),
        ]);
    }

    public function generate(Request $request, Schedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'rounds' => ['required', 'integer'],
        ]);

        $schedule->generate((int) $validated['rounds']);
        $this->toast('Rozpis je vygenerovaný.');

        return to_route('admin.schedule');
    }

    public function swap(Request $request, Round $round, Schedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'first' => ['required', 'integer'],
            'second' => ['required', 'integer'],
        ]);

        $schedule->swap($round, (int) $validated['first'], (int) $validated['second']);
        $this->toast("Hráči v {$round->number}. kole jsou prohození.");

        return to_route('admin.schedule');
    }
}
