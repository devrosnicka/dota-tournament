<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Tournament\Rankings;
use Inertia\Inertia;
use Inertia\Response;

class SeedingController extends Controller
{
    public function __invoke(Rankings $rankings): Response
    {
        $players = Player::query()->active()->get()->keyBy('id');
        $live = collect($rankings->seeding())->keyBy('playerId');
        $snapshot = $players->every(fn (Player $player) => $player->seed_rank !== null);

        // After the ranking phase the frozen snapshot is authoritative.
        $rows = $players
            ->map(fn (Player $player) => [
                'playerId' => $player->id,
                'nick' => $player->nick,
                'rank' => $snapshot ? $player->seed_rank : $live[$player->id]->rank,
                'score' => $snapshot ? $player->seed_score : $live[$player->id]->score,
                'simpleMean' => $live[$player->id]->simpleMean,
                'ratings' => $live[$player->id]->ratings,
            ])
            ->sortBy('rank')
            ->values();

        return Inertia::render('admin/seeding', [
            'seeding' => $rows,
            'snapshot' => $snapshot && $players->isNotEmpty(),
            'submitted' => count($rankings->submittedPlayerIds()),
        ]);
    }
}
