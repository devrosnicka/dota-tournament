<?php

namespace App\Http\Controllers\Player;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Http\ThrottlesAttempts;
use App\Tournament\LoginCodes;
use App\Tournament\TournamentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    use ThrottlesAttempts;

    public function create(TournamentSettings $settings): Response
    {
        return Inertia::render('auth/login', [
            'registrationOpen' => $settings->phase() === Phase::Registration,
        ]);
    }

    public function store(Request $request, LoginCodes $codes): RedirectResponse
    {
        $this->ensureNotThrottled($request, 'login-code', 10, 'code');

        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:4'],
        ]);

        $player = $codes->redeem($validated['code'])
            ?? throw ValidationException::withMessages(['code' => 'Neplatný nebo prošlý kód.']);

        Auth::login($player);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Only the player logs out; an admin flag in the same session stays.
        Auth::guard('web')->logout();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
