# 🛠 ShopHub — Backend API

This is the Laravel REST API powering [ShopHub](../README.md) — it serves JSON to the Vue storefront and admin panel and isn't meant to be browsed directly (visiting `/` shows a small status page confirming the API is running).

---

## 🚀 Tech Stack

- **Laravel 12** (PHP 8.2)
- **MySQL**
- **Laravel Sanctum** — token-based authentication (no session/cookie coupling)
- **Laravel Socialite** — Google & Facebook social login (stateless OAuth flow)
- **Eloquent ORM**
- **Queued Mail** — order confirmation & status-update emails

---

## 🔐 Authentication

Customers and admins share one `users` table (an `is_admin` flag separates them). Login and registration issue a Sanctum personal access token, sent as a `Bearer` header on every authenticated request. Admin-only routes are additionally gated by an `EnsureUserIsAdmin` middleware.

- Auth endpoints (`login`, `register`, `forgot-password`, `reset-password`) are throttled (5/min)
- Password reset uses hashed, single-use, expiring tokens; a successful reset revokes all of the user's tokens
- Changing the password revokes every token except the current session's
- `is_admin` is **not** mass-assignable — it's set explicitly in the few places allowed to grant it
- **Social login** (Google/Facebook via Socialite, stateless): the callback finds-or-creates the user — matching a provider account already linked in `social_accounts`, else linking by verified email, else creating a passwordless user — then issues the same Sanctum token and redirects to the SPA's `/auth/callback`. Social-only (null-password) users are rejected by the password login; providers returning no email (possible on Facebook) are rejected instead of creating an email-less account. Setup: [`docs/SOCIAL_LOGIN_SETUP.md`](../docs/SOCIAL_LOGIN_SETUP.md)

---

## 🌐 API Overview

### Public

```
GET  /api/categories               # active categories, with product counts
GET  /api/categories/{slug}
GET  /api/products                 # ?search=&category=&sort=&featured=&flash_sale=&page=
GET  /api/products/{slug}
```

### Order tracking (public, for guests & legacy orders)

```
POST /api/orders/track             # lookup by order_number + email — returns status + items only, no personal details
```

### Auth (throttled)

```
POST /api/register
POST /api/login
POST /api/forgot-password          # emails a reset link; response never reveals whether the email exists
POST /api/reset-password

GET  /api/auth/{provider}/redirect # social login: 302 to Google/Facebook (404 if unconfigured)
GET  /api/auth/{provider}/callback # OAuth return: find-or-create user, redirect to SPA with token
```

### Customer account (`auth:sanctum`)

```
POST  /api/logout
GET   /api/me
POST  /api/orders                  # checkout (stock-locked, server-side totals, sends confirmation email)
GET   /api/my/orders               # paginated order history
PATCH /api/profile                 # name, email, phone, default shipping address
PATCH /api/profile/password        # requires current password
```

### Admin (`auth:sanctum` + `admin` middleware)

```
GET    /api/admin/dashboard/stats
POST   /api/admin/uploads          # product/category image upload

/api/admin/categories              # full CRUD
/api/admin/products                # full CRUD
/api/admin/users                   # manage admin accounts

GET    /api/admin/orders
GET    /api/admin/orders/{order}
PATCH  /api/admin/orders/{order}/status   # also sends status-update email
```

---

## 🗄 Data Model

```
User          — customers & admins (is_admin flag); phone + default shipping address;
                password is nullable (social-only accounts)
SocialAccount — links a User to a Google/Facebook identity (provider + provider_id)
Category      — name, slug, icon, color_class (brand gradient), product count
Product       — belongs to Category; price, original_price, stock, flash-sale/featured flags
Order         — belongs to User (nullable — legacy guest orders); order_number, status, payment, totals
OrderItem     — snapshot of product name/price at time of order
```

---

## 📧 Email

Order confirmation, status-update, and password-reset emails are sent via queued Laravel Mailables (`app/Mail/OrderConfirmationMail.php`, `OrderStatusUpdatedMail.php`, `PasswordResetMail.php`) rendered from Markdown Blade templates.

By default `MAIL_MAILER=log` — emails are written to `storage/logs/laravel.log` instead of actually sending. To send real email (e.g. via Gmail SMTP), set in `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-address@gmail.com
MAIL_FROM_NAME="ShopHub"
```

`QUEUE_CONNECTION=database` is already set, and `composer run dev` starts a queue worker alongside the server so queued mail actually gets processed locally.

---

## ⚙️ Setup (local)

