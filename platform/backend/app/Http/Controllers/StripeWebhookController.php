<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Stripe\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeWebhookService $stripeWebhookService): JsonResponse
    {
        $payload = (string) $request->getContent();
        $signatureHeader = (string) $request->header('Stripe-Signature', '');
        $webhookSecret = (string) config('services.stripe.webhook_secret', '');

        if ($webhookSecret === '') {
            return response()->json([
                'message' => 'Stripe webhook secret is not configured.',
            ], 503);
        }

        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            return response()->json([
                'message' => 'Invalid Stripe webhook payload.',
            ], 400);
        }

        $stripeWebhookService->handleEvent($event->toArray());

        return response()->json([
            'ok' => true,
        ]);
    }
}

