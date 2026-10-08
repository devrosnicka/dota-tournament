<?php

namespace App\Http\Controllers\Player;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Tournament\Rankings;
use App\Tournament\TournamentSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, TournamentSettings $settings, Rankings $rankings): Response
    {
        $player = $this->player($request);

        return Inertia::render('home', [
            'ranking' => $settings->phase() === Phase::Ranking
                ? ['submitted' => $rankings->savedOrder($player) !== null]
                : null,
        ]);
    }
}
