<?php

namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Per-IP rate limit for login forms that reports as a validation error,
 * so the form shows a readable message instead of a bare 429 page.
 */
trait ThrottlesAttempts
{
    protected function ensureNotThrottled(Request $request, string $name, int $maxAttempts, string $field): void
    {
        $key = $name.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                $field => 'Příliš mnoho pokusů. Zkus to znovu za '.RateLimiter::availableIn($key).' s.',
            ]);
        }

        RateLimiter::hit($key, 60);
    }
}
