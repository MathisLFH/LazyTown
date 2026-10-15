<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RegistrationPaymentController extends Controller
{
    public function show(Request $request, StripeCheckoutService $stripeCheckout): Response|RedirectResponse
    {
        $team = $this->pendingTeam($request);

        if (! $team instanceof Team) {
            return to_route('home');
        }

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('auth/Register', [
            'annualAccessPrice' => $stripeCheckout->annualAccessPriceLabel(),
            'pendingRegistration' => [
                'club' => ['name' => $team->name, 'subdomain' => $user->tenant->subdomain],
                'admin' => $user->only([
                    'first_name', 'last_name', 'email', 'birth_date', 'birth_place',
                    'nationality', 'address', 'postcode', 'city',
                ]),
                'checkoutState' => match ($request->query('checkout')) {
                    'cancelled' => 'cancelled',
                    'pending' => 'pending',
                    default => null,
                },
            ],
        ]);
    }

    public function store(Request $request, StripeCheckoutService $stripeCheckout): SymfonyResponse
    {
        $team = $this->pendingTeam($request);

        if (! $team instanceof Team) {
            return to_route('home');
        }

        $session = $stripeCheckout->createOrReuseSession(
            $team,
            $request->user(),
            route('teams.payment.complete', ['team' => $team->slug]).'?session_id={CHECKOUT_SESSION_ID}',
            route('register.payment', ['checkout' => 'cancelled']),
        );

        if ($session === null) {
            return to_route('register.payment', ['checkout' => 'pending']);
        }

        return Inertia::location($session['url']);
    }

    private function pendingTeam(Request $request): ?Team
    {
        $user = $request->user();

        return $user?->tenant?->teams()
            ->where('created_by', $user->id)
            ->where('is_personal', false)
            ->where('payment_status', 'pending')
            ->orderByDesc('id')
            ->first();
    }
}
