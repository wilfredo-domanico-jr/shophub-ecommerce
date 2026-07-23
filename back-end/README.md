# ShopHub — Backend API

This is the Laravel REST API powering [ShopHub](../README.md) — it serves JSON to the Vue storefront and admin panel and isn't meant to be browsed directly (visiting `/` shows a small status page confirming the API is running).

---

## Tech Stack

- **Laravel 12** (PHP 8.2)
- **MySQL**
- **Laravel Sanctum** — token-based authentication (no session/cookie coupling)
- **Laravel Socialite** — Google & Facebook social login (stateless OAuth flow)
- **Eloquent ORM**
- **Queued Mail** — order confirmation & status-update emails

---

## Authentication

Customers and admins share one `users` table (an `is_admin` flag separates them). Login and registration issue a Sanctum personal access token, sent as a `Bearer` header on every authenticated request. Admin-only routes are additionally gated by an `EnsureUserIsAdmin` middleware.

- Auth endpoints (`login`, `register`, `forgot-password`, `reset-password`) are throttled (5/min)
- Password reset uses hashed, single-use, expiring tokens; a successful reset revokes all of the user's tokens
- Changing the password revokes every token except the current session's
- `is_admin` is **not** mass-assignable — it's set explicitly in the few places allowed to grant it
- **Social login** (Google/Facebook via Socialite, stateless): the callback finds-or-creates the user — matching a provider account already linked in `social_accounts`, else linking by verified email, else creating a passwordless user — then issues the same Sanctum token and redirects to the SPA's `/auth/callback`. Social-only (null-password) users are rejected by the password login; providers returning no email (possible on Facebook) are rejected instead of creating an email-less account. Setup: [`docs/SOCIAL_LOGIN_SETUP.md`](../docs/SOCIAL_LOGIN_SETUP.md)

---

## API Overview

### Public

```
GET  /api/categories               # active categories, with product counts
GET  /api/categories/{slug}
GET  /api/products                 # ?search=&category=&sort=&featured=&flash_sale=&page= (rows include variants_count)
GET  /api/products/{slug}          # includes options + variants (per-variant stock, price/image overrides)
GET  /api/products/{slug}/reviews  # paginated visible reviews + rating breakdown for the distribution bars
GET  /api/careers                  # published job openings for the Careers page
GET  /api/vouchers                 # publicly listed, currently claimable vouchers (safe fields only)
GET  /api/flash-sale               # {sale: null | {title, starts_at, ends_at, is_live}} — current-or-next event
POST /api/newsletter/subscribe     # store email + queue a welcome mail (throttled)
POST /api/newsletter/unsubscribe   # token from the email's unsubscribe link (throttled)
```

### Order tracking (public, for guests & legacy orders)

```
POST /api/orders/track             # lookup by order_number + email — returns status + items only, no personal details
```

### Stripe webhook (public, signature-verified)

```
POST /api/webhooks/stripe          # checkout.session.completed marks the order paid (idempotent) and queues
                                   # the confirmation email; requests are verified against STRIPE_WEBHOOK_SECRET
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

GET    /api/cart                   # the account's cart; lines carry live product data (price/stock/image)
                                   # plus is_available — false when the product is deleted, deactivated,
                                   # or out of stock (deleted products fall back to a name snapshot)
POST   /api/cart/items             # add/upsert a line (same product+variant increments quantity);
                                   # variant_id required for products with options
PATCH  /api/cart/items/{id}        # set quantity (min 1)
DELETE /api/cart/items/{id}        # remove a line
DELETE /api/cart                   # clear the cart (used after checkout)
POST  /api/orders                  # checkout (stock-locked, server-side totals); items take an optional
                                   # variant_id — required for products with options; optional voucher_code
                                   # applies a discount (re-validated under lock). payment_method is
                                   # "Cash on Delivery" (default; confirmation email sent immediately) or
                                   # "Card" (order created unpaid; email sent by the Stripe webhook on payment)
POST  /api/orders/{id}/pay         # create a Stripe hosted-checkout session for an unpaid card order,
                                   # returns {url}; re-callable to retry an abandoned payment
GET   /api/my/orders/{orderNumber}/payment-status  # owner-scoped payment_status/paid_at (polled by the
                                   # SPA's /checkout/return page after the Stripe redirect)
POST  /api/vouchers/preview        # price a voucher against the cart before checkout (throttled, cosmetic —
                                   # never redeems; the order endpoint is the authority)
GET   /api/my/orders               # paginated order history (items link back to the product for reviews)
PATCH /api/profile                 # name, email, phone, default shipping address
PATCH /api/profile/password        # requires current password

POST   /api/products/{slug}/reviews # post a review (1–5 stars + optional comment + up to 4 photos, multipart;
                                    # gated to customers with a delivered order for the product; throttled)
PATCH  /api/reviews/{id}            # edit your own review (rating/comment/photos; photo edits arrive as a
                                    # method-spoofed multipart POST with photos[] to add + remove_photos[] to drop)
DELETE /api/reviews/{id}            # delete your own review (photo files removed too)
```

