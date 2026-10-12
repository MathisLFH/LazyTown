<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToMemberProfileCompletion;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    use RedirectsToMemberProfileCompletion;

    public function toResponse($request): Response
    {
        $redirect = rtrim((string) config('app.url'), '/');

        if ($completionUrl = $this->memberProfileCompletionUrl($request)) {
            return $request->wantsJson()
                ? new JsonResponse(['two_factor' => false, 'redirect' => $completionUrl], 200)
                : Inertia::location($completionUrl);
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false, 'redirect' => $redirect], 200)
            : Inertia::location($redirect);
    }
}
