<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivacyConsent
{
    /**
     * Redirect authenticated users who have not accepted the current policy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('privacy.consent.*')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && (
            $user->privacy_policy_accepted_at === null
            || $user->privacy_policy_version !== config('privacy.policy_version')
        )) {
            return to_route('privacy.consent.edit');
        }

        return $next($request);
    }
}
