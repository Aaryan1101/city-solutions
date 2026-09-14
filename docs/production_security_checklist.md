# Production Security Checklist

Use this before public launch and after every backend upload.

## Hostinger File Protection

- Upload `backend/.htaccess`.
- Upload `backend/public/.htaccess`.
- Upload `backend/public/uploads/.htaccess`.
- Keep `backend/.env` on the server only. Do not share it publicly.
- Confirm these URLs return forbidden/not found:
  - `/app/`
  - `/storage/`
  - `/database/`
  - `/.env`
  - `/database/schema_mysql.sql`

## Admin Access

- Change any default admin password immediately.
- Change `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `backend/.env`; do not leave `admin@citysolutions.local` / `password`.
- Change demo vendor, provider, hotel owner, and real estate agent passwords after importing demo data.
- Use strong unique passwords for admin, vendor, provider, hotel owner, and real estate agent accounts.
- Keep only trusted users as `super_admin`.
- Use `zone_manager` for zone-wise staff instead of sharing super admin access.
- Check `/admin/health` after upload.

## Runtime Settings

- Set `APP_DEBUG=false` in `backend/.env`.
- Set `APP_URL` to the public HTTPS Hostinger domain.
- Set `DB_CONNECTION=mysql` on Hostinger; do not run production on SQLite.
- Keep API write limit enabled in Settings.
- Keep panel session timeout at a reasonable value such as 60-120 minutes.
- Configure Firebase and payment credentials only inside the admin panel or `.env`, not in source files.
- Disable payment methods that are not fully configured.

## Upload Safety

- Only image uploads are allowed for catalog/content images.
- Floating ads allow image/video media only.
- Uploaded PHP/script files are blocked by `.htaccess`.
- Keep `public/uploads` writable, but do not make the whole backend writable.

## Launch Verification

- Test login throttling by attempting repeated wrong logins.
- Test customer login from the app.
- Test order/booking placement in each module.
- Test upload forms with valid images and reject invalid files.
- Check server error logs after testing.
