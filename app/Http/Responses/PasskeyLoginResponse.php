<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToMemberProfileCompletion;
use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    use RedirectsToMemberProfileCompletion;

    public function toResponse($request): Response
    {
        $redirect = $request->user()?->tenant?->url() ?? route('home');
        $completionUrl = $this->memberProfileCompletionUrl($request);

        return $request->wantsJson()
            ? new JsonResponse(['redirect' => $completionUrl ?? redirect()->intended($redirect)->getTargetUrl()], 200)
            : ($completionUrl ? redirect()->to($completionUrl) : redirect()->intended($redirect));
    }
}
