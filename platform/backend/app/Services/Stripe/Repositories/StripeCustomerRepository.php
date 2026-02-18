<?php

declare(strict_types=1);

namespace App\Services\Stripe\Repositories;

use App\Services\Stripe\Helpers\StripeClientFactory;
use InvalidArgumentException;
use Interadigital\CoreModels\Models\User;
use Stripe\Customer;

class StripeCustomerRepository
{
    public function __construct(
        private readonly StripeClientFactory $stripeClientFactory
    ) {
    }

    public function findByEmail(?string $email): ?Customer
    {
        if (! is_string($email) || $email === '') {
            return null;
        }

        $client = $this->stripeClientFactory->make();
        $customers = $client->customers->all([
            'email' => $email,
            'limit' => 1,
        ]);

        if (count($customers->data) === 0) {
            return null;
        }

        return $customers->data[0];
    }

    public function getOrCreate(User $user): Customer
    {
        if (! is_string($user->email) || $user->email === '') {
            throw new InvalidArgumentException('Cannot create Stripe customer without an email.');
        }

        $existing = $this->findByEmail($user->email);

        if ($existing !== null) {
            return $existing;
        }

        $client = $this->stripeClientFactory->make();

        return $client->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => (string) $user->getKey(),
            ],
        ]);
    }
}
