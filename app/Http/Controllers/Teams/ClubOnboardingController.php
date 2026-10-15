<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CreateClubRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubOnboardingController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('teams/Onboarding');
    }

    public function store(CreateClubRequest $request, CreateTeam $createTeam): RedirectResponse
    {
        $team = $createTeam->handle($request->user(), $request->validated('name'));
        $team->update(['payment_status' => 'not_required']);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
