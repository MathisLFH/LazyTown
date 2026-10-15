<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\ConfirmTeamPayment;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamPaymentController extends Controller
{
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

        if ($team->payment_reference !== $sessionId) {
            abort(403);
        }

        $session = $stripeCheckout->retrieveSession($sessionId);

        if (! $stripeCheckout->belongsToTeamAndPayer($session, $team, $payer)) {
            abort(403);
        }

        if (! $stripeCheckout->isPaidForTeamAndPayer($session, $team, $payer)) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Die Zahlung wird noch bestätigt. Bitte aktualisiere die Seite in Kürze.']);

            return to_route('register.payment', ['checkout' => 'pending']);
        }

        $confirmTeamPayment->handle($team, $payer, $sessionId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zahlung erfolgreich bestätigt.']);

        return $team->tenant
            ? redirect()->to($team->tenant->url())
            : to_route('teams.edit', $team);
    }
}
