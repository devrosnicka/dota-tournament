<?php

namespace App\Tournament;

use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Short-lived 4-digit login codes for logging in on another device
 * (PLAN §0: replaces the token links of SPEC §2.2).
 */
final class LoginCodes
{
    public const TTL_MINUTES = 15;

    public function issue(Player $player): string
    {
        return DB::transaction(function () use ($player): string {
            do {
                $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while ($this->activeQuery($code)->whereKeyNot($player->id)->exists());

            $player->login_code = $code;
            $player->login_code_expires_at = now()->addMinutes(self::TTL_MINUTES);
            $player->save();

            return $code;
        });
    }

    /**
     * Consume a code: it logs in at most once.
     */
    public function redeem(string $code): ?Player
    {
        return DB::transaction(function () use ($code): ?Player {
            $player = $this->activeQuery($code)->first();

            if ($player === null) {
                return null;
            }

            $player->login_code = null;
            $player->login_code_expires_at = null;
            $player->save();

            return $player;
        });
    }

    /**
     * @return Builder<Player>
     */
    private function activeQuery(string $code)
    {
        return Player::query()
            ->where('login_code', $code)
            ->where('login_code_expires_at', '>', now());
    }
}
