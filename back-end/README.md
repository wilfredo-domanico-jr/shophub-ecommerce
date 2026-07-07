# 🛠 ShopHub — Backend API

This is the Laravel REST API powering [ShopHub](../README.md) — it serves JSON to the Vue storefront and admin panel and isn't meant to be browsed directly (visiting `/` shows a small status page confirming the API is running).

---

## 🚀 Tech Stack

- **Laravel 12** (PHP 8.2)
- **MySQL**
- **Laravel Sanctum** — token-based authentication (no session/cookie coupling)
- **Eloquent ORM**
- **Queued Mail** — order confirmation & status-update emails

---

## 🔐 Authentication

Only admins have accounts. Login issues a Sanctum personal access token, sent as a `Bearer` header on every authenticated request. Admin-only routes are additionally gated by an `EnsureUserIsAdmin` middleware.

---

## 🌐 API Overview

### Public

```
GET  /api/categories               # active categories, with product counts
GET  /api/categories/{slug}
GET  /api/products                 # ?search=&category=&sort=&featured=&flash_sale=&page=
GET  /api/products/{slug}
```

### Guest checkout & tracking

```
POST /api/orders                   # create order from cart (stock-locked, sends confirmation email)
POST /api/orders/track             # lookup by order_number + email
```

### Auth

```
POST /api/login
POST /api/logout                   # auth:sanctum
GET  /api/me                       # auth:sanctum
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
User        — is_admin flag; only admins have accounts
Category    — name, slug, icon, color_class (brand gradient), product count
Product     — belongs to Category; price, original_price, stock, flash-sale/featured flags
Order       — guest customer details, order_number, status, payment method/status, totals
OrderItem   — snapshot of product name/price at time of order
```

---

## 📧 Email

Order confirmation and status-update emails are sent via queued Laravel Mailables (`app/Mail/OrderConfirmationMail.php`, `OrderStatusUpdatedMail.php`) rendered from Markdown Blade templates.

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

## ⚙️ Setup

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
- 20 demo categories
- ~15 demo products (some featured, some flash-sale)

Run the server, queue worker, and log tailer together via:

```bash
composer run dev
```

(This also starts Laravel's own unused default Vite pipeline — it does **not** start the actual Vue SPA. Run that separately from `front-end/`.)

---

## 🧪 Tests

```bash
php artisan test
```

Feature tests cover guest checkout (including stock-locking against overselling) and order tracking.

---

## 📄 License

Part of the [ShopHub](../README.md) portfolio project.
