<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureClubAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->teams()->where('is_personal', false)->wherePivot('status', 'active')->exists()) {
            return $next($request);
        }

        if ($user?->hasRole('trainer')) {
            return to_route('club.onboarding');
        }

        return Inertia::render('NoClub', [
            'user' => ['name' => $user?->name, 'email' => $user?->email],
        ])->toResponse($request)->setStatusCode(403);
    }
}
