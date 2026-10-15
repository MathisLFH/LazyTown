<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\User;
use LogicException;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutService
{
    private ?StripeClient $client = null;

    /**
     * @return array{id: string, url: string}|null
     */
    public function createOrReuseSession(
        Team $team,
        User $payer,
        string $successUrl,
        string $cancelUrl,
    ): ?array {
        $stripe = $this->client();
        $existingSessionId = $team->payment_reference;

        if (is_string($existingSessionId) && str_starts_with($existingSessionId, 'cs_test_')) {
            $existingSession = $stripe->checkout->sessions->retrieve($existingSessionId);

            if (! $this->belongsToTeamAndPayer($existingSession, $team, $payer)) {
                throw new LogicException('The saved Stripe checkout session does not match this payment.');
            }

            if ($existingSession->status === 'open') {
                return $this->sessionDetails($existingSession);
            }

            if ($existingSession->status === 'complete') {
                return null;
            }
        }

        $idempotencyKey = 'team-payment-'.$team->id.'-'.(
            is_string($existingSessionId) && $existingSessionId !== ''
                ? hash('sha256', $existingSessionId)
                : 'initial'
        );

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'LazyTown Jahreszugang',
                    ],
                    'unit_amount' => $this->annualAccessAmountInCents(),
                ],
                'quantity' => 1,
            ]],
            'client_reference_id' => (string) $team->id,
            'metadata' => [
                'team_id' => (string) $team->id,
                'payer_user_id' => (string) $payer->id,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'team_id' => (string) $team->id,
                    'payer_user_id' => (string) $payer->id,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);

        $details = $this->sessionDetails($session);

        $team->update([
            'payment_reference' => $details['id'],
        ]);

        return $details;
    }

    public function retrieveSession(string $sessionId): Session
    {
        if (! str_starts_with($sessionId, 'cs_test_')) {
            throw new LogicException('Only Stripe test checkout sessions are supported.');
        }

        return $this->client()->checkout->sessions->retrieve($sessionId);
    }

    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        $secret = config('services.stripe.webhook_secret');

        if (! is_string($secret) || ! str_starts_with($secret, 'whsec_')) {
            throw new LogicException('A Stripe test webhook signing secret is required.');
        }

        return Webhook::constructEvent($payload, $signature, $secret);
    }

    public function belongsToTeamAndPayer(Session $session, Team $team, User $payer): bool
    {
        $metadata = $session->metadata?->toArray() ?? [];

        return $session->mode === 'payment'
            && $session->client_reference_id === (string) $team->id
            && ($metadata['team_id'] ?? null) === (string) $team->id
            && ($metadata['payer_user_id'] ?? null) === (string) $payer->id;
    }

    public function isPaidForTeamAndPayer(Session $session, Team $team, User $payer): bool
    {
        return $this->belongsToTeamAndPayer($session, $team, $payer)
            && $session->status === 'complete'
            && $session->payment_status === 'paid'
            && $session->amount_total === $this->annualAccessAmountInCents()
            && $session->currency === 'eur';
    }

    public function annualAccessAmountInCents(): int
    {
        $amount = config('services.stripe.annual_access_amount_cents');

        if (! is_int($amount) || $amount < 1) {
            throw new LogicException('The Stripe annual access amount must be a positive integer in cents.');
        }

        return $amount;
    }

    public function annualAccessPriceLabel(): string
    {
        return number_format($this->annualAccessAmountInCents() / 100, 2, ',', '.').' €';
    }

    private function client(): StripeClient
    {
        if ($this->client instanceof StripeClient) {
            return $this->client;
        }

        $secret = config('services.stripe.secret');

        if (! is_string($secret) || ! str_starts_with($secret, 'sk_test_')) {
            throw new LogicException('A Stripe test API key is required; live keys are not supported.');
        }

        return $this->client = new StripeClient($secret);
    }

    /**
     * @return array{id: string, url: string}
     */
    private function sessionDetails(Session $session): array
    {
        if (! is_string($session->id) || ! is_string($session->url) || $session->url === '') {
            throw new LogicException('Stripe did not return a usable checkout session URL.');
        }

        return [
            'id' => $session->id,
            'url' => $session->url,
        ];
    }
}
