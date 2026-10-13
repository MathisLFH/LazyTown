<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInitialPasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user()?->must_change_password
            && ! $request->routeIs('member.profile-completion.*', 'logout')
        ) {
            return to_route('member.profile-completion.edit');
        }

        return $next($request);
    }
}
