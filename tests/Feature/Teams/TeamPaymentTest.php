<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Teams\StripeCheckoutService;
use Inertia\Testing\AssertableInertia as Assert;
use Stripe\Checkout\Session;
use Stripe\Event;

test('trainers can open club onboarding before payment', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $this->actingAs($user)
        ->get(route('club.onboarding'))
        ->assertOk();
});

function pendingClubRegistration(): array
{
    $user = User::factory()->create(['roles' => ['verwaltung'], 'active_role' => 'verwaltung']);
    $tenant = Tenant::factory()->create(['admin_id' => $user->id, 'subdomain' => 'pending-club']);
    $user->update(['tenant_id' => $tenant->id]);
    $team = Team::factory()->create([
        'tenant_id' => $tenant->id,
        'created_by' => $user->id,
        'is_personal' => false,
        'payment_status' => 'pending',
    ]);

    return [$user, $tenant, $team];
}

test('a pending registration can be resumed with its saved data', function () {
    [$user, , $team] = pendingClubRegistration();

    $this->actingAs($user)
        ->get(route('register.payment', ['checkout' => 'cancelled']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Register')
            ->where('pendingRegistration.club.name', $team->name)
            ->where('pendingRegistration.club.subdomain', 'pending-club')
            ->where('pendingRegistration.admin.email', $user->email)
            ->where('pendingRegistration.checkoutState', 'cancelled')
            ->where('annualAccessPrice', '29,99 €'),
        );
});

test('resuming without a pending registration returns home', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('register.payment'))
        ->assertRedirect(route('home'));
});

test('a new checkout reuses the pending registration without creating records', function () {
    [$user, , $team] = pendingClubRegistration();
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/test';
    $userCount = User::count();
    $teamCount = Team::count();

    $stripeCheckout = Mockery::mock(StripeCheckoutService::class);
    $stripeCheckout->shouldReceive('createOrReuseSession')
        ->once()
        ->andReturnUsing(function (
            Team $checkoutTeam,
            User $payer,
            string $successUrl,
            string $cancelUrl,
        ) use ($team, $user, $checkoutUrl): array {
            expect($checkoutTeam->is($team))->toBeTrue()
                ->and($payer->is($user))->toBeTrue()
                ->and($successUrl)->toContain('{CHECKOUT_SESSION_ID}')
                ->and($cancelUrl)->toBe(route('register.payment', ['checkout' => 'cancelled']));

            return ['id' => 'cs_test_checkout', 'url' => $checkoutUrl];
        });
    $this->instance(StripeCheckoutService::class, $stripeCheckout);

    $this->actingAs($user)
        ->withHeader('X-Inertia', 'true')
        ->post(route('register.payment.store'))
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', $checkoutUrl);

    expect(User::count())->toBe($userCount)
        ->and(Team::count())->toBe($teamCount)
        ->and($team->fresh()->payment_status)->toBe('pending');
});

test('tenant subdomains of unpaid clubs redirect to the payment step', function () {
    config(['app.url' => 'https://lazytown.test']);
    [, $tenant, $team] = pendingClubRegistration();

    $this->get('https://pending-club.lazytown.test/register')
        ->assertRedirect('https://lazytown.test/register/payment');

    $team->update(['payment_status' => 'paid']);

    $this->get('https://pending-club.lazytown.test/register')->assertOk();
});

test('the home page sends users with an unpaid club to the payment step', function () {
    [$user] = pendingClubRegistration();

    $this->actingAs($user)->get(route('home'))->assertRedirect(route('register.payment'));
});

test('club onboarding creates a team without payment', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $response = $this->actingAs($user)->post(route('club.onboarding.store'), ['name' => 'New Club']);
    $team = Team::where('name', 'New Club')->firstOrFail();

    $response->assertRedirect(route('teams.edit', $team));
    expect($team->payment_status)->toBe('not_required')
        ->and($team->members()->whereKey($user->id)->exists())->toBeTrue();
});

