# Platform Frontend (React + Vite)

This app is the modern frontend replacement for the blade UI in `platform_legacy`.

## Current groundwork

- React 19 + TypeScript + Vite scaffold in `platform/frontend`.
- API client layer in `src/lib/platformApi.ts`.
- Starter screen that calls:
  - `GET /api/v1/health`
  - `GET /api/v1/migration/legacy-domains`
- Dev proxy for `/api/*` requests to the Laravel backend.

## Local setup

1. Copy environment file:
   - `cp .env.example .env`
2. Install dependencies:
   - `npm install`
3. Start dev server:
   - `npm run dev`

By default the Vite proxy forwards API requests to `http://localhost:8000`.

## Next migration slices

- Add auth flow and session handling.
- Replace legacy blade pages with React routes by domain (catalog, checkout, customer portal).
- Move server/provisioning actions onto typed backend endpoints.
