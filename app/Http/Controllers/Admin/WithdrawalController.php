<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Tournament\Withdrawals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function store(Request $request, Player $player, Withdrawals $withdrawals): RedirectResponse
    {
        $validated = $request->validate(['round' => ['required', 'integer']]);

        $withdrawals->withdraw($player, (int) $validated['round']);
        $this->toast("{$player->nick} odstoupil od {$validated['round']}. kola. Další kola jsou přegenerovaná.");

        return to_route('admin.players.index');
    }
}