test('team owners are activated after stripe confirms the checkout session', function () {
    $user = User::factory()->create(['roles' => ['spieler'], 'active_role' => 'spieler']);
    $team = Team::factory()->create(['payment_reference' => 'cs_test_checkout']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $session = Session::constructFrom([
        'id' => 'cs_test_checkout',
        'object' => 'checkout.session',
        'mode' => 'payment',
        'status' => 'complete',
        'payment_status' => 'paid',
        'amount_total' => 2999,
        'currency' => 'eur',
        'client_reference_id' => (string) $team->id,
        'metadata' => [
            'team_id' => (string) $team->id,
            'payer_user_id' => (string) $user->id,
        ],
    ]);
    $event = Event::constructFrom([
        'id' => 'evt_checkout_paid',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_checkout',
                'object' => 'checkout.session',
            ],
        ],
    ]);

    $stripeCheckout = Mockery::mock(StripeCheckoutService::class);
    $stripeCheckout->shouldReceive('constructWebhookEvent')->once()->andReturn($event);
    $stripeCheckout->shouldReceive('retrieveSession')->once()->with('cs_test_checkout')->andReturn($session);
    $stripeCheckout->shouldReceive('belongsToTeamAndPayer')->once()->andReturnTrue();
    $stripeCheckout->shouldReceive('isPaidForTeamAndPayer')->once()->andReturnTrue();
    $this->instance(StripeCheckoutService::class, $stripeCheckout);

    $this->post(route('stripe.webhook'), [], ['Stripe-Signature' => 'test-signature'])
        ->assertNoContent();

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'payment_status' => 'paid',
        'payment_reference' => 'cs_test_checkout',
    ]);
    expect($team->fresh()->payment_paid_at)->not->toBeNull()
        ->and($team->members()->whereKey($user->id)->exists())->toBeTrue()
        ->and($user->fresh()->current_team_id)->toBe($team->id);
});

test('the success return confirms payment with stripe before activating the team', function () {
    $user = User::factory()->create(['roles' => ['spieler'], 'active_role' => 'spieler']);
    $team = Team::factory()->create(['payment_reference' => 'cs_test_checkout']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $session = Session::constructFrom([
        'id' => 'cs_test_checkout',
        'object' => 'checkout.session',
        'mode' => 'payment',
        'status' => 'complete',
        'payment_status' => 'paid',
        'amount_total' => 2999,
        'currency' => 'eur',
        'client_reference_id' => (string) $team->id,
        'metadata' => [
            'team_id' => (string) $team->id,
            'payer_user_id' => (string) $user->id,
        ],
    ]);

    $stripeCheckout = Mockery::mock(StripeCheckoutService::class);
    $stripeCheckout->shouldReceive('retrieveSession')->once()->with('cs_test_checkout')->andReturn($session);
    $stripeCheckout->shouldReceive('belongsToTeamAndPayer')->once()->andReturnTrue();
    $stripeCheckout->shouldReceive('isPaidForTeamAndPayer')->once()->andReturnTrue();
    $this->instance(StripeCheckoutService::class, $stripeCheckout);

    $this->actingAs($user)
        ->get(route('teams.payment.complete', ['team' => $team, 'session_id' => 'cs_test_checkout']))
        ->assertRedirect(route('teams.edit', $team));

    $this->assertDatabaseHas('teams', ['id' => $team->id, 'payment_status' => 'paid']);
    expect($team->fresh()->payment_paid_at)->not->toBeNull()
        ->and($user->fresh()->current_team_id)->toBe($team->id);
});

test('unpaid stripe sessions do not activate a team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['payment_reference' => 'cs_test_checkout']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $session = Session::constructFrom([
        'id' => 'cs_test_checkout',
        'object' => 'checkout.session',
        'mode' => 'payment',
        'status' => 'complete',
        'payment_status' => 'unpaid',
        'amount_total' => 2999,
        'currency' => 'eur',
        'client_reference_id' => (string) $team->id,
        'metadata' => [
            'team_id' => (string) $team->id,
            'payer_user_id' => (string) $user->id,
        ],
    ]);

    $stripeCheckout = Mockery::mock(StripeCheckoutService::class);
    $stripeCheckout->shouldReceive('retrieveSession')->once()->with('cs_test_checkout')->andReturn($session);
    $stripeCheckout->shouldReceive('belongsToTeamAndPayer')->once()->andReturnTrue();
    $stripeCheckout->shouldReceive('isPaidForTeamAndPayer')->once()->andReturnFalse();
    $this->instance(StripeCheckoutService::class, $stripeCheckout);

    $this->actingAs($user)
        ->get(route('teams.payment.complete', ['team' => $team, 'session_id' => 'cs_test_checkout']))
        ->assertRedirect(route('register.payment', ['checkout' => 'pending']));

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'payment_status' => 'pending',
        'payment_paid_at' => null,
    ]);
    expect($team->members()->whereKey($user->id)->exists())->toBeTrue();
});

test('stripe webhook rejects invalid signatures', function () {
    config([
        'services.stripe.secret' => 'sk_test_example',
        'services.stripe.webhook_secret' => 'whsec_example',
    ]);

    $this->postJson(route('stripe.webhook'), ['invalid' => 'payload'], [
        'Stripe-Signature' => 't=1,v1=invalid',
    ])->assertBadRequest();
});
