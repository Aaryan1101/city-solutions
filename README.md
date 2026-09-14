<p align="center">
  <img src="assets/images/city_solution_logo.jpeg" alt="City Solutions logo" width="180">
</p>

<h1 align="center">City Solutions</h1>

<p align="center">
  A full-stack, multi-service city marketplace built with Flutter and PHP.
</p>

<p align="center">
  <img alt="Flutter" src="https://img.shields.io/badge/Flutter-Android-02569B?logo=flutter">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php">
  <img alt="Database" src="https://img.shields.io/badge/Database-MySQL%20%7C%20SQLite-4479A1?logo=mysql">
  <img alt="Firebase" src="https://img.shields.io/badge/Firebase-Auth%20%7C%20FCM-FFCA28?logo=firebase&logoColor=black">
</p>

## Overview

City Solutions brings everyday local services into one mobile experience. A customer can shop for groceries and general products, order medicines, book home services and hotels, discover restaurants and property, or request a taxi. A separate worker app handles field assignments, while the PHP backend provides REST APIs, a public website, and role-based operational panels.

This repository is a portfolio project and includes the application source, database schemas, demo data, deployment documentation, and safe configuration templates. Production credentials are intentionally excluded.

## Highlights

- Two Android product flavors from one Flutter codebase: customer and worker.
- Seven customer modules: Mart, E-Commerce, Medical, Services, Hotels, Restaurants, and Real Estate, plus Taxi workflows.
- Zone-aware catalogs and serviceability, search, carts, checkout, bookings, orders, wishlists, reviews, wallets, refunds, complaints, and support.
- Worker flows for delivery, service jobs, and taxi assignments with status and location updates.
- PHP REST API with MySQL/SQLite support and no framework runtime dependency.
- Admin, vendor, service-provider, hotel-owner, and real-estate-agent panels.
- Firebase integration points for Phone OTP, Google Sign-In, App Check, and FCM notifications.
- Google Maps integration points for maps, geocoding, directions, and route calculations.
- Payment configuration, webhook endpoints, reporting, invoices, promotions, POS foundations, and production health checks.

## Architecture

```text
Flutter customer app ─┐
                     ├── REST/JSON ── PHP backend ── MySQL (production)
Flutter worker app ──┘                    │          SQLite (local demo)
                                         ├── Public website and legal pages
                                         ├── Admin and partner panels
                                         └── Firebase, Maps and payment adapters
```

| Area | Location | Responsibility |
| --- | --- | --- |
| Customer mobile app | `lib/main.dart`, `lib/features/` | Shopping, bookings, account, support, location, and module experiences |
| Worker mobile app | `lib/main_worker.dart`, `lib/worker/` | Authentication, assignments, navigation, status, and location updates |
| Backend/API | `backend/app/`, `backend/public/` | REST endpoints, business logic, panels, public pages, and uploads |
| Database | `backend/database/` | MySQL/SQLite schemas, migrations, and optional demo records |
| Deployment guides | `docs/` | Firebase, Google Cloud, Hostinger, payments, security, and launch checklists |

## Technology

- Flutter and Dart
- PHP 8.1+ with PDO
- MySQL for deployment; SQLite for local development
- Firebase Authentication, Cloud Messaging, and App Check
- Google Maps Platform
- REST/JSON APIs and role-based web panels

## Local Setup

### Prerequisites

- Flutter SDK compatible with Dart `>=3.2.0 <4.0.0`
- Android Studio/SDK and Java 17
- PHP 8.1+ with `pdo_sqlite` for the local demo, or PDO MySQL for a MySQL database
- Git

### 1. Clone and install Flutter dependencies

```bash
git clone https://github.com/Aaryan1101/city-solutions.git
cd city-solutions
flutter pub get
```

### 2. Start the local backend

```bash
cp backend/.env.example backend/.env
php backend/scripts/setup_sqlite.php
php -S 127.0.0.1:8088 -t backend/public
```

Then open `http://127.0.0.1:8088/admin/login`.

Demo administrator credentials are `admin@citysolutions.local` / `password`. These are for local development only and must be replaced in any deployed environment.

### 3. Configure Maps (optional for initial UI testing)

```bash
cp android/secrets.properties.example android/secrets.properties
```

