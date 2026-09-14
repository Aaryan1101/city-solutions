# Hostinger Backend Upload Checklist

Use this after backend changes and before live app testing on Hostinger.

## Upload When Ready

Upload the backend after you have:

- A Hostinger domain or subdomain for the backend, for example `mart.yourdomain.com`.
- A Hostinger MySQL database name.
- A Hostinger MySQL username.
- A Hostinger MySQL password.
- PHP 8.1 or newer selected in Hostinger.

## What To Upload

Upload this folder:

```text
backend/
```

Best option: the web document root should point to:

```text
backend/public
```

If Hostinger keeps the web document root as:

```text
public_html
```

then upload the contents of `backend/` directly into `public_html`. The root `index.php` and `.htaccess` are included for this case and will route requests into `public/`.

The root URL serves the public City Solutions website and legal pages. The admin panel is available at:

```text
/admin/login
```

After upload, also check:

```text
/
/privacy-policy
/terms
/refund-cancellation
/medical-compliance
/contact
/admin/health
```

## Configure `.env`

Create `backend/.env` from `backend/.env.hostinger.example` and set:

```text
APP_URL=https://your-hostinger-backend-domain.com
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=your_hostinger_mysql_host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

If you see this error:

```text
SQLSTATE[HY000] [14] unable to open database file
```

then `.env` is still using SQLite. Change `DB_CONNECTION` to `mysql` and fill the Hostinger MySQL credentials.

## Database

Import this file in phpMyAdmin:

```text
backend/database/schema_mysql.sql
```

For cross-module demo data, import this after the schema:

```text
backend/database/demo_data_mysql.sql
```

If Hostinger CLI access is available, run:

```bash
php backend/scripts/setup_mysql.php
```

That will create tables and seed starter admin/catalog data.

Default admin:

```text
admin@citysolutions.local
password
```

Change this password immediately after upload. Also change `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`; do not leave `admin@citysolutions.local` / `password` in production.

If demo data was imported, change demo vendor, provider, hotel owner, and real estate agent passwords before giving panel access to anyone.

## Curl Tests After Upload

Once uploaded, test:

```bash
curl https://your-hostinger-backend-domain.com/api/v1/mart/config
curl https://your-hostinger-backend-domain.com/api/v1/mart/categories
curl https://your-hostinger-backend-domain.com/api/v1/mart/products
curl https://your-hostinger-backend-domain.com/api/v1/ecommerce/config
curl https://your-hostinger-backend-domain.com/api/v1/medical/config
curl https://your-hostinger-backend-domain.com/api/v1/services/config
curl https://your-hostinger-backend-domain.com/api/v1/services/categories
curl https://your-hostinger-backend-domain.com/api/v1/services/services
curl https://your-hostinger-backend-domain.com/api/v1/hotels/home
curl https://your-hostinger-backend-domain.com/api/v1/restaurants/home
curl https://your-hostinger-backend-domain.com/api/v1/real-estate/home
curl https://your-hostinger-backend-domain.com/api/v1/app/config
```

Then test cart/order:

```bash
curl -X POST https://your-hostinger-backend-domain.com/api/v1/mart/cart/add?guest_id=test-guest \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":2}'

curl -X POST https://your-hostinger-backend-domain.com/api/v1/mart/orders/place \
  -H "Content-Type: application/json" \
  -d '{"guest_id":"test-guest","customer_name":"Test User","customer_phone":"9999999999","address":"Lucknow","payment_method":"cash_on_delivery"}'
```

Before public launch, open `/admin/health` as a super admin. Treat any `action_needed` rows as deployment work unless the row explicitly says the related external integration is disabled.
