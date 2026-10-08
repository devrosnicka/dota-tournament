<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Tournament\AuditLogger;
use App\Tournament\LoginCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Log in on another device": the player shows a code here and types it in
 * on the other device.
 */
class DeviceLoginController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('device', [
            'loginCode' => $request->session()->get('device_login_code'),
        ]);
    }

    public function store(Request $request, LoginCodes $codes, AuditLogger $audit): RedirectResponse
    {
        $player = $this->player($request);
        $code = $codes->issue($player);

        $audit->player($player, 'player.login_code_issued');

        return to_route('device')->with('device_login_code', [
            'code' => $code,
            'expiresAt' => $player->login_code_expires_at?->toIso8601String(),
        ]);
    }
}