Replace the placeholder in `android/secrets.properties` with an Android-restricted Maps key. The real file is ignored by Git.

### 4. Run an Android app

Customer app:

```bash
flutter run --flavor customer -t lib/main.dart
```

Worker app:

```bash
flutter run --flavor worker -t lib/main_worker.dart
```

The source currently contains a hosted API fallback. For another backend, supply the appropriate compile-time URL, for example:

```bash
flutter run --flavor customer -t lib/main.dart \
  --dart-define=CITY_API_BASE_URL=https://your-domain.com/api/v1 \
  --dart-define=MART_API_BASE_URL=https://your-domain.com/api/v1/mart \
  --dart-define=ECOMMERCE_API_BASE_URL=https://your-domain.com/api/v1/ecommerce
```

Other supported module defines include `SERVICES_API_BASE_URL`, `HOTEL_API_BASE_URL`, `RESTAURANT_API_BASE_URL`, `REAL_ESTATE_API_BASE_URL`, `CITY_TAXI_API_BASE_URL`, and `CITY_WORKER_API_BASE_URL`.

## Firebase Setup

Firebase is fault-tolerant and optional during basic development: the apps can start without native Firebase configuration, but authentication, push notifications, and App Check will remain disabled.

Register both Android applications in one Firebase project and place the downloaded files here:

| App | Package ID | Local config file |
| --- | --- | --- |
| Customer | `com.citysolutions.app` | `android/app/src/customer/google-services.json` |
| Worker | `com.citysolutions.worker` | `android/app/src/worker/google-services.json` |

These files contain project-specific configuration and are ignored by Git. Follow [Firebase and Google Cloud production setup](docs/FIREBASE_GOOGLE_CLOUD_SETUP.md) for provider, service-account, Maps, Routes, backend, and build steps.

## Build and Quality Checks

```bash
flutter analyze
flutter test
flutter build apk --release --flavor customer -t lib/main.dart
flutter build apk --release --flavor worker -t lib/main_worker.dart
```

Before a production release, replace the temporary debug signing configuration with a private release keystore.

## Deployment

For a production-style backend deployment:

1. Point a domain or subdomain at `backend/public`, or use the included root forwarding files.
2. Copy `backend/.env.hostinger.example` to `.env` on the server and replace every placeholder.
3. Import `backend/database/schema_mysql.sql`, followed by optional demo SQL only in a non-production environment.
4. Make runtime storage and upload directories writable.
5. Open `/admin/health` and resolve every reported action before launch.

See the [Hostinger deployment checklist](docs/hostinger_backend_upload_checklist.md) and [production security checklist](docs/production_security_checklist.md).

## Current Status and Remaining Setup

The application and backend foundations are implemented, but a cloned copy is not production-ready until its owner completes the external configuration below:

- Register both Android apps in Firebase and add the two local `google-services.json` files.
- Enable Firebase Phone Authentication, Google Sign-In, Cloud Messaging, and App Check; configure the backend service account.
- Create restricted Google Maps keys and enable the required Maps, Routes, Places, and Geocoding APIs.
- Configure a production domain, HTTPS, MySQL database, secure `APP_KEY`, and non-default administrator credentials.
- Select and configure a live payment gateway, secrets, verified webhook URLs, and refund handling.
- Add release signing, final package/store metadata, privacy/legal business details, and store accounts.
- Perform device-level end-to-end testing for OTP, notifications, maps, payments, uploads, background location, and every customer/worker workflow.
- Complete optional production-depth items such as carrier tracking, richer POS hardware support, realtime chat, analytics/crash reporting, and licensed final media.

The complete account and credential checklist is in [External Services Setup](docs/external_services_setup_guide.md). Deferred Mart enhancements are tracked in [Mart Future Tasks](docs/mart_future_tasks.md).

## Security Notes

- Never commit `.env`, Maps keys, Firebase config/service-account files, signing keystores, payment secrets, or generated databases.
- Demo credentials and sample data are not suitable for production.
- Restrict keys by package, SHA fingerprint, API, host, and server IP wherever the provider supports it.

## Repository Scope

This repository currently targets Android. The backend can be tested locally with SQLite and deployed with MySQL. External services and production credentials are deliberately left to each deployer, so cloning the source does not activate paid APIs or live authentication automatically.
