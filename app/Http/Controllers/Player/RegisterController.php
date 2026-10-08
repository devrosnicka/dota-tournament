<?php

namespace App\Http\Controllers\Player;

use App\Enums\Phase;
use App\Http\Controllers\Controller;
use App\Http\ThrottlesAttempts;
use App\Models\Player;
use App\Tournament\AuditLogger;
use App\Tournament\TournamentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    use ThrottlesAttempts;

    public function create(TournamentSettings $settings): Response
    {
        return Inertia::render('auth/register', [
            'open' => $settings->phase() === Phase::Registration,
        ]);
    }

    public function store(Request $request, TournamentSettings $settings, AuditLogger $audit): RedirectResponse
    {
        if ($settings->phase() !== Phase::Registration) {
            throw ValidationException::withMessages(['nick' => 'Registrace je uzavřená.']);
        }

        $this->ensureNotThrottled($request, 'register', 10, 'registration_code');

        $validated = $request->validate([
            'nick' => ['required', 'string', 'min:2', 'max:24', 'unique:players,nick'],
            'registration_code' => ['required', 'string'],
        ]);

        $expected = (string) config('tournament.registration_code');

        if ($expected === '' || ! hash_equals($expected, $validated['registration_code'])) {
            throw ValidationException::withMessages(['registration_code' => 'Neplatný registrační kód.']);
        }

        $player = Player::query()->create(['nick' => $validated['nick']]);

        Auth::login($player);
        $request->session()->regenerate();
        $audit->player($player, 'player.registered');

        return to_route('home');
    }
}
