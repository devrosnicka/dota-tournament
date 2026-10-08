<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Phase;
use App\Enums\PlayerStatus;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Tournament\PhaseManager;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(PhaseManager $phases): Response
    {
        $next = $phases->current()->next();
        $revertTo = $phases->revertTarget();

        return Inertia::render('admin/dashboard', [
            'phases' => array_map(self::phase(...), Phase::cases()),
            'next' => $next ? self::phase($next) : null,
            'blockers' => $phases->advanceBlockers(),
            'revertTo' => $revertTo ? self::phase($revertTo) : null,
            'activePlayers' => Player::query()->where('status', PlayerStatus::Active)->count(),
        ]);
    }

    /**
     * @return array{value: string, label: string}
     */
    private static function phase(Phase $phase): array
    {
        return ['value' => $phase->value, 'label' => $phase->label()];
    }
}
