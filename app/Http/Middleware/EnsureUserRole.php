<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $requiredRole): Response
    {
        if (! $request->user()?->hasRole($requiredRole)) {
            return Inertia::render('errors/PermissionDenied', [
                'requiredRole' => $requiredRole,
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
