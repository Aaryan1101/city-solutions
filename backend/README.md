# City Solutions Backend

Panel-first backend and public website for City Solutions modules: Mart, E-Commerce, Medical, Services, Hotels, Restaurants, and Real Estate.

The web root serves the public website. Admin and partner panels are available from `/admin/login` and the role-specific panel links.

## Local Setup

This machine needs either `pdo_sqlite` or a running MySQL server for local DB tests.

```bash
cd city-solutions
cp backend/.env.example backend/.env
php backend/scripts/setup_sqlite.php
php -S 127.0.0.1:8088 -t backend/public
```

Admin:

```text
http://127.0.0.1:8088/admin/login
admin@citysolutions.local
password
```

API examples:

```bash
curl http://127.0.0.1:8088/api/v1/mart/config
curl http://127.0.0.1:8088/api/v1/mart/categories
curl http://127.0.0.1:8088/api/v1/mart/products
```

## Demo Data

After importing `database/schema_mysql.sql`, import:

```text
database/demo_data_mysql.sql
```

This adds cross-module demo zones, vendors, products, services, hotels, restaurants, real estate records, banners, floating ads, and sample operational data for checking app/admin reflection.

## Hostinger Notes

- Upload `backend/`.
- Best option: point the domain/subdomain document root to `backend/public`.
- If Hostinger keeps the document root as `public_html`, upload the contents of `backend/` directly into `public_html`; the root `index.php` and `.htaccess` will forward requests into `public/`.
- Copy `.env.example` to `.env`.
- Set MySQL credentials in `.env`.
- Import `backend/database/schema_mysql.sql` through phpMyAdmin, or run `php backend/scripts/setup_mysql.php` if CLI access is available.
- Import `backend/database/demo_data_mysql.sql` after the schema when you need a populated test environment.
- Open `/admin/health` after upload and resolve any `action_needed` rows before production use.
- Default seeded admin is `admin@citysolutions.local` / `password`; change it immediately after deployment.
- If demo data is imported, change demo vendor, provider, hotel owner, and real estate agent passwords before launch.
