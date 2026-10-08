<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use App\Services\Teams\StripeCheckoutService;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\StripeObject;

class HandleStripeCheckoutWebhook
{
    public function __construct(
        private StripeCheckoutService $stripeCheckout,
        private ConfirmTeamPayment $confirmTeamPayment,
    ) {}

    public function handle(Event $event): void
    {
        if (! in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'checkout.session.expired',
        ], true)) {
            return;
        }

        $eventObject = $event->data->object;

        if (! $eventObject instanceof StripeObject || ! is_string($eventObject->id)) {
            Log::warning('Stripe checkout webhook did not contain a session ID.', [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);

            return;
        }

        $session = $this->stripeCheckout->retrieveSession($eventObject->id);
        $metadata = $session->metadata?->toArray() ?? [];
        $teamId = $metadata['team_id'] ?? null;
        $payerId = $metadata['payer_user_id'] ?? null;

        if (! is_string($teamId) || ! ctype_digit($teamId) || ! is_string($payerId) || ! ctype_digit($payerId)) {
            Log::warning('Stripe checkout session is missing valid team or payer metadata.', [
                'event_id' => $event->id,
                'stripe_session_id' => $session->id,
            ]);

            return;
        }

        $team = Team::query()->find($teamId);
        $payer = User::query()->find($payerId);

        if (! $team instanceof Team || ! $payer instanceof User) {
            Log::warning('Stripe checkout session references a missing team or payer.', [
                'event_id' => $event->id,
                'stripe_session_id' => $session->id,
                'team_id' => $teamId,
                'payer_user_id' => $payerId,
            ]);

            return;
        }

        if (! $this->stripeCheckout->belongsToTeamAndPayer($session, $team, $payer)) {
            Log::warning('Stripe checkout session metadata does not match its team or payer.', [
                'event_id' => $event->id,
                'stripe_session_id' => $session->id,
                'team_id' => $team->id,
            ]);

            return;
        }

        if ($team->payment_reference !== $session->id && $team->payment_status !== 'skipped') {
            Log::warning('Ignoring a stale Stripe checkout session.', [
                'event_id' => $event->id,
                'stripe_session_id' => $session->id,
                'team_id' => $team->id,
            ]);

            return;
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            if ($this->stripeCheckout->isPaidForTeamAndPayer($session, $team, $payer)) {
                $this->confirmTeamPayment->handle($team, $payer, $session->id);
            }

            return;
        }

        Team::query()
            ->whereKey($team->id)
            ->where('payment_status', 'pending')
            ->where('payment_reference', $session->id)
            ->update(['payment_reference' => null]);
    }
}
