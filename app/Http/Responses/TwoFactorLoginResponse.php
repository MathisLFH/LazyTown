<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToMemberProfileCompletion;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use RedirectsToMemberProfileCompletion;

    public function toResponse($request): Response
    {
        if ($completionUrl = $this->memberProfileCompletionUrl($request)) {
            return $request->wantsJson()
                ? new JsonResponse(['two_factor' => false, 'redirect' => $completionUrl], 200)
                : Inertia::location($completionUrl);
        }

        $redirect = rtrim((string) config('app.url'), '/');

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false, 'redirect' => $redirect], 200)
            : Inertia::location($redirect);
    }
}
