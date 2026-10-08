<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\ConfirmTeamPayment;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\StripeCheckoutService;
use App\Services\Teams\TeamPermissionSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TeamPaymentController extends Controller
{
    public function __construct(private TeamPermissionSynchronizer $permissionSynchronizer) {}

    public function edit(Request $request, Team $team, StripeCheckoutService $stripeCheckout): Response
    {
        Gate::authorize('update', $team);

        return Inertia::render('Bezahlung', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
                'paymentStatus' => $team->payment_status,
                'paidAt' => $team->payment_paid_at?->toISOString(),
                'amount' => number_format($stripeCheckout->annualAccessAmountInCents() / 100, 2, ',', '.').' €',
                'allowSkip' => app()->environment(['local', 'testing']),
                'checkoutState' => match ($request->query('checkout')) {
                    'cancelled' => 'cancelled',
                    'pending' => 'pending',
                    default => null,
                },
            ],
        ]);
    }

    public function update(Request $request, Team $team, StripeCheckoutService $stripeCheckout): SymfonyResponse
    {
        Gate::authorize('update', $team);

        if ($team->payment_status !== 'pending') {
            return to_route('teams.edit', $team);
        }

        $session = $stripeCheckout->createOrReuseSession(
            $team,
            $request->user(),
            route('teams.payment.complete', ['team' => $team->slug]).'?session_id={CHECKOUT_SESSION_ID}',
            route('teams.payment.edit', ['team' => $team->slug, 'checkout' => 'cancelled']),
        );

        if ($session === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Die Zahlung wird noch bestätigt. Bitte aktualisiere die Seite in Kürze.']);

            return to_route('teams.payment.edit', ['team' => $team->slug, 'checkout' => 'pending']);
        }

        return Inertia::location($session['url']);
    }

    public function complete(
        Request $request,
        Team $team,
        StripeCheckoutService $stripeCheckout,
        ConfirmTeamPayment $confirmTeamPayment,
    ): RedirectResponse {
        Gate::authorize('update', $team);

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'starts_with:cs_test_', 'max:255'],
        ]);
        $sessionId = $validated['session_id'];
        $payer = $request->user();

        if (! $payer instanceof User) {
            abort(401);
        }

        if ($team->payment_reference !== $sessionId && $team->payment_status !== 'skipped') {
            abort(403);
        }

        $session = $stripeCheckout->retrieveSession($sessionId);

        if (! $stripeCheckout->belongsToTeamAndPayer($session, $team, $payer)) {
            abort(403);
        }

        if (! $stripeCheckout->isPaidForTeamAndPayer($session, $team, $payer)) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Die Zahlung wird noch bestätigt. Bitte aktualisiere die Seite in Kürze.']);

            return to_route('teams.payment.edit', ['team' => $team->slug, 'checkout' => 'pending']);
        }

        $confirmTeamPayment->handle($team, $payer, $sessionId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zahlung erfolgreich bestätigt.']);

        return to_route('teams.edit', $team);
    }

    public function skip(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        abort_unless(app()->environment(['local', 'testing']), 404);

        $user = $request->user();

        DB::transaction(function () use ($team, $user): void {
            $lockedTeam = Team::query()
                ->whereKey($team->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(in_array($lockedTeam->payment_status, ['pending', 'skipped'], true), 409);

            $lockedTeam->update([
                'payment_status' => 'skipped',
                'payment_reference' => 'SKIP-'.strtoupper(Str::random(10)),
                'payment_paid_at' => null,
            ]);

            $membership = $lockedTeam->memberships()->updateOrCreate(
                ['user_id' => $user->id],
                ['role' => TeamRole::Owner],
            );

            $this->permissionSynchronizer->synchronizeMembership($membership);

            $user->switchTeam($lockedTeam);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zahlung zu Testzwecken übersprungen.']);

        return to_route('teams.edit', $team);
    }
}
