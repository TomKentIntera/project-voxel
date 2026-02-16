# Cursor AI Guidelines (Frontend)

This document defines how AI tools (like Cursor) should work in this **frontend-only** project.

The application uses **React**, **TypeScript**, and **Vite**.

---

## Core Principles

- Always use **TypeScript** (`.ts`, `.tsx`) — never plain JavaScript files.  
- Prefer **reusing existing components, hooks, and utilities** over creating new ones.  
- Keep code **modular**, **typed**, and **tested**.  
- Follow all existing **ESLint** and **Prettier** rules.  
- Every new feature or fix must include tests.  
- Make changes **incrementally and safely**.

---

## Technology Standards

- **Language:** TypeScript  
- **Framework:** React (function components with hooks)  
- **Build Tool:** Vite  
- **Routing:** React Router  
- **Client State:** Zustand  
- **Async Data:** React Query (TanStack Query)  
- **Linting:** ESLint  
- **Testing:** Vitest with React Testing Library  

---

## Project Structure

Follow the current repository layout. A typical structure is:

- `src/components` – Shared UI components  
- `src/features` – Feature-specific modules  
- `src/hooks` – Reusable React hooks  
- `src/stores` – Zustand stores for shared state  
- `src/types` – Shared type definitions  
- `src/utils` or `src/lib` – Generic helper functions  
- `src/router` – React Router configuration  
- `src/tests` – Test utilities and fixtures  
- `src/pages` – Page components organized by domain

Keep modules small and cohesive. Avoid deep nesting unless conceptually justified.

### Pages Organization

Pages are organized into folders by their domain and purpose:

- `src/pages/landing/` – InteraHR landing page and its portal (base domain)
  - Marketing landing page
  - Tenant admin portal (on base domain)
  - 404/not found pages

- `src/pages/tenant/` – Tenant job board pages and applicant portals (tenant subdomains)
  - Public job site for tenants
  - Applicant portal for candidates

- `src/pages/tenant_admin/` – Administration for tenants (tenant subdomains)
  - HR admin area for managing jobs, analytics, hiring workflows
  - Tenant-specific administration features

- `src/pages/admin/` – Admin pages for InteraHR internal admins (platform management)
  - Platform administration
  - System configuration
  - Tenant management from platform perspective

---

## Components and UI

- Use **function components** with React hooks.  
- Each component should have a **single clear responsibility**.  
- Extract repeated logic into **custom hooks** instead of duplicating code.  
- ALWAYS prefer **existing components** over introducing new ones.  
- If a new component is required, it MUST be **brandable** and MUST be
  **added to Storybook**.  
- If unsure, **ask for confirmation** before creating a new component.  
- Where possible, use **components over raw HTML**.  
- Follow the **existing styling system** (Tailwind, CSS Modules, styled-components, etc.).  
- Do **not** introduce new styling libraries.  
- Every new component must include at least one test verifying rendering and interaction.
- Tenant-space components must be **brandable by default**: use `useBranding`,
  accept optional branding overrides, and rely on shared components like `Button`
  rather than `Branding*`-prefixed components.

Props must always be **explicitly typed**.  
Move complex logic out of JSX and into hooks or stores.

---

## State Management

Use a clear **three-layer model** for state:

### 1. Local UI State
- Use `useState` or `useReducer` for local state such as form inputs, modals, or toggles.  
- Keep local state as close as possible to where it’s used.

### 2. Shared Client State (Zustand)
- Use **Zustand** for application-wide or cross-component state:  
  - Authentication/session  
  - UI preferences (theme, layout)  
  - Feature-level filters or selections  
- Keep stores **small and domain-specific** (e.g. `useAuthStore`, `useThemeStore`).  
- Do not create a single, massive store.  
- All stores must be **fully typed** and include **unit tests** for actions and selectors.

### 3. Server Data (React Query)
- Use **React Query** for all asynchronous or remote data.  
- Create dedicated hooks per data resource (e.g. `useUserProfile`, `usePosts`).  
- Use React Query’s built-in **caching**, **invalidation**, and **refetch** mechanisms.  
- Do **not** duplicate React Query logic in Zustand or Context.

---

## Routing and Navigation

- All navigation must use **React Router**.  
- Define routes centrally (e.g. `src/router`) or per feature if that pattern exists.  
- Use:
  - `BrowserRouter`, `Routes`, and `Route` for route definitions  
  - `useNavigate`, `useParams`, and `useLocation` in components  
  - `Link` or `NavLink` for navigation elements  
- Prefer **lazy-loaded routes** (`React.lazy`, `Suspense`) for large pages.  
- Keep URLs meaningful and consistent.

---

## ESLint and Code Quality

- ESLint must run cleanly before committing.  
- Do not disable rules globally; fix issues properly.  
- Maintain consistent naming and formatting.  
- Avoid:
  - Unused imports or variables  
  - Implicit `any` types  
  - Overly large components (split logic into hooks when >300 lines)  
- If ESLint is missing, create a minimal config including:  
  - `@typescript-eslint`  
  - `eslint-plugin-react`  
  - `eslint-plugin-react-hooks`  
  - Prettier integration (if applicable)

---

## Testing

Testing is **mandatory** for all new or updated code.

- Use **Vitest** and **React Testing Library**.  
- Write tests for:
  - Component rendering and interaction  
  - Hook behaviour and state transitions  
  - Zustand store logic  
- Keep tests **deterministic**, **fast**, and **well-structured**.  
- Mock external dependencies where necessary.  
- Follow existing test file conventions.

---

## Documentation and Comments

- Keep comments short, clear, and only when behaviour is non-obvious.  
- Use **TSDoc-style comments** for exported hooks, stores, and components.  
- Avoid long descriptive comments; keep documentation close to the code it describes.

---

## Summary

Cursor should always:

1. Use **TypeScript** for every file.  
2. Use **React Router** for all navigation.  
3. Manage shared state with **Zustand**.  
4. Handle async data with **React Query**.  
5. Enforce **ESLint** and fix issues, not silence them.  
6. Include **tests** for all new functionality.  
7. Follow existing folder structure and naming conventions.  
8. Prioritise **consistency**, **modularity**, and **reuse** over novelty or complexity.