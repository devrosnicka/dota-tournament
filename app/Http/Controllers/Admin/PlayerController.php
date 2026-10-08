<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Tournament\AuditLogger;
use App\Tournament\LoginCodes;
use App\Tournament\TournamentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(TournamentSettings $settings): Response
    {
        $players = Player::query()->orderBy('nick')->get()->map(fn (Player $player) => [
            'id' => $player->id,
            'nick' => $player->nick,
            'status' => $player->status->value,
            'withdrawnFromRound' => $player->withdrawn_from_round,
            'registeredAt' => $player->created_at?->toIso8601String(),
            'loginCode' => $player->login_code !== null && $player->login_code_expires_at?->isFuture()
                ? ['code' => $player->login_code, 'expiresAt' => $player->login_code_expires_at->toIso8601String()]
                : null,
        ]);

        return Inertia::render('admin/players', [
            'players' => $players,
            'canDelete' => $this->canDelete($settings),
        ]);
    }

    public function update(Request $request, Player $player, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'nick' => ['required', 'string', 'min:2', 'max:24', Rule::unique('players', 'nick')->ignore($player)],
        ]);

        $from = $player->nick;
        $player->update(['nick' => $validated['nick']]);

        $audit->admin('player.renamed', ['player_id' => $player->id, 'from' => $from, 'to' => $player->nick]);
        $this->toast("Hráč {$from} je teď {$player->nick}.");

        return to_route('admin.players.index');
    }

    public function destroy(Player $player, TournamentSettings $settings, AuditLogger $audit): RedirectResponse
    {
        if (! $this->canDelete($settings)) {
            $this->toast('Hráče lze smazat jen během registrace a hodnocení.', 'error');

            return to_route('admin.players.index');
        }

        $player->delete();

        $audit->admin('player.deleted', ['player_id' => $player->id, 'nick' => $player->nick]);
        $this->toast("Hráč {$player->nick} byl smazán.");

        return to_route('admin.players.index');
    }

    public function loginCode(Player $player, LoginCodes $codes, AuditLogger $audit): RedirectResponse
    {
        $code = $codes->issue($player);

        $audit->admin('player.login_code_issued', ['player_id' => $player->id]);
        $this->toast("Kód pro {$player->nick}: {$code}");

        return to_route('admin.players.index');
    }

    private function canDelete(TournamentSettings $settings): bool
    {
        return in_array($settings->phase(), [Phase::Registration, Phase::Ranking], true);
    }
}
