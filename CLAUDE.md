# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

ShopHub is an early-stage, **under active development** e-commerce app split into two fully decoupled projects in this repo:

- `back-end/` — Laravel 12 REST API (PHP 8.2, MySQL, Sanctum)
- `front-end/` — Vue 3 + TypeScript SPA (Vite, Pinia, Vue Router, Tailwind, Axios, Chart.js)

The frontend talks to the backend purely over HTTP/JSON; there is no server-rendered coupling between them.

## Commands

### Backend (`back-end/`)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

- Run everything (server + queue listener + logs + Vite) concurrently: `composer run dev`
- Serve API only: `php artisan serve`
- Run tests: `composer run test` (or `php artisan test`)
- Run a single test: `php artisan test --filter=TestName` or `php artisan test tests/Feature/ExampleTest.php`
- Lint/format PHP: `vendor/bin/pint`

### Frontend (`front-end/`)

```bash
npm install
```

- Dev server: `npm run dev`
- Production build (type-checks via `vue-tsc -b` then builds): `npm run build`
- Preview a production build: `npm run preview`
- Run tests (Vitest, specs in `src/**/*.spec.ts`): `npm test` (also `npm run test:watch`, `npm run test:coverage`)
- No lint script is configured yet.

## Architecture

### Backend

- Route registration is centralized in `bootstrap/app.php` (Laravel 12's new bootstrap style — there is no `Kernel.php`). API routes live in `routes/api.php`, web routes in `routes/web.php`.
- All API controllers live under `app/Http/Controllers/Api/` (e.g. `AuthController`). Non-API controllers stay directly under `app/Http/Controllers/`.
- CORS is configured in `config/cors.php` and allows only `FRONTEND_URL` (default `http://localhost:5173`) with `supports_credentials: true`, required for Sanctum's SPA cookie flow.
- **Auth is currently in a mixed/inconsistent state**: `AuthController::register` issues a Sanctum bearer token (`createToken(...)->plainTextToken`), but `AuthController::login` uses session-based `Auth::attempt()` + `$request->session()->regenerate()` and returns no token. The frontend's Axios client (`front-end/src/services/api.ts`) always attaches a bearer token from `localStorage` _and_ the auth store hits `/sanctum/csrf-cookie` first, i.e. the code is straddling both Sanctum SPA (cookie/CSRF) and token-based auth. When touching auth, pick one strategy consistently rather than assuming the existing code is the intended pattern.
- `bootstrap/app.php` force-appends `HandleCors` globally and prepends Sanctum's `EnsureFrontendRequestsAreStateful` to the `api` middleware group — needed for the SPA cookie flow to work at all.

### Frontend

- Routing (`src/router/index.ts`) is nested under three layouts: `DefaultLayout` (public site), `AuthenticationLayout` (`/admin/login`), and `AdminLayout` (`/admin`, `/admin/products`, `/admin/orders`, `/admin/categories`, `/admin/users`). There is currently no route guard enforcing auth on the admin routes.
- State is managed with Pinia stores under `src/stores/`: `auth.ts` (login/logout, current user) and `cart.ts` (cart items, quantities, totals). `auth.fetchUser()` and the `App.vue` mount hook that calls it are currently commented out — the user's identity is not actually being rehydrated on load.
- All HTTP calls go through the shared Axios instance in `src/services/api.ts`, configured with `withCredentials: true` and `baseURL` from `VITE_API_BASE_URL` (see `front-end/.env`).
- Components are split into `components/common/` (cross-page: header, footer, cart modal, product/order cards, etc.) and `components/home/` (home-page-specific sections). Admin pages live under `views/Admin/`, public pages directly under `views/`.
- Tailwind (v3 config + `@tailwindcss/vite` v4 plugin both present) is used for styling.

## Commit conventions

Use conventional commit prefixes (`feat:`, `fix:`, `refactor:`, `chore:`), keep the message short but descriptive, and never include Claude attribution (no name, no `Co-Authored-By` trailer).
