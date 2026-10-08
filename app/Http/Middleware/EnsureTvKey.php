<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only TV view behind the shared TV_KEY (SPEC §2.2).
 */
class EnsureTvKey
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('tournament.tv_key');

        abort_if($expected === '' || ! hash_equals($expected, $request->string('key')->toString()), 404);

        return $next($request);
    }
}
