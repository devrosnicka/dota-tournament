<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;

abstract class Controller
{
    /**
     * The logged-in player on routes behind the `auth` middleware.
     */
    protected function player(Request $request): Player
    {
        $player = $request->user();

        abort_unless($player instanceof Player, 403);

        return $player;
    }

    protected function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
