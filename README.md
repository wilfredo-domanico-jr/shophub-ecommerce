# 🛒 ShopHub — E‑Commerce Platform

ShopHub is a full-stack **e‑commerce web application** built with a **Laravel REST API** and a **Vue 3 SPA**. It's a portfolio project demonstrating a real, working store: a browsable catalog, guest checkout, order tracking, and a full admin panel — built from the ground up with an industry-standard, decoupled architecture.

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

Only **admins** have accounts — the storefront itself is guest-first:

- Admins log in at `/admin/login` via Sanctum bearer tokens
- Customers check out as guests (name, email, phone, address) — no registration required
- Guest orders are tracked later via **order number + email** lookup
- Admin routes are gated both by a Laravel middleware (`EnsureUserIsAdmin`) and a Vue Router navigation guard

---

## ✨ Core Features

### 👤 Storefront (Customer-facing)

- Home page with hero carousel, flash sale section, dynamic category bar, and trending products
- Full **product listing page** — search, category filter, sort, pagination
- **Product detail page** with quantity picker and add-to-cart
- Live **search autosuggest** in the header
- Cart → **guest checkout** (Cash on Delivery) → order confirmation with order number
- **Order tracking** by order number + email, from the header, footer, or a dedicated modal
- Real content pages: Help Center (FAQ), Returns & Refunds, Shipping Info, Our Story, Careers, Press & Media, Privacy Policy
- Order confirmation & status-update **emails**, sent via a queued Laravel Mailable

### 🛠 Admin Panel

- Dashboard with real sales/orders/products/customers stats and a 7-day sales chart
- **Products** — full CRUD, drag-and-drop image upload, search, pagination
- **Categories** — full CRUD with icon & brand-color pickers
- **Orders** — searchable/paginated list, inline status updates, full order-detail view
- **Admins** — manage who can access the admin panel
- Consistent ShopHub branding throughout (icons, gradients, empty/loading states)

---

## 📂 Project Structure

### Backend (Laravel) — see [`back-end/README.md`](back-end/README.md)

```
/app/Http/Controllers/Api      # public, guest, and admin API controllers
/app/Models                    # User, Category, Product, Order, OrderItem
/app/Mail                      # order confirmation & status-update Mailables
/database/migrations           # schema
/database/seeders              # demo categories, products, admin user
/routes/api.php                # all API routes
```

### Frontend (Vue) — see [`front-end/README.md`](front-end/README.md)

```
/src/views                     # Home, Shop, ProductDetail, InfoPage, Admin/*
/src/components                # common + admin components
/src/stores                    # Pinia stores (auth, cart)
/src/services                  # typed API clients
/src/router                    # routes + auth guard
```

---

## ⚙️ Setup Instructions

### 1️⃣ Backend (Laravel API)

```bash
cd back-end
composer install
cp .env.example .env
php artisan key:generate
# set DB_* in .env, then:
php artisan migrate --seed
php artisan serve
```

### 2️⃣ Frontend (Vue SPA)

```bash
cd front-end
npm install
npm run dev
```

Make sure `VITE_API_BASE_URL` (front-end `.env`) points at the backend's `/api` URL, and `FRONTEND_URL` (back-end `.env`) matches the frontend's dev URL for CORS.

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
- Customer accounts (currently guest-only)
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
