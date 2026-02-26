# Treakers — Aston University Year 2 Team Project (Team 27)

![Treakers Logo](Assets/Images/Treakers%20Logo.png)

Treakers is a sneaker e-commerce web application built as part of our Aston University BSc (Year 2) Team Project (Team 27). The app includes a customer storefront and an admin dashboard.

## Live site
- https://treakers-demo.vercel.app/

## Demo accounts
- User: `demo@example.com` / `demo1234`
- Admin: `admin@example.com` / `admin1234`

## Features
### Shop
- Browse categories and products
- Product search autocomplete (AJAX)
- Basket + checkout flow (PayPal optional)
- Orders and product reviews

### Admin
- Admin dashboard
- Manage categories, products, users, and orders
- Product/category images via `http(s)` image URL (uploads don’t persist on Vercel)

## How it works (high level)
- The UI is server-rendered with PHP pages (for example `index.php` and pages under `other_pages/`).
- Shared helpers live in `Assets/Functions/` and the database connection/seed logic lives in `Assets/Database/connectdb.php`.
- On Vercel, `vercel.json` routes requests to `api/index.php`. The router maps the requested path to the matching PHP file and executes it. Static files (CSS/JS/images) are served directly.

## Tech stack
- PHP (server-rendered pages)
- SQLite (demo database)
- Bootstrap + custom CSS
- jQuery + Swiper
- Vercel via the `vercel-php` runtime

## Screenshots
![Login](<Assets/Images/Shoe 1 (Login).png>)
![Registration](<Assets/Images/Shoe 2 (Registration).png>)

## Run locally
### Requirements
- PHP 8+ recommended

### Start
From the repo root:

```sh
php -S localhost:8000
```

Then open `http://localhost:8000`.

On first load it creates `data/treakers-demo.db` using `data/sqlite_demo.sql` and seeds demo data.

Reset demo data:
- Delete `data/treakers-demo.db` and refresh the page.

## Deploy to Vercel
This repo includes:
- `vercel.json` (routes requests to the PHP router)
- `api/index.php` (serverless front controller)

Steps:
1. Push this repo to GitHub.
2. In Vercel, click **Add New → Project** and import the repo.
3. Deploy.

Notes:
- On Vercel the SQLite database is stored in the serverless temp directory (`/tmp/treakers-demo.db`), so data may reset between invocations.
- For images on Vercel, use the image URL fields in the admin dashboard.

## Environment variables
All optional:
- `DB_DRIVER` (`sqlite` or `mysql`, default: `sqlite`)
- `SQLITE_PATH` (override the SQLite path locally)
- `PAYPAL_CLIENT_ID` (enables PayPal buttons)

MySQL (only if `DB_DRIVER=mysql`):
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`

## Project structure (high level)
- `index.php` — Landing page
- `other_pages/` — Pages (products, basket, login, admin, etc.)
- `Assets/` — CSS/JS/images + DB connector + shared functions
- `Includes/` — Shared navbar/footer
- `api/index.php` — Vercel PHP router
