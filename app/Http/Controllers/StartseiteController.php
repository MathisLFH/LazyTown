<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StartseiteController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (! $request->user()) {
            return Inertia::render('Welcome');
        }

        if ($request->user()->hasRole('trainer') && $request->user()->teams()->where('is_personal', false)->wherePivot('status', 'active')->doesntExist()) {
            return to_route('club.onboarding');
        }

        $email = strtolower($request->user()->email);

        $pendingMembers = Membership::query()
            ->with('team')
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->get()
            ->map(fn (Membership $membership) => [
                'team' => ['name' => $membership->team->name, 'slug' => $membership->team->slug],
            ]);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        if ($request->user()->teams()->where('is_personal', false)->wherePivot('status', 'active')->doesntExist()) {
            return Inertia::render('NoClub', [
                'user' => [
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],
                'pendingInvitations' => $pendingInvitations,
                'pendingMembers' => $pendingMembers,
            ]);
        }

        return Inertia::render('Startseite', [
            'pendingInvitations' => $pendingInvitations,
        ]);
    }
}