### Admin (`auth:sanctum` + `admin` middleware)

```
GET    /api/admin/dashboard/stats
POST   /api/admin/uploads          # product/category image upload

/api/admin/categories              # full CRUD
/api/admin/products                # full CRUD; store/update accept nested options + variants
                                   # (synced by id: kept ids update, missing delete, new create;
                                   #  product stock becomes the sum of variant stocks)
/api/admin/users                   # manage admin accounts
/api/admin/careers                 # manage job openings (incl. unpublished)
/api/admin/vouchers                # full CRUD for discount codes (percent/fixed, min spend,
                                   # validity window, usage + per-customer limits, active toggle)
/api/admin/flash-sales             # schedule flash sale events (title, start/end, active toggle)

GET    /api/admin/reviews          # all reviews incl. hidden; ?search= (comment/product/customer) &rating=
PATCH  /api/admin/reviews/{id}/visibility  # hide/unhide (hidden reviews drop out of the product rating)
DELETE /api/admin/reviews/{id}     # delete a review and its photo files

/api/admin/newsletters             # newsletter campaigns: drafts, edit, delete
POST   /api/admin/newsletters/{id}/send   # queue the campaign to all active subscribers
GET    /api/admin/newsletter-subscribers  # searchable subscriber list with status
DELETE /api/admin/newsletter-subscribers/{id}

GET    /api/admin/orders
GET    /api/admin/orders/{order}
PATCH  /api/admin/orders/{order}/status   # also sends status-update email
```

---

## Data Model

```
User          — customers & admins (is_admin flag); phone + default shipping address;
                password is nullable (social-only accounts)
SocialAccount — links a User to a Google/Facebook identity (provider + provider_id)
Category      — name, slug, icon, color_class (brand gradient), product count
Product       — belongs to Category; price, original_price, stock, flash-sale/featured flags;
                optional JSON options (e.g. Color/Size) when the product has variations;
                rating + reviews_count are derived from visible reviews (never set directly)
ProductVariant — one sellable combination (e.g. Red / M); per-variant stock, optional
                price/image overrides, unique per product via a deterministic variant_key
Order         — belongs to User (nullable — legacy guest orders); order_number, status, payment, totals;
                voucher_id + voucher_code snapshot + discount when a voucher was applied
OrderItem     — snapshot of product name/price (+ variant label, e.g. "Red / M") at time of order
Review        — 1–5 star rating + optional comment/photos per (user, product) — one each,
                verified-purchase gated at the API; is_hidden for admin moderation
Voucher       — discount code; percent (optional max cap) or fixed amount, min spend, validity
                window, usage/per-customer limits, used_count, is_active, is_public (storefront listing)
FlashSale     — scheduled homepage sale event: title, starts_at, ends_at, is_active;
                the storefront shows the earliest active event that hasn't ended
JobOpening    — careers-page posting; title, department, location, type, is_active
Newsletter    — campaign; subject, body, optional image, draft/sent + sent_at
NewsletterSubscriber — email, per-subscriber unsubscribe token, unsubscribed_at

```

