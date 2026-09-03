<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\ProcessTeamPaymentRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamPaymentController extends Controller
{
    public function edit(Request $request, Team $team): Response
    {
        Gate::authorize('update', $team);

        return Inertia::render('Bezahlung', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
                'paymentStatus' => $team->payment_status,
                'paidAt' => $team->payment_paid_at?->toISOString(),
            ],
        ]);
    }

    public function update(ProcessTeamPaymentRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $this->completePayment($request, $team, 'paid', 'PAY-');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zahlung erfolgreich verarbeitet.']);

        return to_route('teams.edit', $team);
    }

    public function skip(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $this->completePayment($request, $team, 'skipped', 'SKIP-');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zahlung zu Testzwecken übersprungen.']);

        return to_route('teams.edit', $team);
    }

    private function completePayment(Request $request, Team $team, string $status, string $referencePrefix): void
    {
        $user = $request->user();
        $roles = $user->roles ?: ['spieler'];

        if (! in_array('trainer', $roles, true)) {
            $roles[] = 'trainer';
        }

        DB::transaction(function () use ($team, $user, $roles, $status, $referencePrefix): void {
            $team->update([
                'payment_status' => $status,
                'payment_reference' => $referencePrefix.strtoupper(Str::random(10)),
                'payment_paid_at' => now(),
            ]);

            $user->update([
                'roles' => $roles,
                'active_role' => 'trainer',
            ]);

            $team->memberships()->updateOrCreate(
                ['user_id' => $user->id],
                ['role' => TeamRole::Owner],
            );

            $user->switchTeam($team);
        });
    }
}
