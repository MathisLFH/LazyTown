<?php

namespace App\Http\Responses;

use App\Models\Team;
use App\Models\User;
use App\Services\Teams\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function __construct(private StripeCheckoutService $stripeCheckout) {}

    public function toResponse($request): Response
    {
        $user = $request->user();
        $tenant = $user?->tenant;
        $pendingPremiumTeam = $user instanceof User && $tenant
            ? $tenant->teams()
                ->where('created_by', $user->id)
                ->where('is_personal', false)
                ->where('payment_status', 'pending')
                ->orderByDesc('id')
                ->first()
            : null;

        if (! $user instanceof User || ! $tenant || ! $pendingPremiumTeam instanceof Team) {
            return $request->wantsJson()
                ? new JsonResponse(['two_factor' => false], 201)
                : ($tenant
                    ? redirect()->to($tenant->url())
                    : redirect()->route('home'));
        }

        $applicationBaseUrl = rtrim((string) config('app.url'), '/');
        $paymentPageUrl = $applicationBaseUrl.route('teams.payment.edit', [
            'team' => $pendingPremiumTeam->slug,
        ], false);
        $checkout = $this->stripeCheckout->createOrReuseSession(
            $pendingPremiumTeam,
            $user,
            $applicationBaseUrl.route('teams.payment.complete', [
                'team' => $pendingPremiumTeam->slug,
            ], false).'?session_id={CHECKOUT_SESSION_ID}',
            $applicationBaseUrl.route('teams.payment.edit', [
                'team' => $pendingPremiumTeam->slug,
                'checkout' => 'cancelled',
            ], false),
        );

        if ($checkout === null) {
            return $request->wantsJson()
                ? new JsonResponse(['two_factor' => false, 'payment_url' => $paymentPageUrl], 201)
                : redirect()->to($paymentPageUrl);
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false, 'checkout_url' => $checkout['url']], 201)
            : Inertia::location($checkout['url']);
    }
}