```bash
composer install
cp .env.example .env
php artisan key:generate

# set DB_* and FRONTEND_URL in .env, then:
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

`php artisan migrate --seed` creates:
- One admin user (`admin@shophub.test` / `password`)
- A demo customer (`customer@shophub.test` / `password`) — **only when `DEMO_MODE=true`**
- 20 demo categories
- ~15 demo products (some featured, some flash-sale)

Run the server, queue worker, and log tailer together via:

```bash
composer run dev
```

(This also starts Laravel's own unused default Vite pipeline — it does **not** start the actual Vue SPA. Run that separately from `front-end/`.)

---

## 🐳 Docker

From the repo root:

```bash
cp .env.example .env    # root .env — MySQL + demo-admin credentials, not committed
docker compose up -d --build backend mysql
```

What the backend image does on every container start (`docker-entrypoint.sh`):

1. Copies `.env.example` → `.env` if no `.env` is present
2. Generates an `APP_KEY`
3. Waits for MySQL to accept connections, then runs `php artisan migrate --force`
4. Runs `php artisan db:seed --force` (idempotent — safe to run every start)
5. Runs `php artisan storage:link`
6. Starts PHP's built-in server directly (**not** `php artisan serve` — see note below) on `0.0.0.0:8000`

Configuration (DB host/credentials, `FRONTEND_URL`, etc.) is passed via the `environment:` block in the root `docker-compose.yml`, not baked into the image. MySQL/demo credentials specifically come from the root `.env` (via Compose's automatic variable substitution) rather than being hardcoded in `docker-compose.yml`, so nothing sensitive sits in a tracked file.

> **Why not `php artisan serve`?** Laravel's `ServeCommand` deliberately strips most non-allowlisted environment variables from the dev-server process it spawns, so it can reliably detect and reload on `.env` file changes. That means container environment variables like `DB_HOST` get silently ignored in favor of whatever's in the `.env` file. The Dockerfile instead runs PHP's built-in server directly against Laravel's own routing script, which respects the real environment.

Rebuild after changing PHP dependencies or the Dockerfile: `docker compose build backend`.

---

## 🎭 Demo Mode

Set these in `.env` (**off by default** — everywhere, including `docker-compose.yml`) to add one-click "Try Demo Admin Login" / "Try Demo Customer Login" buttons to the frontend's `/admin/login` and `/login` pages — handy for letting portfolio visitors explore both sides of the app without needing credentials:

```
DEMO_MODE=true
DEMO_ADMIN_EMAIL=admin@shophub.test
DEMO_ADMIN_PASSWORD=password
DEMO_CUSTOMER_EMAIL=customer@shophub.test
DEMO_CUSTOMER_PASSWORD=password
```

`GET /api/config` is a public endpoint that exposes these values to the frontend **only when `DEMO_MODE=true`** — with it off, the endpoint just returns `{"demo_mode": false}` and reveals nothing. `AdminUserSeeder` and `DemoCustomerSeeder` read the same env vars, so the seeded demo accounts always match whatever credentials are configured (the demo customer is only seeded while demo mode is on).

While demo mode is on, the demo accounts are **protected from tampering** so the shared credentials keep working for every visitor: they can't be edited or deleted from the admin panel, their profile and password can't be changed, password resets for them are silently ignored, and checkout always uses the seeded demo identity regardless of what's typed. All of this is enforced server-side (`User::isProtectedDemoAccount()`), with the corresponding UI controls disabled. With demo mode off, the same accounts behave like any other.

> ⚠️ Demo mode publishes working credentials on a public endpoint by design. Never enable it on a deployment whose admin account can reach real data.

---

## 🔗 Social Login (Google & Facebook)

Optional — with no credentials configured, `/api/config` reports `social_providers: []` and the SPA hides the buttons entirely. To enable a provider, create OAuth credentials and set them in `.env`:

```
FRONTEND_URL=http://localhost:5173

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/api/auth/google/callback

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=http://localhost:8000/api/auth/facebook/callback
```

In short: **Google** — Cloud Console → OAuth consent screen (External, add yourself as a Test user) → Credentials → OAuth client ID (Web application) → register the callback URL above. **Facebook** — Meta for Developers → Create App with the (consumer) Facebook Login use case → Facebook Login Settings → register the callback URL → add the `email` permission to the use case → copy App ID/Secret (dev mode only allows accounts with a Tester role). Facebook's redirect URI must use `localhost` — it doesn't exempt `127.0.0.1` from its HTTPS requirement.

Full click-by-click walkthrough, linking behavior, and known limitations: [`docs/SOCIAL_LOGIN_SETUP.md`](../docs/SOCIAL_LOGIN_SETUP.md).

> 🗓 The console steps are documented **as of July 2026**. Google and Meta rework their dashboards often — if a menu doesn't match, the concepts (client ID/secret, exact-match redirect URIs) still apply; check the provider's current docs.

---

## 🧪 Tests

```bash
php artisan test
```

85+ feature and unit tests covering:
- Auth (register/login/logout/me, admin-vs-customer access to admin routes)
- Social login (Socialite mocked — new-user creation, email linking, repeat logins, no-email and provider-failure errors, null-password login rejection)
- Customer accounts (profile updates, password change, password reset flow, order history isolation)
- Public catalog (category/product filtering, search, sort, active-only visibility)
- Admin CRUD (categories, products, users) including validation and authorization
- Dashboard stats aggregation
- Checkout (including stock-locking against overselling) and order tracking (including its trimmed, PII-free response)
- Model behavior (`Order` number generation/uniqueness, `Product` scopes)

Tests run against an in-memory SQLite database (configured in `phpunit.xml`), so no database setup is needed to run them — including in CI.

---

## 📄 License

Part of the [ShopHub](../README.md) portfolio project.
