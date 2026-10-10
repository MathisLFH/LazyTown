<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('new members are redirected to complete their profile before using the site', function () {
    $user = User::factory()->create();
    $user->forceFill(['must_change_password' => true])->save();

    $this->actingAs($user)
        ->get(route('startseite'))
        ->assertRedirect(route('member.profile-completion.edit'));

    $this->get(route('member.profile-completion.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/CompleteMemberProfile')
            ->where('member.email', $user->email),
        );
});

test('initial member login redirects directly to profile completion', function () {
    config(['app.url' => 'https://lazytown.test']);
    $tenant = Tenant::factory()->create(['subdomain' => 'new-member']);
    $member = User::factory()->create(['tenant_id' => $tenant->id]);
    $member->forceFill(['must_change_password' => true])->save();

    $this->post('https://lazytown.test/login', [
        'email' => $member->email,
        'password' => 'password',
    ])->assertRedirect($tenant->url().route('member.profile-completion.edit', absolute: false));
});

test('members can complete their profile and replace their initial password', function () {
    config(['app.url' => 'https://lazytown.test']);
    $tenant = Tenant::factory()->create(['subdomain' => 'profile-completion']);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->forceFill(['must_change_password' => true])->save();

    $this->actingAs($user)
        ->patch(route('member.profile-completion.update'), [
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => $user->email,
            'birth_date' => '1990-01-01',
            'birth_place' => 'Berlin',
            'nationality' => 'Deutsch',
            'address' => 'Musterstraße 1',
            'postcode' => '01234',
            'city' => 'Berlin',
            'password' => 'StrongPassword42!',
            'password_confirmation' => 'StrongPassword42!',
        ])
        ->assertRedirect($tenant->url());

    $user = $user->fresh();

    expect($user->name)->toBe('Max Mustermann')
        ->and($user->first_name)->toBe('Max')
        ->and($user->last_name)->toBe('Mustermann')
        ->and($user->birth_date->toDateString())->toBe('1990-01-01')
        ->and($user->birth_place)->toBe('Berlin')
        ->and($user->nationality)->toBe('Deutsch')
        ->and($user->address)->toBe('Musterstraße 1')
        ->and($user->postcode)->toBe('01234')
        ->and($user->city)->toBe('Berlin')
        ->and($user->must_change_password)->toBeFalse()
        ->and(Hash::check('StrongPassword42!', $user->password))->toBeTrue();

    $this->get(route('startseite'))->assertOk();
});

test('profile completion rejects missing identity data and mismatched weak passwords', function () {
    $user = User::factory()->create();
    $user->forceFill(['must_change_password' => true])->save();

    $this->actingAs($user)
        ->from(route('member.profile-completion.edit'))
        ->patch(route('member.profile-completion.update'), [
            'first_name' => '',
            'last_name' => 'Mustermann',
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
        ->assertSessionHasErrors(['first_name', 'password']);

    expect($user->fresh()->must_change_password)->toBeTrue()
        ->and(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('members cannot keep the initial password when completing their profile', function () {
    $user = User::factory()->create();
    $user->forceFill(['must_change_password' => true])->save();

    $this->actingAs($user)
        ->from(route('member.profile-completion.edit'))
        ->patch(route('member.profile-completion.update'), [
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});

test('members can log out before completing their profile', function () {
    $user = User::factory()->create();
    $user->forceFill(['must_change_password' => true])->save();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
