<?php

namespace App\Http\Controllers;

use App\Models\ClubMatch;
use App\Models\Membership;
use App\Models\Message;
use App\Models\TeamInvitation;
use App\Models\Training;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use UnexpectedValueException;

class StartseiteController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (! $request->user()) {
            return Inertia::render('Welcome');
        }

        $user = $request->user();
        $activeClubTeams = $user->teams()
            ->where('is_personal', false)
            ->wherePivot('status', 'active');
        $hasClub = (clone $activeClubTeams)->exists();

        if ($user->hasRole('trainer') && ! $hasClub) {
            return to_route('club.onboarding');
        }

        $currentTeam = (clone $activeClubTeams)
            ->where('teams.id', $user->current_team_id)
            ->first();
        $logoTeam = $currentTeam ?? (clone $activeClubTeams)->first();
        $email = strtolower($user->email);
        $now = now();

        $pendingMembers = Membership::query()
            ->with('team')
            ->where('user_id', $user->id)
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

        $upcomingMatches = $currentTeam === null
            ? collect()
            : $currentTeam->matches()
                ->where('matches.tenant_id', $currentTeam->tenant_id)
                ->whereDate('matches.date', '>=', $now->toDateString())
                ->orderBy('matches.date')
                ->orderBy('matches.id')
                ->limit(4)
                ->get(['matches.id', 'matches.date', 'matches.versus', 'matches.at_home', 'matches.city'])
                ->map(fn (ClubMatch $match) => [
                    'id' => 'match-'.$match->id,
                    'title' => ($match->at_home ? 'Heimspiel gegen ' : 'Auswärtsspiel gegen ').$match->versus,
                    'date' => $match->date->format('d.m.Y'),
                    'place' => $match->city ?: 'Spielort nicht angegeben',
                    'sortAt' => $match->date->copy()->startOfDay()->getTimestamp(),
                ]);

        $upcomingTrainings = $currentTeam === null
            ? collect()
            : $currentTeam->trainings()
                ->with('gym:id,name')
                ->where('trainings.tenant_id', $currentTeam->tenant_id)
                ->orderBy('trainings.weekday')
                ->orderBy('trainings.starts_at')
                ->orderBy('trainings.id')
                ->get(['trainings.id', 'trainings.gym_id', 'trainings.weekday', 'trainings.starts_at', 'trainings.ends_at'])
                ->map(fn (Training $training) => [
                    'id' => 'training-'.$training->id,
                    'title' => 'Training '.$currentTeam->name,
                    'date' => $training->weekday.', '.substr((string) $training->starts_at, 0, 5).'-'.substr((string) $training->ends_at, 0, 5).' Uhr',
                    'place' => $training->gym?->name ?? 'Halle nicht angegeben',
                    'sortAt' => $this->nextTrainingAt($training, $now)->getTimestamp(),
                ]);

        $upcomingEvents = $upcomingMatches
            ->concat($upcomingTrainings)
            ->sortBy('sortAt')
            ->take(4)
            ->map(function (array $event): array {
                unset($event['sortAt']);

                return $event;
            })
            ->values();

        $news = $currentTeam === null
            ? collect()
            : $user->messages()
                ->where('messages.tenant_id', $currentTeam->tenant_id)
                ->latest('messages.created_at')
                ->limit(4)
                ->get(['messages.id', 'messages.title', 'messages.created_at'])
                ->map(fn (Message $message) => [
                    'id' => $message->id,
                    'title' => $message->title,
                    'date' => $message->created_at?->format('d.m.Y'),
                ]);

        return Inertia::render('Startseite', [
            'hasClub' => $hasClub,
            'upcomingEvents' => $upcomingEvents,
            'news' => $news->values(),
            'pendingInvitations' => $pendingInvitations,
            'pendingMembers' => $pendingMembers,
            'trainerName' => $currentTeam?->assignedTrainer()->first(['users.id', 'users.name'])?->name,
            'clubLogoUrl' => $logoTeam?->logo_path
                ? Storage::disk('public')->url($logoTeam->logo_path)
                : null,
        ]);
    }

    private function nextTrainingAt(Training $training, CarbonInterface $now): CarbonInterface
    {
        $weekday = match (mb_strtolower($training->weekday)) {
            'montag', 'monday' => 1,
            'dienstag', 'tuesday' => 2,
            'mittwoch', 'wednesday' => 3,
            'donnerstag', 'thursday' => 4,
            'freitag', 'friday' => 5,
            'samstag', 'saturday' => 6,
            'sonntag', 'sunday' => 7,
            default => throw new UnexpectedValueException('Unknown training weekday: '.$training->weekday),
        };

        $daysUntilTraining = ($weekday - $now->isoWeekday() + 7) % 7;
        $trainingAt = $now->copy()
            ->startOfDay()
            ->addDays($daysUntilTraining)
            ->setTimeFromTimeString((string) $training->starts_at);

        if ($trainingAt->lt($now)) {
            $trainingAt = $trainingAt->addWeek();
        }

        return $trainingAt;
    }
}
