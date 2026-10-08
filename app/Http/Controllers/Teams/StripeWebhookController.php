<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\HandleStripeCheckoutWebhook;
use App\Http\Controllers\Controller;
use App\Services\Teams\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripeCheckoutService $stripeCheckout,
        HandleStripeCheckoutWebhook $handleWebhook,
    ): Response {
        $signature = $request->header('Stripe-Signature');

        if (! is_string($signature) || $signature === '') {
            return response()->json(['message' => 'Missing Stripe signature.'], 400);
        }

        try {
            $event = $stripeCheckout->constructWebhookEvent($request->getContent(), $signature);
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            Log::warning('Rejected an invalid Stripe webhook.', [
                'exception' => $exception::class,
            ]);

            return response()->json(['message' => 'Invalid Stripe webhook.'], 400);
        }

        $handleWebhook->handle($event);

        return response()->noContent();
    }
}
