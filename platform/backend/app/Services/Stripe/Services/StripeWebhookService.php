<?php

declare(strict_types=1);

namespace App\Services\Stripe\Services;

use Illuminate\Support\Facades\Log;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;

class StripeWebhookService
{
    /**
     * @param  array<string, mixed>  $eventPayload
     */
    public function handleEvent(array $eventPayload): void
    {
        $eventType = $eventPayload['type'] ?? null;

        if (! is_string($eventType) || $eventType === '') {
            return;
        }

        switch ($eventType) {
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($eventPayload);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($eventPayload);
                break;

            default:
                Log::info('Unhandled Stripe webhook event.', ['type' => $eventType]);
                break;
        }
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    private function handleInvoicePaymentSucceeded(array $eventPayload): void
    {
        $subscriptionId = $eventPayload['data']['object']['subscription'] ?? null;

        if (! is_string($subscriptionId) || $subscriptionId === '') {
            return;
        }

        $server = Server::where('stripe_tx_id', $subscriptionId)->first();

        if ($server === null) {
            return;
        }

        $server->fill([
            'stripe_tx_return' => true,
            'suspended' => false,
            'status' => ServerStatus::ACTIVE->value,
        ]);
        $server->save();
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    private function handleSubscriptionDeleted(array $eventPayload): void
    {
        $subscriptionId = $eventPayload['data']['object']['id'] ?? null;

        if (! is_string($subscriptionId) || $subscriptionId === '') {
            return;
        }

        $server = Server::where('stripe_tx_id', $subscriptionId)->first();

        if ($server === null) {
            return;
        }

        $server->fill([
            'suspended' => true,
            'status' => ServerStatus::SUSPENDED->value,
        ]);
        $server->save();
    }
}
