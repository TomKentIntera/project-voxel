## Platform Groundwork

This directory contains the first-pass modernization scaffold for the legacy platform.

- `backend/`: Fresh Laravel 12 application (API/backend foundation).
- `orchestrator/`: Fresh Laravel 12 application for orchestration workflows.
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

### Orchestrator (`platform/orchestrator`)

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
./scripts/platform-start.sh
```

`platform-start.sh` now reuses persisted LocalStack resources and skips Terraform event-bus
provisioning when resources already exist. Use `--force-provision` to run Terraform apply
again, or `--skip-provision` to bypass provisioning entirely.

Start with the testing-only Wings container enabled:

```bash
./scripts/platform-start.sh --with-wings
```

Routes exposed by the root Traefik router:

- `http://store.localhost` -> React frontend dev server
- `http://api.localhost` -> Laravel backend/API server
- `http://orchestrator.localhost` -> Laravel orchestrator service
- `http://panel.localhost` -> Pterodactyl Panel
- `http://storybook.localhost` -> Component storybook (dev only)

Database note: the Pterodactyl panel uses the shared `mysql` container with its own
`pterodactyl` schema and credentials (it does not run a separate MySQL container).

Additional testing-only service (not started unless `--with-wings` is passed):

- `http://127.0.0.1:8080` -> Pterodactyl Wings API
- `sftp://127.0.0.1:2022` -> Wings SFTP endpoint

Wings config lives at `docker/wings/config.yml`. Replace `uuid`, `token_id`, and `token`
with values from your Panel node configuration before running node lifecycle tests.

Stop the stack:

```bash
./scripts/platform-stop.sh
```

Reset the stack and rerun fresh migrations:

```bash
./scripts/platform-reset.sh
```

Optional reset flags:

```bash
./scripts/platform-reset.sh --rebuild   # rebuild images/recreate containers
./scripts/platform-reset.sh --seed      # run migrate:fresh with seeders
./scripts/platform-reset.sh --rebuild --seed
```

If your machine does not resolve `*.localhost` automatically, add this hosts entry:

```text
127.0.0.1 store.localhost api.localhost orchestrator.localhost panel.localhost storybook.localhost
```

## Next steps

- Define backend domain models and API contracts.
- Replace legacy Blade-driven flows with React routes/components.
- Wire frontend API client to backend endpoints.
