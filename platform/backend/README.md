# Platform Backend (Laravel 12)

This service is the modern API-first replacement for `platform_legacy`.

## Purpose

- Move business logic from blade/web routes into versioned JSON APIs.
- Provide a stable backend contract for the React frontend in `../frontend`.
- Migrate the legacy feature set in small vertical slices.

## Current groundwork

- Laravel 12 application scaffolded in `platform/backend`.
- Versioned API routing enabled:
  - `GET /api/v1/health`
  - `GET /api/v1/migration/legacy-domains`
- Initial legacy-domain inventory in `config/migration.php`.
- CORS baseline config in `config/cors.php` for local React development.

## Local setup

> PHP and Composer are required to run this service locally.

1. Copy environment values:
   - `cp .env.example .env`
2. Install dependencies:
   - `composer install`
3. Generate app key:
   - `php artisan key:generate`
4. Run server:
   - `php artisan serve --host=0.0.0.0 --port=8000`

## Migration approach

1. Add typed request/response contracts for one domain at a time.
2. Move legacy controller logic into service classes and API resources.
3. Keep endpoints versioned under `/api/v1/*`.
4. Remove blade dependencies once equivalent React routes are live.
