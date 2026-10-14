<?php

use App\Enums\TeamRole;
use App\Models\ClubMatch;
use App\Models\Gym;
use App\Models\Message;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\Training;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can visit the home page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('home'));
    $response->assertOk();
});

test('authenticated users can visit the home page', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Startseite')
            ->where('hasClub', false)
            ->where('trainerName', null)
            ->has('upcomingEvents', 0)
            ->has('news', 0),
        );
});

test('home page shows database events and news for the selected club', function () {
    $this->travelTo(now()->setDate(2026, 10, 14)->startOfDay());

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Demo Verein',
    ]);

    $team->members()->attach($user, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
        'status' => 'active',
    ]);
    $user->switchTeam($team);

    $firstMatch = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'date' => '2026-10-16',
        'versus' => 'TSV Nord',
        'at_home' => false,
        'city' => 'Hannover',
    ]);
    $secondMatch = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'date' => '2026-10-18',
        'versus' => 'SV Süd',
        'at_home' => true,
        'city' => 'Berlin',
    ]);
    $match = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'date' => '2026-10-20',
        'versus' => 'TSV West',
        'at_home' => true,
        'city' => 'Berlin',
    ]);
    ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'date' => '2026-10-21',
    ]);
    ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'date' => '2026-10-13',
    ]);

    $otherTeam = Team::factory()->create(['tenant_id' => $tenant->id]);
    ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $otherTeam->id,
        'date' => '2026-10-19',
    ]);

    $gym = Gym::factory()->create(['name' => 'Halle Nord']);
    $training = Training::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'gym_id' => $gym->id,
        'weekday' => 'Mittwoch',
        'starts_at' => '18:30:00',
        'ends_at' => '20:00:00',
    ]);

    $message = Message::factory()->create([
        'tenant_id' => $tenant->id,
        'title' => 'Neue Trainingszeiten',
    ]);
    $foreignMessage = Message::factory()->create([
        'title' => 'Andere Vereinsnachricht',
    ]);
    $user->messages()->attach([$message->id, $foreignMessage->id]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Startseite')
            ->where('upcomingEvents', [
                [
                    'id' => 'training-'.$training->id,
                    'title' => 'Training Demo Verein',
                    'date' => 'Mittwoch, 18:30-20:00 Uhr',
                    'place' => 'Halle Nord',
                ],
                [
                    'id' => 'match-'.$firstMatch->id,
                    'title' => 'Auswärtsspiel gegen TSV Nord',
                    'date' => '16.10.2026',
                    'place' => 'Hannover',
                ],
                [
                    'id' => 'match-'.$secondMatch->id,
                    'title' => 'Heimspiel gegen SV Süd',
                    'date' => '18.10.2026',
                    'place' => 'Berlin',
                ],
                [
                    'id' => 'match-'.$match->id,
                    'title' => 'Heimspiel gegen TSV West',
                    'date' => '20.10.2026',
                    'place' => 'Berlin',
                ],
            ])
            ->where('news', [
                [
                    'id' => $message->id,
                    'title' => 'Neue Trainingszeiten',
                    'date' => '14.10.2026',
                ],
            ]),
        );
});

test('home page shows pending team assignments for members without a club', function () {
    $member = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($member, [
        'role' => TeamRole::Member->value,
        'status' => 'pending',
    ]);

    $this->actingAs($member)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Startseite')
            ->where('hasClub', false)
            ->has('pendingMembers', 1)
            ->where('pendingMembers.0.team.name', 'Laravel Team')
            ->where('pendingMembers.0.team.slug', $team->slug),
        );
});

test('trainers without a club are sent to club setup', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('club.onboarding'));
});

test('home page shows when the selected team has no assigned trainer', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Startseite')
            ->where('hasClub', true)
            ->where('trainerName', null),
        );
});

test('home page shows the trainer assigned to the selected team', function () {
    $owner = User::factory()->create();
    $trainer = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($trainer, [
        'role' => TeamRole::Admin->value,
        'status' => 'active',
    ]);
    $owner->switchTeam($team);

    $this->actingAs($owner)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Startseite')
            ->where('hasClub', true)
            ->where('trainerName', $trainer->name),
        );
});

test('home page includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Startseite')
        ->where('hasClub', false)
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('home page does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Startseite')
        ->where('hasClub', false)
        ->has('pendingInvitations', 0),
    );
});

test('home page excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Startseite')
        ->where('hasClub', false)
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('home page does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Startseite')
        ->where('hasClub', false)
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});
