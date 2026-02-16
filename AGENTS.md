# Repository Development Rules

## Shared Laravel domain artifacts

- `packages/php/core-models` is the single source of truth for shared Laravel domain artifacts.
- Shared Eloquent models must live in `packages/php/core-models/src/Models`.
- Shared model factories must live in `packages/php/core-models/src/Database/Factories`.
- Shared model migrations must live in `packages/php/core-models/database/migrations`.

## Backend usage rules

- `platform/backend` must consume shared models from `interadigital/core-models`.
- Do not create or reintroduce shared model classes in `platform/backend/app/Models`.
- Do not create duplicate shared model factories or shared model migrations in `platform/backend/database`.
- When changing shared model schema/behavior, update the package first, then refresh backend lockfile with:
  - `composer update interadigital/core-models`
