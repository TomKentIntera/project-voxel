# Platform modernization workspace

This directory contains the new platform foundation that will replace `platform_legacy`.

## Structure

- `backend/` - Laravel 12 API-first backend.
- `frontend/` - React + TypeScript + Vite frontend.

## Legacy reference

The legacy app currently mixes blade rendering and business logic in `platform_legacy`.

Primary route groups identified:

1. Marketing/catalog pages (`/`, `/plans`, `/faqs`, `/terms`, `/privacy-policy`, `/vaulthunters`)
2. Checkout/config/provisioning (`/plan/*`, `/server/initialise/*`)
3. Authenticated customer area (`/client`, `/client/billing`, `/client/referrals`)
4. Integrations/webhooks (`/api/versions/*`, `/api/server/isInitialised/*`, `/api/webhook/stripe`)

## Migration strategy

1. Keep legacy app as operational reference.
2. Implement equivalent JSON endpoints in `backend/api/v1/*` by domain.
3. Build React routes and typed clients in `frontend/` against those endpoints.
4. Migrate one vertical slice at a time and decommission corresponding blade flows.
