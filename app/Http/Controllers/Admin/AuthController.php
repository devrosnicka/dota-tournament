<?php

namespace App\Http\Controllers\Admin;

use App\Http\AdminSession;
use App\Http\Controllers\Controller;
use App\Http\ThrottlesAttempts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    use ThrottlesAttempts;

    public function create(Request $request): Response|RedirectResponse
    {
        if (AdminSession::check($request)) {
            return to_route('admin.dashboard');
        }

        return Inertia::render('admin/login');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureNotThrottled($request, 'admin-login', 5, 'password');

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $expected = (string) config('tournament.admin_password');

        if ($expected === '' || ! hash_equals($expected, $validated['password'])) {
            throw ValidationException::withMessages(['password' => 'Nesprávné heslo.']);
        }

        AdminSession::grant($request);

        return to_route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminSession::revoke($request);

        return to_route('admin.login');
    }
}
