## Platform Groundwork

This directory contains the first-pass modernization scaffold for the legacy platform.

- `backend/`: Fresh Laravel 12 application (API/backend foundation).
- `frontend/`: Fresh React application powered by Vite.

No functionality, models, or UI were migrated from `platform_legacy` in this pass.

## Local development

### Backend (`platform/backend`)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend (`platform/frontend`)

```bash
npm install
npm run dev
```

## Docker development (from repo root)

```bash
docker compose up --build
```

Routes exposed by the root gateway:

- `http://store.localhost` -> React frontend dev server
- `http://api.localhost` -> Laravel backend/API server

Stop the stack:

```bash
docker compose down
```

If your machine does not resolve `*.localhost` automatically, add this hosts entry:

```text
127.0.0.1 store.localhost api.localhost
```

Project helper scripts are stored in the repo root `scripts/` directory.

## Next steps

- Define backend domain models and API contracts.
- Replace legacy Blade-driven flows with React routes/components.
- Wire frontend API client to backend endpoints.
