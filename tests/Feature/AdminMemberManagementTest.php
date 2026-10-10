<?php

use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('administrators see every member from their tenant only', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $member = User::factory()->create(['tenant_id' => $tenant->id]);
    $otherTenantMember = User::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/members/Index')
            ->has('members', 2)
            ->where('members.0.id', fn ($id) => in_array($id, [$administrator->id, $member->id], true))
            ->where('members.1.id', fn ($id) => in_array($id, [$administrator->id, $member->id], true))
            ->where('members.0.id', fn ($id) => $id !== $otherTenantMember->id)
            ->where('members.1.id', fn ($id) => $id !== $otherTenantMember->id),
        );
});

test('administrators can create a member account in their tenant', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);

    $this->actingAs($administrator)
        ->post(route('admin.members.store'), [
            'email' => 'new-member@example.com',
            'password' => 'temporary-password',
        ])
        ->assertRedirect(route('admin.members.index'));

    $member = User::where('email', 'new-member@example.com')->firstOrFail();

    expect($member->tenant_id)->toBe($tenant->id)
        ->and($member->roles)->toBe(['spieler'])
        ->and($member->active_role)->toBe('spieler')
        ->and($member->must_change_password)->toBeTrue()
        ->and(Hash::check('temporary-password', $member->password))->toBeTrue();
});

test('member account creation rejects duplicate emails and weak passwords', function () {
    $administrator = User::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $existingMember = User::factory()->create();

    $this->actingAs($administrator)
        ->from(route('admin.members.index'))
        ->post(route('admin.members.store'), [
            'email' => $existingMember->email,
            'password' => 'short',
        ])
        ->assertSessionHasErrors(['email', 'password']);

    expect(User::where('email', $existingMember->email)->count())->toBe(1);
});

test('only administrators can view and create members', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $this->actingAs($user)
        ->get(route('admin.members.index'))
        ->assertForbidden();

    $this->post(route('admin.members.store'), [
        'email' => 'unauthorized@example.com',
        'password' => 'temporary-password',
    ])->assertForbidden();

    expect(User::where('email', 'unauthorized@example.com')->exists())->toBeFalse();
});

test('administrators can delete another tenant member after confirming their name', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $member = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Zu löschendes Mitglied',
    ]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $team->members()->attach($administrator, [
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);
    $team->members()->attach($member, [
        'tenant_id' => $tenant->id,
        'role' => 'member',
    ]);

    $this->actingAs($administrator)
        ->delete(route('admin.members.destroy', $member), [
            'name' => 'Zu löschendes Mitglied',
        ])
        ->assertRedirect(route('admin.members.index'));

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
    $this->assertModelExists($team);
    expect($team->memberships()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('member deletion requires the exact member name', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $member = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($administrator)
        ->from(route('admin.members.index'))
        ->delete(route('admin.members.destroy', $member), ['name' => 'Wrong name'])
        ->assertSessionHasErrors('name');

    $this->assertModelExists($member);
});

test('administrators cannot delete themselves, the tenant administrator, or a team owner', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenantAdministrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $tenantAdministrator->id]);
    $teamOwner = User::factory()->create(['tenant_id' => $tenant->id]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $team->members()->attach($teamOwner, [
        'tenant_id' => $tenant->id,
        'role' => 'owner',
    ]);

    $this->actingAs($administrator)
        ->delete(route('admin.members.destroy', $administrator), ['name' => $administrator->name])
        ->assertForbidden();

    $this->delete(route('admin.members.destroy', $tenantAdministrator), ['name' => $tenantAdministrator->name])
        ->assertForbidden();

    $this->delete(route('admin.members.destroy', $teamOwner), ['name' => $teamOwner->name])
        ->assertForbidden();

    $this->assertModelExists($administrator);
    $this->assertModelExists($tenantAdministrator);
    $this->assertModelExists($teamOwner);
    $this->assertModelExists($team);
});

test('member deletion hides foreign tenant members and forbids non administrators', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $foreignMember = User::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
    ]);
    $trainer = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['trainer'],
        'active_role' => 'trainer',
    ]);

    $this->actingAs($administrator)
        ->delete(route('admin.members.destroy', $foreignMember), ['name' => $foreignMember->name])
        ->assertNotFound();

    $this->actingAs($trainer)
        ->delete(route('admin.members.destroy', $foreignMember), ['name' => $foreignMember->name])
        ->assertForbidden();

    $this->assertModelExists($foreignMember);
});
