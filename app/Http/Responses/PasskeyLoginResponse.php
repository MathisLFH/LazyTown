<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToMemberProfileCompletion;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    use RedirectsToMemberProfileCompletion;

    public function toResponse($request): Response
    {
        $redirect = rtrim((string) config('app.url'), '/');
        $completionUrl = $this->memberProfileCompletionUrl($request);

        return $request->wantsJson()
            ? new JsonResponse(['redirect' => $completionUrl ?? $redirect], 200)
            : Inertia::location($completionUrl ?? $redirect);
    }
}
