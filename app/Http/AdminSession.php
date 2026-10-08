<?php

namespace App\Http;

use Illuminate\Http\Request;

/**
 * The admin is not a user account, just a flag in the session (SPEC §2.2).
 * The same session may also hold a logged-in player.
 */
final class AdminSession
{
    public const KEY = 'is_admin';

    public static function check(Request $request): bool
    {
        return $request->hasSession() && $request->session()->get(self::KEY) === true;
    }

    public static function grant(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::KEY, true);
    }

    public static function revoke(Request $request): void
    {
        $request->session()->forget(self::KEY);
        $request->session()->regenerateToken();
    }
}
