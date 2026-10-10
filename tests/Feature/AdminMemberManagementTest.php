<?php

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
