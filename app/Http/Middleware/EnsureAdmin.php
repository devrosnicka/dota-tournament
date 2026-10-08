<?php

namespace App\Http\Middleware;

use App\Http\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! AdminSession::check($request)) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
