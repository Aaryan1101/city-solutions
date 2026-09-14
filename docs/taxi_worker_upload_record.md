# Taxi + Worker Upload Record

Upload these backend paths to Hostinger after this implementation:

- `backend/app`
- `backend/public`
- `backend/resources`
- `backend/database/taxi_worker_mysql.sql`

Import in phpMyAdmin:

- `backend/database/taxi_worker_mysql.sql`

New admin/backend routes:

- `/admin/taxi`
- `/api/v1/taxi/config`
- `/api/v1/taxi/quote`
- `/api/v1/taxi/rides`
- `/api/v1/taxi/rides/{id}`
- `/api/v1/taxi/rides/{id}/cancel`
- `/api/v1/taxi/rides/{id}/rate`
- `/api/v1/zones/reverse-geocode`
- `/api/v1/zones/search`
- `/api/v1/workers/login`
- `/api/v1/workers/me`
- `/api/v1/workers/assignments`
- `/api/v1/workers/assignments/status`
- `/api/v1/workers/location`
- `/api/v1/workers/device-token`

Demo taxi worker:

- Phone: `9000000001`
- Password: `123456`

The demo driver cannot receive production Taxi offers until an admin uploads
license, RC, and insurance images and sets future license/insurance expiry
dates in `/admin/taxi`. Replace the demo password before public testing.

APK build commands:

```bash
HOME=/tmp /home/potato/flutter/bin/flutter build apk --release --flavor customer -t lib/main.dart
HOME=/tmp /home/potato/flutter/bin/flutter build apk --release --flavor worker -t lib/main_worker.dart
```

APK output paths:

- `build/app/outputs/flutter-apk/app-customer-release.apk`
- `build/app/outputs/flutter-apk/app-worker-release.apk`

Implemented taxi/worker production behavior:

- Taxi is independent from Mart/E-Com/Services tables.
- Rider pickup and destination are selected on Google Maps or through address search.
- Route distance/time and all vehicle fares are issued by a short-lived, one-use server quote; the app cannot submit its own distance or fare.
- Taxi offers go to fresh, online, approved, KYC-complete drivers in the matching zone and vehicle type. The first atomic accept wins.
- Worker app can go online/offline and update live location.
- Customer ride tracking polls live status/location and shows the assigned driver, vehicle, route, OTP, cancellation, and rating actions.
- Taxi trips require OTP before the worker can start the ride.
- Cancellation fee applies after driver assignment/arrival.
- Wallet rides are refunded on eligible customer, driver, or admin cancellation with idempotent ledger references.
- Completed taxi rides create driver earning and commission records in `taxi_driver_ledgers`.
- Taxi admin controls vehicle fare rules, wait/cancellation fees, cash/wallet availability, quote validity, Routes key, driver commission, KYC, dispatch, and ride status.
- `/admin/health` checks Taxi schema, Routes key, active vehicle types, zone drivers, KYC, expiry dates, and payment availability.
- Worker login is rate limited and worker API tokens expire using `worker_session_timeout_hours` setting, default `168` hours.
- Worker Firebase token endpoint is available; actual push delivery still depends on Firebase credentials and app-side FCM token wiring.

## Required Google setup

- Android key: restrict it to `com.citysolutions.app` and `com.citysolutions.worker` with their signing SHA certificates; enable Maps SDK for Android.
- Server key: restrict by Hostinger server IP where possible; enable Routes API and Geocoding API; enter it in `/admin/taxi`. Address search and reverse geocoding are proxied through the backend so this key never enters the APK.
- Do not put the unrestricted server key in the APK.

## Live acceptance test

1. Import `backend/database/taxi_worker_mysql.sql` and open `/admin/taxi` once.
2. Configure the Routes key, quote validity, enabled payments, commission, and support phone.
3. Upload KYC and future expiry dates for a zone-assigned driver.
4. Log into the worker APK and go online with GPS enabled.
5. In the customer APK, log in, select map pickup/drop points, refresh a fare quote, and request a ride.
6. Accept in Worker, mark arrived, enter the OTP shown only to the rider, start, complete, and submit a rider rating.
7. Repeat once with Wallet and cancel to verify the refund ledger.
