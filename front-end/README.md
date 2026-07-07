# 🛍 ShopHub — Frontend (SPA)

This is the Vue 3 storefront and admin panel for [ShopHub](../README.md) — a fully decoupled SPA that talks to the [Laravel API](../back-end/README.md) over REST.

---

## 🚀 Tech Stack

- **Vue 3** + **TypeScript** (`<script setup>`)
- **Vite** — build tool
- **Pinia** — state management (auth, cart)
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
- `/products` — searchable, filterable, sortable, paginated product listing
- `/products/:slug` — product detail with quantity picker & add-to-cart
- Live search autosuggest in the header (`SearchAutosuggest.vue`)
- Cart → guest checkout (`CheckoutModal.vue`) → order confirmation
- Order tracking modal (order number + email)
- Content pages: `/help-center`, `/returns-refunds`, `/shipping-info`, `/our-story`, `/careers`, `/press-media`, `/privacy-policy` (driven by `src/data/infoPages.ts`)

### Admin panel (`/admin`)

- Route-guarded (`router.beforeEach` checks `auth.isAdmin`)
- Dashboard — real stats + 7-day sales chart
- Products — CRUD, drag-and-drop image upload, search, pagination
- Categories — CRUD with icon/color pickers
- Orders — searchable/paginated list, inline status updates, full order-detail modal
- Admins — manage who can log in to the admin panel

---

## 📂 Project Structure

```
src/
  views/                  # Home, Shop, ProductDetail, InfoPage, Auth/Login, Admin/*
  components/
    common/               # Header, Footer, Cart/Checkout/OrderTracking modals, ProductCard, etc.
    home/                 # homepage-specific sections
    admin/                # ImageDropzone, Pagination
  stores/                 # Pinia: auth.ts, cart.ts
  services/               # typed Axios clients: products, categories, orders, admin/*
  router/                 # routes + auth guard
  data/                   # static content (info pages)
```

---

## ⚙️ Setup

```bash
npm install
npm run dev
```

Set `VITE_API_BASE_URL` in `.env` to the backend's API URL, e.g.:

```
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Make sure this matches whatever port Vite actually starts on when you set the backend's `FRONTEND_URL` (for CORS) — if the default port is taken, Vite silently picks the next one.

---

## 🏗 Build

```bash
npm run build      # type-checks (vue-tsc) then builds
npm run preview    # preview the production build
```

---

## 📄 License

Part of the [ShopHub](../README.md) portfolio project.
