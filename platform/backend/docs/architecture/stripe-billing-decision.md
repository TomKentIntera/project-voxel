# Stripe Billing Implementation Decision

## Status

Accepted (2026-02-18)

## Context

We need Stripe billing support in the backend and now have a dedicated Stripe module under:

- `app/Services/Stripe/Services`
- `app/Services/Stripe/Repositories`
- `app/Services/Stripe/Helpers`

Two options were considered:

1. Use **Laravel Cashier**
2. Build a **custom Stripe SDK integration**

Current constraints:

- Shared domain models are owned by `packages/php/core-models`.
- Existing billing state is tied to shared server fields like `stripe_tx_id` and `stripe_tx_return`.
- JWT-first API flows and provisioning/suspension logic are already custom.
- Plan-to-Stripe mapping already exists in `config/plans.php` and is environment-specific.

## Decision

Use a **custom Stripe implementation** based on `stripe/stripe-php` for the current backend.

## Rationale

- Keeps Stripe-specific lifecycle control aligned with current server provisioning behavior.
- Avoids immediate schema/model coupling to Cashier traits and tables.
- Fits the shared-model architecture already in place.
- Lets us migrate incrementally and keep the Stripe boundary explicit in `app/Services/Stripe`.

## Consequences

- We own Stripe orchestration code (checkout sessions, webhook handling, customer lookup).
- We keep the option to move to Cashier later if domain models and schema are reshaped for it.
- Billing strategy is configurable via `STRIPE_BILLING_DRIVER`, defaulting to `custom`.
