# 🛒 ShopHub — E‑Commerce Platform

ShopHub is a full-stack **e‑commerce web application** built with a **Laravel REST API** and a **Vue 3 SPA**. It's a portfolio project demonstrating a real, working store: a browsable catalog, customer accounts with cart and checkout, order tracking, and a full admin panel — built from the ground up with an industry-standard, decoupled architecture.

---

## Screenshot

<p align="center">
  <img src="Project-Screenshot.png" alt="ShopHub Screenshot" width="700">
</p>

---

## 🚀 Tech Stack

### Backend (API)

- **Laravel 12** — RESTful API backend (PHP 8.2)
- **MySQL** — relational database
- **Laravel Sanctum** — token-based authentication
- **Eloquent ORM** — database interaction
- **Queued Mail** — order confirmation & status-update emails

### Frontend (SPA)

- **Vue 3** + **TypeScript**
- **Vite** — build tool
- **Pinia** — state management
- **Vue Router** — client-side routing
- **Tailwind CSS** — styling
- **Axios** — API communication
- **Chart.js** — admin dashboard analytics

---

## 🧩 Architecture Overview

```
Frontend (Vue SPA)
   │
   │  Axios (HTTP Requests, Bearer token auth)
   ▼
Backend (Laravel REST API)
   │
   ▼
Database (MySQL)
```

- Frontend and backend are **fully decoupled** — the API has no knowledge of how it's rendered
- Pure token-based auth (Sanctum personal access tokens), no session/cookie coupling
- Ready for future mobile app integration

---

## 🔐 Authentication

Both **customers** and **admins** have accounts, authenticated with Sanctum bearer tokens:

- Customers register and sign in at `/login`; adding to cart and checking out require an account (guests are redirected to login and resume where they left off — including reopening checkout)
- Customer account area: profile with saved contact number & default shipping address (pre-fills checkout), password change, and order history at `/account`
- **Password reset** via an emailed link — single-use, hashed, expiring tokens
- Auth endpoints (`login`, `register`, `forgot/reset-password`) are **rate-limited** against brute force and enumeration
- Admins log in at `/admin/login`; admin routes are gated both by a Laravel middleware (`EnsureUserIsAdmin`) and a Vue Router navigation guard
- Legacy guest orders remain trackable via **order number + email** lookup (the tracking response is deliberately trimmed to status + items — no personal details)

---

## ✨ Core Features

### 👤 Storefront (Customer-facing)

- Home page with hero carousel, flash sale section, dynamic category bar, and trending products
- Full **product listing page** — search, category filter, sort, pagination
- **Product detail page** with quantity picker, add-to-cart, and **Buy Now** (single-item express checkout that leaves the cart untouched)
- Live **search autosuggest** in the header
- **Customer accounts** — registration, login, password reset via email, profile with saved contact & shipping details, and a My Orders history
- Cart → **checkout** (Cash on Delivery, sign-in required, form pre-filled from the profile) → order confirmation with order number
- **Toast notifications** for cart, auth, and admin actions
- **Order tracking** by order number + email for guests and legacy orders (header button shown to guests only — signed-in customers use My Orders)
- Real content pages: Help Center (FAQ), Returns & Refunds, Shipping Info, Our Story, Careers, Press & Media, Privacy Policy
- Order confirmation, status-update & password-reset **emails**, sent via queued Laravel Mailables

### 🛠 Admin Panel

- Dashboard with real sales/orders/products/customers stats and a 7-day sales chart
- **Products** — full CRUD, drag-and-drop image upload, search, pagination
- **Categories** — full CRUD with icon & brand-color pickers
- **Orders** — searchable/paginated list, inline status updates, full order-detail view
- **Users** — searchable admin/customer list with role filter; create, edit, and remove accounts
- Consistent ShopHub branding throughout (icons, gradients, empty/loading states)

---

## 📂 Project Structure

### Backend (Laravel) — see [`back-end/README.md`](back-end/README.md)

```
/app/Http/Controllers/Api      # public, auth/profile, and admin API controllers
/app/Models                    # User, Category, Product, Order, OrderItem
/app/Mail                      # order, status-update & password-reset Mailables
/database/migrations           # schema
/database/seeders              # demo categories, products, admin (+ demo customer)
/routes/api.php                # all API routes
```

