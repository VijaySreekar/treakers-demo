# Treakers — Sneaker E‑Commerce Demo (PHP + SQLite)

![Treakers Logo](Assets/Images/Treakers%20Logo.png)

Portfolio-ready demo of a multi‑page PHP e‑commerce site (browse, cart, checkout, orders, reviews) with an admin dashboard. It runs out-of-the-box on a seeded **SQLite `.db`** and is deployable on **Vercel** (serverless-friendly demo mode).

## Live Demo
- Vercel: _add your link here_ (e.g. `https://your-project.vercel.app`)

## Demo Accounts
- User: `demo@example.com` / `demo1234`
- Admin: `admin@example.com` / `admin1234`

## Features
**Shop**
- Categories + product pages
- Search autocomplete (AJAX)
- Cart + checkout (PayPal optional; disabled by default)
- Orders, order tracking, returns (demo)
- Product reviews (one review per order item)

**Admin**
- Dashboard metrics
- Manage categories, products, users, and orders
- Demo-safe images: use an `http(s)` **Image URL** (uploads don’t persist on Vercel)

## Tech Stack
- PHP (server-rendered pages)
- SQLite (demo mode)
- Bootstrap + custom CSS
- jQuery + Swiper
- Vercel deployment via `vercel-php` runtime

## Screenshots
![Login](<Assets/Images/Shoe 1 (Login).png>)
![Registration](<Assets/Images/Shoe 2 (Registration).png>)

## Getting Started (Local)
### 1) Requirements
- PHP 8+ recommended

### 2) Run the site (SQLite demo)
From this folder:

`php -S localhost:8000`

Then open:
- `http://localhost:8000`

On first load it creates `data/treakers-demo.db` using `data/sqlite_demo.sql` and seeds demo data.

Reset demo data:
- Delete `data/treakers-demo.db` and refresh the page.

## Deployment (Vercel)
This repo includes:
- `vercel.json` (routes all requests to PHP)
- `api/index.php` (front controller for serverless)

### Steps
1. Push this repo to GitHub.
2. In Vercel, click **Add New → Project** and import the repo (preset: **Other**).
3. Deploy.

Notes:
- On Vercel the SQLite database is stored in `/tmp/treakers-demo.db` (serverless temp storage), so data may reset on cold starts — perfect for a portfolio demo.
- For images on Vercel, prefer the **Image URL** fields in the admin dashboard.

## Environment Variables
All optional for demo mode:
- `DB_DRIVER=sqlite`
- `SQLITE_PATH` (override the SQLite path locally)
- `PAYPAL_CLIENT_ID` (enables PayPal buttons)

Optional MySQL mode (not required for the portfolio demo):
- `DB_DRIVER=mysql`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`

## Project Structure (High Level)
- `index.php` — Landing page
- `other_pages/` — Pages (products, basket, login, admin, etc.)
- `Assets/` — CSS/JS/images + DB connector + shared functions
- `Includes/` — Shared navbar/footer
- `api/index.php` — Vercel PHP router
