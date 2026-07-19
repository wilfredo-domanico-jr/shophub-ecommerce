# 🛍 ShopHub — Frontend (SPA)

This is the Vue 3 storefront and admin panel for [ShopHub](../README.md) — a fully decoupled SPA that talks to the [Laravel API](../back-end/README.md) over REST.

---

## 🚀 Tech Stack

- **Vue 3** + **TypeScript** (`<script setup>`)
- **Vite** — build tool
- **Pinia** — state management (auth, cart, toasts)
- **Vue Router** — routing + admin auth guard
- **Tailwind CSS** — styling, with a centralized brand theme
- **Axios** — typed API service layer
- **Chart.js** (`vue-chartjs`) — admin dashboard sales chart

---

## 🎨 Branding

Centralized in `tailwind.config.js` and `src/assets/main.css`:

- **Colors** — brand primary (orange gradient), secondary (pink/purple), accent (teal/blue), success (green)
- **Fonts** — Poppins (body), Outfit (display/headings)

---

## ✨ Pages & Features

### Storefront (`/`)

- Home — hero carousel, flash sale, dynamic category bar, shop-by-category, trending products
- **Scheduled flash sale countdown** — the section follows the admin-set schedule via `GET /api/flash-sale`: "Starts In" before opening (grid hidden), "Ends In" while live, hidden entirely when nothing is scheduled; the upcoming→live flip happens client-side the second the countdown hits zero
- `/products` — searchable, filterable, sortable, paginated product listing
- `/products/:slug` — product detail with quantity picker, add-to-cart & **Buy Now** (single-item checkout, cart untouched)
- **Product variations** — option pickers (Color, Size, …) on the detail page; sold-out combinations are disabled, and price, photo, and stock switch to the selected variant. Each variant is its own cart line, and quick-add on product cards routes variant products to the detail page first
- Live search autosuggest in the header (`SearchAutosuggest.vue`)
- **Customer accounts** — `/login`, `/register`, `/forgot-password`, `/reset-password`, plus `/account` (profile) and `/account/orders` (order history), all backed by router guards (`requiresAuth` / `guestOnly`)
- **Social login** — "Continue with Google / Facebook" buttons on login & register (shown only for providers the backend reports as configured via `/api/config`), with an `/auth/callback` landing page that scrubs the token from history, signs the user in, and resumes any pending redirect. Backend setup: [`docs/SOCIAL_LOGIN_SETUP.md`](../docs/SOCIAL_LOGIN_SETUP.md)
- Add-to-cart and checkout require sign-in — guests are redirected to `/login?redirect=...` and resume where they left off (a pending checkout reopens automatically via `?checkout=1`)
- Cart → checkout (`CheckoutModal.vue`, pre-filled from the saved profile) → order confirmation
- **Voucher codes** at checkout — live discount preview via `/api/vouchers/preview`, inline validation errors, and a "You saved ₱X" confirmation; discounts also show in My Orders and order tracking. Public vouchers are discoverable on a `/vouchers` page (copy-to-clipboard, linked from the footer) and as tap-to-apply suggestions inside the checkout modal
- Global **toast notifications** (`stores/toast.ts` + `ToastContainer.vue`) for cart, auth, and admin feedback
- Order tracking modal (order number + email) — header entry point shown to guests only
- Footer **newsletter signup** (welcome email) and a public `/unsubscribe` page for the one-click links in every newsletter email
- Content pages: `/help-center`, `/returns-refunds`, `/shipping-info`, `/our-story`, `/careers`, `/press-media`, `/privacy-policy` (driven by `src/data/infoPages.ts`); the Careers page renders live job openings from the API

### Admin panel (`/admin`)

- Route-guarded (`router.beforeEach` checks `auth.isAdmin`)
- Dashboard — real stats + 7-day sales chart
- Products — CRUD, drag-and-drop image upload, search, pagination, a **variant editor** (define up to 3 options, generate the combinations, then set per-variant stock, price overrides, and photos — product stock is derived from the variant total), plus original price and featured / flash-sale (+ goal) / active flags
- Flash Sales — schedule sale windows with live/upcoming/ended/disabled status badges; the homepage section follows this schedule
- Categories — CRUD with a searchable icon picker (200+ Heroicons, regenerable via `scripts/generate-category-icons.mjs`) and color choice: brand gradient presets or any custom color via a native color picker
- Orders — searchable/paginated list, inline status updates, full order-detail modal
- Users — searchable admin/customer list with role filter; create, edit, remove
- Careers — manage the job openings shown on the public Careers page (publish/hide, edit, delete)
- Vouchers — create/edit discount codes (percent with optional cap, or fixed amount) with min spend, validity dates, usage limits, once-per-customer, and an active toggle; usage counts shown live
- Newsletter — two tabs: Campaigns (write with optional banner image, save as draft, send to all subscribers) and Subscribers (search, status, remove)

---

## 📂 Project Structure

```
src/
  views/                  # Home, Shop, ProductDetail, InfoPage, Auth/*, Account/*, Admin/*
  components/
    common/               # Header, Footer, Cart/Checkout/OrderTracking modals, ToastContainer, ProductCard, etc.
    home/                 # homepage-specific sections
    account/              # AccountNav
    auth/                 # SocialLoginButtons
    admin/                # ImageDropzone
  stores/                 # Pinia: auth.ts, cart.ts (incl. buy-now), toast.ts
  composables/            # useAddToCart (auth-gated add-to-cart / buy-now guard)
  services/               # typed Axios clients: products, categories, orders, account, config, admin/*
  router/                 # routes + auth guards (requiresAuth, requiresAdmin, guestOnly)
  utils/                  # small shared helpers (e.g. category color class/hex rendering)
  data/                   # static content (info pages, generated category icon library)
scripts/                  # dev utilities (regenerate the category icon library)
```

---

## ⚙️ Setup (local)

```bash
npm install
cp .env.example .env
npm run dev
```

Set `VITE_API_BASE_URL` in `.env` to the backend's API URL, e.g.:

```
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Make sure this matches whatever port Vite actually starts on when you set the backend's `FRONTEND_URL` (for CORS) — if the default port is taken, Vite silently picks the next one.

---

## 🐳 Docker

From the repo root:

```bash
docker compose up -d --build frontend
```

Runs `npm ci` then the Vite dev server (with HMR) inside a `node:20-alpine` container, bound to `0.0.0.0:5173` so it's reachable from the host at http://localhost:5173. `VITE_API_BASE_URL` is supplied via `docker-compose.yml`'s `environment:` block rather than a `.env` file.

This image is dev-oriented (matches `npm run dev`), not a production build — for a real deployment you'd build a static bundle (`npm run build`) and serve `dist/` from a static host or an nginx image instead.

---

## 🧪 Testing

```bash
npm run test            # run once (Vitest)
npm run test:watch      # watch mode
npm run test:coverage   # with coverage (v8)
```

Tests live alongside the code they cover, in `__tests__/` folders (e.g. `src/stores/__tests__/cart.spec.ts`). Currently covers the `cart` (including buy-now semantics) and `auth` (including social token login and cart-clearing logout) Pinia stores and the `StarRating`/`Pagination` components.

---

## 🏗 Build

```bash
npm run build      # type-checks (vue-tsc) then builds
npm run preview    # preview the production build
```

---

## 📄 License

Part of the [ShopHub](../README.md) portfolio project.
