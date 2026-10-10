<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToMemberProfileCompletion;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    use RedirectsToMemberProfileCompletion;

    public function toResponse($request): Response
    {
        $redirect = $request->user()?->tenant?->url() ?? route('home');

        if ($completionUrl = $this->memberProfileCompletionUrl($request)) {
            return $request->wantsJson()
                ? new JsonResponse(['two_factor' => false, 'redirect' => $completionUrl], 200)
                : redirect()->to($completionUrl);
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($redirect);
    }
}
