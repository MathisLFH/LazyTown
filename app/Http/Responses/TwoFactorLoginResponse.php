<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use App\Http\Responses\Concerns\RedirectsToMemberProfileCompletion;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use RedirectsToCurrentTeam;
    use RedirectsToMemberProfileCompletion;

    public function toResponse($request): Response
    {
        if ($completionUrl = $this->memberProfileCompletionUrl($request)) {
            return $request->wantsJson()
                ? new JsonResponse(['two_factor' => false, 'redirect' => $completionUrl], 200)
                : redirect()->to($completionUrl);
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($this->redirectPathForCurrentTeam($request, Fortify::redirects('login')));
    }
}
