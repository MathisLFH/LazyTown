<?php

use App\Enums\TeamRole;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Teams\StripeCheckoutService;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration screen includes team invitation context', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('register', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/Register')
        ->where('teamInvitation.code', $invitation->code)
        ->where('teamInvitation.teamName', 'Laravel Team'),
    );
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $response->assertRedirect(route('home'));
});

test('clubs can register an administrator and subscription without storing a payment method', function () {
    config(['app.url' => 'https://lazytown.test']);
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_registration';

    $stripeCheckout = Mockery::mock(StripeCheckoutService::class);
    $stripeCheckout->shouldReceive('createOrReuseSession')
        ->once()
        ->andReturnUsing(function (
            Team $team,
            User $payer,
            string $successUrl,
            string $cancelUrl,
        ) use ($checkoutUrl): array {
            expect($payer->email)->toBe('club-admin@example.com')
                ->and($team->payment_status)->toBe('pending')
                ->and($successUrl)->toStartWith('https://lazytown.test/settings/teams/')
                ->and($successUrl)->toContain($team->slug.'/payment/complete?session_id={CHECKOUT_SESSION_ID}')
                ->and($cancelUrl)->toBe('https://lazytown.test/register/payment?checkout=cancelled');

            return ['id' => 'cs_test_registration', 'url' => $checkoutUrl];
        });
    $this->instance(StripeCheckoutService::class, $stripeCheckout);

    $response = $this->withHeader('X-Inertia', 'true')->post(route('register.store'), [
        'club_name' => 'Ninjas in Pyjamas',
        'subdomain' => 'NINJAS-in-pyjamas',
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'club-admin@example.com',
        'birth_date' => '1990-01-01',
        'birth_place' => 'Berlin',
        'nationality' => 'Deutsch',
        'address' => 'Musterstraße 1',
        'postcode' => '01234',
        'city' => 'Berlin',
        'subscription' => 'premium',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', $checkoutUrl);
    $this->assertAuthenticated();

    $user = User::where('email', 'club-admin@example.com')->firstOrFail();
    $tenant = Tenant::where('subdomain', 'ninjas-in-pyjamas')->firstOrFail();
    $subscription = Subscription::where('type', 'premium')->firstOrFail();
    $team = $tenant->teams()->firstOrFail();

    expect($user->name)->toBe('Max Mustermann')
        ->and($user->tenant_id)->toBe($tenant->id)
        ->and($user->roles)->toBe(['verwaltung'])
        ->and($user->active_role)->toBe('verwaltung')
        ->and($tenant->admin_id)->toBe($user->id)
        ->and($tenant->name)->toBe('Ninjas in Pyjamas')
        ->and($tenant->url())->toBe('https://ninjas-in-pyjamas.lazytown.test')
        ->and($team->tenant_id)->toBe($tenant->id)
        ->and($team->payment_status)->toBe('pending')
        ->and($team->members()->whereKey($user->id)->exists())->toBeFalse()
        ->and($user->current_team_id)->toBeNull()
        ->and($tenant->subscriptions()->whereKey($subscription->id)->exists())->toBeTrue()
        ->and($tenant->subscriptions()->firstOrFail()->pivot->payment_type)->toBeNull();
});

test('premium json registrations return a stripe checkout url without activating the team', function () {
    config(['app.url' => 'https://lazytown.test']);
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_json_registration';

    $stripeCheckout = Mockery::mock(StripeCheckoutService::class);
    $stripeCheckout->shouldReceive('createOrReuseSession')
        ->once()
        ->andReturn(['id' => 'cs_test_json_registration', 'url' => $checkoutUrl]);
    $this->instance(StripeCheckoutService::class, $stripeCheckout);

    $response = $this->postJson(route('register.store'), [
        'club_name' => 'JSON Club',
        'subdomain' => 'json-club',
        'first_name' => 'Json',
        'last_name' => 'Admin',
        'email' => 'json-admin@example.com',
        'subscription' => 'premium',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('two_factor', false)
        ->assertJsonPath('checkout_url', $checkoutUrl);

    $user = User::where('email', 'json-admin@example.com')->firstOrFail();
    $team = $user->tenant->teams()->firstOrFail();

    expect($team->members()->whereKey($user->id)->exists())->toBeFalse()
        ->and($team->payment_status)->toBe('pending');
});

test('free club registrations do not wait for payment', function () {
    config(['app.url' => 'https://lazytown.test']);

    $response = $this->post(route('register.store'), [
        'club_name' => 'Local Club',
        'subdomain' => 'local-club',
        'first_name' => 'Eva',
        'last_name' => 'Beispiel',
        'email' => 'free-club@example.com',
        'subscription' => 'free',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('https://local-club.lazytown.test');

    $team = Team::where('name', 'Local Club')->firstOrFail();

    expect($team->payment_status)->toBe('not_required')
        ->and($team->tenant->subscriptions()->firstOrFail()->type)->toBe('free')
        ->and($team->tenant->subscriptions()->firstOrFail()->price)->toBe('0.00')
        ->and($team->tenant->subscriptions()->firstOrFail()->pivot->payment_type)->toBeNull();
});

test('tenant subdomains resolve to the matching tenant', function () {
    config(['app.url' => 'https://lazytown.test']);
    $tenant = Tenant::factory()->create([
        'name' => 'Ninjas in Pyjamas',
        'subdomain' => 'ninjas-in-pyjamas',
    ]);

    $this->get('https://ninjas-in-pyjamas.lazytown.test/register')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tenant.name', $tenant->name)
            ->where('tenant.subdomain', $tenant->subdomain),
        );
});

test('unknown tenant subdomains return not found', function () {
    config(['app.url' => 'https://lazytown.test']);

    $this->get('https://missing.lazytown.test/register')
        ->assertNotFound();
});

test('registration starts a player without assigning a club', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Trainer User',
        'email' => 'trainer@example.com',
        'start_role' => 'trainer',
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    expect(User::where('email', 'trainer@example.com')->first()->roles)->toBe(['spieler']);
});