---

## Email

Order confirmation, status-update, password-reset, newsletter-welcome, and newsletter campaign emails are sent via queued Laravel Mailables (`app/Mail/`) rendered from Markdown Blade templates. Newsletter emails include a per-subscriber unsubscribe link; unsubscribed addresses are never mailed again.

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

## Setup (local)

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
- Demo reviews from a handful of seeded reviewer accounts (product ratings derive from them;
  in demo mode the demo customer also gets a delivered order so visitors can post a review)
- 3 sample job openings for the Careers page

Run the server, queue worker, and log tailer together via:

```bash
composer run dev
```

(This also starts Laravel's own unused default Vite pipeline — it does **not** start the actual Vue SPA. Run that separately from `front-end/`.)

---

## Docker

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

## Demo Mode

Set these in `.env` (**off by default** — everywhere, including `docker-compose.yml`) to add one-click "Try Demo Admin Login" / "Try Demo Customer Login" buttons to the frontend's `/admin/login` and `/login` pages — handy for letting portfolio visitors explore both sides of the app without needing credentials:

```
DEMO_MODE=true
DEMO_SANDBOX_CONFIRMED=true
DEMO_ADMIN_EMAIL=admin@shophub.test
DEMO_ADMIN_PASSWORD=password
DEMO_CUSTOMER_EMAIL=customer@shophub.test
DEMO_CUSTOMER_PASSWORD=password
```

`GET /api/config` is a public endpoint that exposes these values to the frontend only when **both** `DEMO_MODE=true` **and** `DEMO_SANDBOX_CONFIRMED=true` — with either off, the endpoint just returns `{"demo_mode": false}` and reveals nothing. The two flags are deliberately separate: an env file copied from a demo box into a real deployment (bringing `DEMO_MODE=true` along with it) still can't publish the admin password unless someone also, separately, sets `DEMO_SANDBOX_CONFIRMED=true`. `AdminUserSeeder` and `DemoCustomerSeeder` read `DEMO_MODE` alone (not the confirmation flag) — seeding the accounts isn't the security risk, broadcasting their passwords is — so the seeded demo accounts always match whatever credentials are configured (the demo customer is only seeded while `DEMO_MODE` is on).

While `DEMO_MODE` is on, the demo accounts are **protected from tampering** so the shared credentials keep working for every visitor: they can't be edited or deleted from the admin panel, their profile and password can't be changed, password resets for them are silently ignored, and checkout always uses the seeded demo identity regardless of what's typed. All of this is enforced server-side (`User::isProtectedDemoAccount()`), keyed off `DEMO_MODE` alone, with the corresponding UI controls disabled. With demo mode off, the same accounts behave like any other.

> ⚠️ Demo mode publishes working credentials on a public endpoint by design once both flags are set. Never enable it on a deployment whose admin account can reach real data.

---

## Social Login (Google & Facebook)

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

## Stripe Payments (Card Checkout)

Card payments go through Stripe's **hosted Checkout** (redirect flow) alongside Cash on Delivery — see `StripeCheckoutService`, `PaymentController`, and `StripeWebhookController`. It's optional: with no key configured, `/api/config` reports `card_payments_enabled: false` and the SPA only shows Cash on Delivery.

```
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

**Get a test key.** Sign up at [dashboard.stripe.com](https://dashboard.stripe.com) and make sure **Test mode** is on (toggle, top right). Developers → API keys → reveal the **Secret key** (`sk_test_...`) → put it in `STRIPE_SECRET`. No publishable key is needed — the app never loads Stripe.js, it just redirects to a Stripe-hosted page.

**Receiving webhooks locally.** The webhook is what actually marks an order paid — the redirect back to the SPA is just a UI state, never trusted on its own. Stripe's servers can't reach `127.0.0.1`, so pick one:

- **Stripe CLI** (simplest): `stripe listen --forward-to http://127.0.0.1:8000/api/webhooks/stripe`. Keep it running while you test; copy the `whsec_...` it prints into `STRIPE_WEBHOOK_SECRET` and restart `php artisan serve`. The secret changes on every run.
- **ngrok** (closer to a real deployment, works if the CLI isn't installed): `ngrok http --url=<your-reserved-domain> 8000` (or plain `ngrok http 8000` for a random URL each time). Then in the Dashboard: Developers → Webhooks → Add endpoint → `https://<your-domain>/api/webhooks/stripe`, subscribe to `checkout.session.completed`, reveal that endpoint's signing secret, and put it in `STRIPE_WEBHOOK_SECRET`.

  > ⚠️ The endpoint URL **must** be `https://` and point at the exact path with no redirects in front of it. Registering it as `http://` (ngrok's edge answers plain HTTP with an empty redirect to `https://`) makes Stripe receive a `307` and give up — Stripe does not follow redirects, so the request never reaches Laravel and the order silently stays unpaid. Sanity-check the tunnel before wiring up Stripe:
  > ```bash
  > curl -i https://<your-domain>/api/webhooks/stripe -X POST
  > ```
  > A `400 {"message":"Invalid signature."}` response is what you want — it proves the request reached the webhook controller and only the (expected, no real signature sent) verification failed.

  Note the CLI's signing secret and a Dashboard endpoint's signing secret are different values — use whichever one matches the delivery method you actually have running.

**Test cards** (any future expiry date, any 3-digit CVC):

| Scenario | Number |
| --- | --- |
| Succeeds | `4242 4242 4242 4242` |
| Declined — insufficient funds | `4000 0000 0000 9995` |
| Declined — generic | `4000 0000 0000 0002` |
| Requires 3D Secure, then succeeds | `4000 0025 0000 3155` |

**Verifying the loop:** checkout with "Card (Stripe)" → pay with a test card → redirected to `/checkout/return`, which polls until the order flips to Paid. Confirm the tunnel/CLI window logged a `200` for `checkout.session.completed`, and check `storage/logs/laravel.log` for the queued confirmation email (`MAIL_MAILER=log` by default).

> Stripe doesn't support merchants based in the Philippines for live payments — a real PH deployment would swap in PayMongo behind the same architecture (order created first, webhook is the sole writer of `payment_status`). Test mode has no such restriction.

---

## Tests

```bash
php artisan test
```

200+ feature and unit tests covering:
- Auth (register/login/logout/me, admin-vs-customer access to admin routes)
- Social login (Socialite mocked — new-user creation, email linking, repeat logins, no-email and provider-failure errors, null-password login rejection)
- Customer accounts (profile updates, password change, password reset flow, order history isolation)
- Public catalog (category/product filtering, search, sort, active-only visibility)
- Product variants (options/variants in the public payload, admin sync with combination-integrity validation, variant checkout: per-variant stock/price, label snapshots, rollback on overselling)
- Vouchers (discount math incl. caps and clamping, checkout redemption + used_count, every rejection rule — min spend, validity window, limits, once-per-customer — preview endpoint, admin CRUD validation)
- Flash sales (public current-event resolution: live wins over upcoming, ended/inactive ignored; admin scheduling CRUD + validation)
- Reviews (verified-purchase and duplicate gates, photo upload validation/storage, derived rating/reviews_count resync on every write, owner-only edit/delete, admin moderation incl. hide/unhide and photo-file cleanup on user/product deletion)
- Admin CRUD (categories, products, users, job openings, newsletters) including validation and authorization
- Newsletter (subscribe/welcome mail, unsubscribe tokens, resubscribe, drafts-only editing, sends skipping unsubscribed addresses)
- Dashboard stats aggregation
- Checkout (including stock-locking against overselling) and order tracking (including its trimmed, PII-free response)
- Model behavior (`Order` number generation/uniqueness, `Product` scopes, `ProductVariant` key/label helpers)

Tests run against an in-memory SQLite database (configured in `phpunit.xml`), so no database setup is needed to run them — including in CI.

---

## License

Part of the [ShopHub](../README.md) portfolio project.