### Frontend (Vue) — see [`front-end/README.md`](front-end/README.md)

```
/src/views                     # Home, Shop, ProductDetail, Auth/*, Account/*, Admin/*
/src/components                # common + account + admin components
/src/stores                    # Pinia stores (auth, cart, toast)
/src/composables               # shared logic (auth-gated add-to-cart)
/src/services                  # typed API clients
/src/router                    # routes + auth guards
```

### Root

```
/docker-compose.yml             # MySQL + backend + frontend, one command to run everything
/.github/workflows              # backend.yml + frontend.yml CI
```

---

## ⚙️ Setup Instructions

There are two ways to run ShopHub: natively on your machine, or via Docker Compose (no PHP/Node/MySQL install required). Pick one.

### Option A — Local (native)

**1️⃣ Backend (Laravel API)**

```bash
cd back-end
composer install
cp .env.example .env
php artisan key:generate
# set DB_* in .env, then:
php artisan migrate --seed
php artisan serve
```

**2️⃣ Frontend (Vue SPA)**

```bash
cd front-end
npm install
npm run dev
```

Make sure `VITE_API_BASE_URL` (front-end `.env`) points at the backend's `/api` URL, and `FRONTEND_URL` (back-end `.env`) matches the frontend's dev URL for CORS.

### Option B — Docker

```bash
cp .env.example .env    # set your own MySQL/demo-admin credentials
docker compose up -d --build
```

`docker-compose.yml` has no hardcoded credentials — it reads MySQL and demo-admin credentials from a root `.env` (gitignored), which Docker Compose loads automatically. Edit the copied `.env` before starting if you want your own values.

This starts MySQL, the backend API (migrated + seeded automatically), and the frontend dev server:

| Service  | URL                          |
| -------- | ----------------------------- |
| Frontend | http://localhost:5173         |
| Backend  | http://localhost:8000         |
| MySQL    | localhost:3307                |

Seeded admin login: `admin@shophub.test` / `password`. See [`back-end/README.md`](back-end/README.md#-docker) and [`front-end/README.md`](front-end/README.md#-docker) for details on what each container does.

To stop: `docker compose down` (add `-v` to also wipe the database volume).

**Demo mode:** set `DEMO_MODE=true` in the root `.env` (it's **off by default**) to add one-click "Try Demo Admin Login" and "Try Demo Customer Login" buttons to `/admin/login` and `/login` — portfolio visitors can explore both sides of the app without credentials. The demo customer account is only seeded while demo mode is on. See [`back-end/README.md`](back-end/README.md#-demo-mode) for details.

---

## 🧪 Testing

```bash
cd back-end && php artisan test     # 75+ feature & unit tests (auth, accounts, password reset, catalog, admin CRUD, checkout, tracking)
cd front-end && npm run test        # Vitest: Pinia stores (auth, cart incl. buy-now) + components
```

## ⚙️ Continuous Integration

Two GitHub Actions workflows ([`.github/workflows`](.github/workflows)) run on every push/PR that touches their respective folder:

- **`backend.yml`** — installs Composer dependencies, runs the full PHPUnit suite (SQLite in-memory, no external services needed)
- **`frontend.yml`** — installs npm dependencies, runs Vitest, then type-checks and builds

---

## 🌐 API Communication

- All data exchange is handled via REST endpoints under `/api`
- JSON request/response format
- Centralized Axios client with Bearer token auth for admin routes

---

## 📈 Scalability & Best Practices

- Clean separation of frontend and backend
- Reusable API — ready for a future mobile app
- Modular, typed frontend services per resource (products, orders, categories, admin/*)
- Admin actions guarded by both middleware and route guards

---

## 🧪 Future Enhancements

- Real payment gateway integration (currently Cash on Delivery only)
- Product reviews & ratings
- Wishlist / saved items
- Discount and voucher system

---

## 👨‍💻 Author

**Wilfredo Domanico Jr.**

Full‑stack Web Developer

---

## 📄 License

This project is for educational and portfolio purposes.

---

> 💡 _ShopHub demonstrates a production‑ready Laravel + Vue architecture commonly used in real‑world enterprise applications._
