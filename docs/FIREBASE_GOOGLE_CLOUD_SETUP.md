# Firebase and Google Cloud production setup

City Solutions uses one Firebase project and two independently registered Android apps.

| App | Android package | Firebase config destination |
| --- | --- | --- |
| Customer app | `com.citysolutions.app` | `android/app/src/customer/google-services.json` |
| Worker app | `com.citysolutions.worker` | `android/app/src/worker/google-services.json` |

Do not upload either `google-services.json` file to Hostinger. They belong only in the Flutter project before building the APK/AAB.

## 1. Create the Firebase apps

1. Open Firebase Console and select the City Solutions project linked to the billing-enabled Google Cloud project.
2. Add an Android app with package `com.citysolutions.app`.
3. Add another Android app with package `com.citysolutions.worker`.
4. Add these current test-signing fingerprints to both apps:
   - SHA-1: `92:34:64:45:C5:0B:6A:C4:E5:F6:49:48:B3:DB:98:54:0D:90:57:0D`
   - SHA-256: `19:2B:8B:34:35:E1:E1:E6:BF:A6:5D:7C:52:B1:34:3B:6C:16:95:6E:C7:08:30:C1:EA:71:DB:D9:9C:CA:C1:83`
5. Download each `google-services.json` and place it in the destination shown above.

These fingerprints are for the current debug/test signing key. Before Play Store release, create a private upload key and add its SHA-1/SHA-256 too. Also add the Play App Signing fingerprints after the first Play Console upload.

## 2. Enable customer authentication

In Firebase Console > Authentication:

1. Enable **Phone** sign-in.
2. Set the SMS region policy to the actual operating countries, including India.
3. Add test phone numbers and fixed OTP codes for development so tests do not consume SMS quota.
4. Enable **Google** sign-in and select the public support email.
5. Confirm both SHA fingerprints are saved, then download the customer `google-services.json` again if Firebase asks you to refresh it.

The worker app continues to use City Solutions worker credentials and roles. Firebase is used there for push, not customer Google/OTP login.

## 3. Enable push notifications

1. In Google Cloud APIs, confirm **Firebase Cloud Messaging API (HTTP v1)** is enabled.
2. In Firebase Console > Project settings > Service accounts, generate a private service-account JSON for the backend.
3. In City Solutions Admin > Settings, enter:
   - Firebase Project ID
   - Firebase Service Account JSON
   - Enable Push Notifications
   - Enable Firebase Customer Auth
   - Enable Phone OTP Login
   - Enable Google Login
4. In each module settings section, enable Firebase Push where a module-specific switch exists. Module credentials can inherit the global service account.
5. Install and open both apps once so their FCM tokens register with Hostinger.
6. Send a test notification from Admin, then test real order, booking, refund, assignment, and taxi status events.

The service-account JSON is a server credential. Keep it only in the protected Admin settings or a server secret; never put it in Flutter assets, `google-services.json`, source control, or a public download directory.

## 4. Configure the Android Maps key

In Google Cloud Console:

1. Enable **Maps SDK for Android**.
2. Create an API key named `City Solutions Android Maps`.
3. Set application restriction to **Android apps**.
4. Add both package IDs and the SHA-1 fingerprint above.
5. Restrict the key to **Maps SDK for Android**.
6. Copy `android/secrets.properties.example` to `android/secrets.properties` and set:

```properties
MAPS_API_KEY=YOUR_ANDROID_RESTRICTED_MAPS_KEY
```

`android/secrets.properties` is ignored by Git and must not be uploaded to Hostinger.

## 5. Configure Hostinger Routes and geocoding

1. Enable **Routes API** and **Geocoding API** in Google Cloud.
2. Create a separate key named `City Solutions Hostinger Routes`.
3. Restrict it to Routes API and Geocoding API.
4. Restrict it to the Hostinger server's outbound public IP when the hosting plan provides a stable IP. Never reuse the Android key here.
5. In Admin > Taxi Operations, enter it as **Google Routes server API key**.
6. Keep approximate/fallback taxi quotes disabled for production.

This server key powers route distance/duration/fare quotes and reverse geocoding. The Android key only renders maps inside the app.

## 6. Upload backend changes

Upload these paths while preserving their location under `public_html`:

```text
backend/app/Controllers/Api/DeviceTokenController.php
backend/app/Controllers/Api/FirebaseAuthController.php
backend/app/Controllers/Admin/SettingsController.php
backend/app/Support/FirebaseIdToken.php
backend/app/Support/FirebasePush.php
backend/app/Support/Settings.php
backend/public/index.php
backend/resources/views/admin/settings.php
```

If the contents of `backend/` are deployed directly into `public_html`, upload the matching top-level folders instead: `app/`, `public/`, and `resources/`.

Import once in phpMyAdmin:

```text
backend/database/firebase_auth_mysql.sql
```

The migration is repeat-safe. A fresh database should use the updated `backend/database/schema_mysql.sql` instead.

## 7. Build configured apps

```bash
flutter clean
flutter pub get
flutter build apk --release --flavor customer -t lib/main.dart
flutter build apk --release --flavor worker -t lib/main_worker.dart
```

Outputs:

```text
build/app/outputs/flutter-apk/app-customer-release.apk
build/app/outputs/flutter-apk/app-worker-release.apk
```

## 8. Production checks

- Phone OTP succeeds on a real Indian number and a Firebase test number.
- Google login creates/links one City Solutions customer, rather than a duplicate module account.
- Customer receives foreground, background, and terminated-app notifications.
- Delivery person, service provider, and taxi driver receive assignments in the worker app.
- Map renders without `REQUEST_DENIED`.
- Address selection resolves an address and zone.
- Taxi quote uses `route_source=google`, then assignment, trip OTP, tracking, completion, and cancellation are tested.
- Firebase App Check debug tokens are registered for debug builds; Play Integrity is configured before enforcing App Check in production.
- Release upload signing is configured before generating the Play Store AAB.

Firebase Cloud Functions and Firestore are not required for the current architecture. Hostinger remains the business backend and MySQL remains the source of truth; Firebase supplies authentication, push delivery, and app attestation.
