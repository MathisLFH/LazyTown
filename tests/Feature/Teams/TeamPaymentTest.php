<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\StripeCheckoutService;
use Stripe\Checkout\Session;
use Stripe\Event;

test('trainers can open club onboarding before payment', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $this->actingAs($user)
        ->get(route('club.onboarding'))
        ->assertOk();
});

test('team owners can start stripe checkout without marking the team paid', function () {
    $user = User::factory()->create(['roles' => ['spieler'], 'active_role' => 'spieler']);
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $sessionId = 'cs_test_checkout';
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/test';

    $stripeCheckout = Mockery::mock(StripeCheckoutService::class);
    $stripeCheckout->shouldReceive('createOrReuseSession')
        ->once()
        ->andReturnUsing(function (
            Team $checkoutTeam,
            User $payer,
            string $successUrl,
            string $cancelUrl,
        ) use ($team, $user, $sessionId, $checkoutUrl): array {
            expect($checkoutTeam->is($team))->toBeTrue()
                ->and($payer->is($user))->toBeTrue()
                ->and($successUrl)->toContain('{CHECKOUT_SESSION_ID}')
                ->and($cancelUrl)->toContain('checkout=cancelled');

            $checkoutTeam->update(['payment_reference' => $sessionId]);

            return ['id' => $sessionId, 'url' => $checkoutUrl];
        });
    $this->instance(StripeCheckoutService::class, $stripeCheckout);

    $response = $this->actingAs($user)
        ->withHeader('X-Inertia', 'true')
        ->post(route('teams.payment.update', $team));

    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', $checkoutUrl);
    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'payment_status' => 'pending',
        'payment_reference' => $sessionId,
        'payment_paid_at' => null,
    ]);
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
        ->assertRedirect(route('teams.payment.edit', ['team' => $team->slug, 'checkout' => 'pending']));

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

test('club onboarding creates an unowned pending team until payment', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $response = $this->actingAs($user)->post(route('club.onboarding.store'), ['name' => 'New Club']);
    $team = Team::where('name', 'New Club')->firstOrFail();

    $response->assertRedirect(route('teams.payment.edit', $team));
    expect($team->members()->count())->toBe(0);

    $this->actingAs($user)->post(route('teams.payment.skip', $team));

    expect($user->refresh()->roles)->toContain('trainer')
        ->and($team->fresh()->members()->whereKey($user->id)->exists())->toBeTrue();
});

test('team owners can skip payment temporarily', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user)
        ->post(route('teams.payment.skip', $team))
        ->assertRedirect(route('teams.edit', $team));

    $this->assertDatabaseHas('teams', ['id' => $team->id, 'payment_status' => 'skipped']);
});

test('team members cannot process payment', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->post(route('teams.payment.skip', $team))
        ->assertForbidden();
});

test('payment can only be skipped outside production', function () {
    $this->app['env'] = 'production';

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $csrfToken = 'production-test-token';

    $this->actingAs($user)
        ->withSession(['_token' => $csrfToken])
        ->post(route('teams.payment.skip', $team), ['_token' => $csrfToken])
        ->assertNotFound();

    $this->assertDatabaseHas('teams', ['id' => $team->id, 'payment_status' => 'pending']);
});
