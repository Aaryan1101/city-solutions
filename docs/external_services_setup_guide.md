# City Solutions External Services Setup Guide

This document lists the external services, credentials, and production accounts needed before publishing City Solutions.

## Summary: What Is Actually Required

Required for production:

1. Hostinger domain, HTTPS and MySQL.
2. Google Maps Platform API key.
3. Firebase project for push notifications.
4. Payment gateway merchant account.
5. Play Console developer account for Android publishing.
6. Legal/business details for public policies and store listing.
7. Support email/phone.
8. Production media/assets with usage rights.

Required only if enabled:

1. SMS/OTP provider.
2. SMTP/email provider.
3. WhatsApp Business API.
4. External object storage/CDN.
5. Realtime chat provider.
6. Analytics/crash reporting.
7. iOS Apple Developer account.

## 1. Hostinger, Domain, HTTPS and Database

Purpose:

- Host backend API.
- Host public website and legal pages.
- Host admin/vendor/provider/hotel-owner panels.
- Store production MySQL data.

Where used:

- `backend/.env`
- Hostinger File Manager
- phpMyAdmin

Setup:

1. Buy/attach domain in Hostinger.
2. Enable SSL/HTTPS.
3. Create MySQL database.
4. Copy `backend/.env.example` to `.env`.
5. Set:

```text
APP_NAME="City Solutions"
APP_URL=https://YOUR_DOMAIN
APP_KEY=long-random-secret
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=YOUR_HOSTINGER_DB
DB_USERNAME=YOUR_HOSTINGER_DB_USER
DB_PASSWORD=YOUR_HOSTINGER_DB_PASSWORD
ADMIN_EMAIL=real-admin-email
ADMIN_PASSWORD=strong-password
```

6. Import:

```text
backend/database/schema_mysql.sql
backend/database/demo_all_modules_mysql.sql          # only for demo/test data
backend/database/production_legal_settings_mysql.sql # production legal defaults
```

Production checks:

- `https://YOUR_DOMAIN/` opens website.
- `https://YOUR_DOMAIN/admin` opens admin.
- `https://YOUR_DOMAIN/api/v1/mart/home` returns JSON.
- Admin Health Check passes core database/file checks.

## 2. Google Maps Platform

Purpose:

- Address picker map.
- Automatic zone detection from location/address.
- Address display in app top bar.
- Geocoding/reverse geocoding if configured.

Required APIs:

- Maps SDK for Android.
- Maps SDK for iOS if publishing iOS.
- Places API if autocomplete/search is used.
- Geocoding API if backend/app converts address to coordinates.
- Maps JavaScript API only if website/admin map UI is added later.

Setup:

1. Open Google Cloud Console.
2. Create or select project.
3. Enable billing.
4. Enable required APIs:
   - Maps SDK for Android
   - Places API
   - Geocoding API
   - Maps SDK for iOS later if needed
5. Create API key.
6. Restrict key:
   - Android app restriction: package name + SHA-1 certificate fingerprint.
   - API restrictions: only Maps/Places/Geocoding required by app.
7. For debug builds, add debug SHA-1.
8. For release builds, add release signing SHA-1.

Where to put it:

- Android manifest / Flutter Google Maps config already used by the app.
- If the key is currently hardcoded locally, replace it with the production restricted key before release.

How to get SHA-1:

Debug:

```bash
cd android
./gradlew signingReport
```

Release:

Use the keystore used to sign the production APK/AAB:

```bash
keytool -list -v -keystore YOUR_RELEASE_KEYSTORE.jks -alias YOUR_ALIAS
```

Production checks:

- Address picker opens map.
- Location permission prompt appears correctly.
- Selecting address updates top bar.
- Zone is detected correctly.
- Products/hotels/services filter by selected zone.
- Key restriction does not break release build.

## 3. Firebase Push Notifications

Purpose:

- Customer order/booking status notifications.
- Vendor/provider/hotel-owner/admin notifications.
- Refund/support/payment updates.

Required Firebase services:

- Firebase Cloud Messaging.
- Android app config.
- Service account JSON for backend HTTP v1 push.

Setup:

1. Create Firebase project.
2. Add Android app with production package name.
3. Download `google-services.json`.
4. Add the file to Android app.
5. Generate service account JSON:
   - Firebase Project Settings
   - Service Accounts
   - Generate new private key
6. Open admin:

```text
/admin/settings
```

7. Fill:

```text
Firebase Push = enabled after testing
Firebase Project ID
Firebase Sender ID
Firebase App ID
Firebase Service Account JSON
Firebase Test Device Token
```

8. Save and use `Test Firebase Push`.

Production checks:

- App registers device token after login.
- Admin test push reaches device.
- Status update creates in-app notification and push.
- Logout does not keep sending private notifications to wrong device.
- Admin Health Check has no failed push jobs.

Detailed Firebase and Google Cloud instructions are in `docs/FIREBASE_GOOGLE_CLOUD_SETUP.md`.

## 4. Payment Gateway

Purpose:

- Online payments.
- Payment verification.
- Refund to original payment method.
- Reconciliation.

Recommended India gateways:

- Razorpay.
- Cashfree.
- PhonePe.
- PayU.
- CCAvenue.

Current backend supports:

- Per-module payment settings.
- Public key.
- Secret/auth header.
- Capture URL.
- Status URL.
- Refund URL.
- HMAC webhook secret.
- Webhook event deduplication.
- Admin reconciliation.

Where to configure:

```text
/admin/settings
```

Module-specific settings should be configured for:

- Mart.
- E-Commerce.
- Medical.
- Services.
- Hotels.
- Restaurants.

Webhook URLs:

```text
https://YOUR_DOMAIN/api/v1/mart/payments/webhook
https://YOUR_DOMAIN/api/v1/ecommerce/payments/webhook
https://YOUR_DOMAIN/api/v1/medical/payments/webhook
https://YOUR_DOMAIN/api/v1/services/payments/webhook
https://YOUR_DOMAIN/api/v1/hotels/payments/webhook
https://YOUR_DOMAIN/api/v1/restaurants/payments/webhook
```

Production checks:

- Test payment success.
- Test payment failure.
- Test duplicate webhook.
- Test invalid signature.
- Test refund.
- Test payment status in app.
- Test admin payment reports.

## 5. SMS / OTP Provider

Purpose:

- Phone verification.
- OTP login if you decide to enable OTP instead of password.
- Critical order/booking SMS updates if required.

Current status:

- The app currently has account/password flow.
- SMS/OTP is not mandatory unless the client wants OTP login or SMS alerts.

Possible providers:

- MSG91.
- Twilio.
- Fast2SMS.
- Textlocal.
- Gupshup.

Needed credentials:

```text
SMS_PROVIDER
SMS_API_KEY
SMS_SENDER_ID
SMS_TEMPLATE_ID_LOGIN
SMS_TEMPLATE_ID_ORDER
```

Setup plan if enabled:

1. Register provider account.
2. Complete DLT/template approval if sending SMS in India.
3. Add backend settings/env fields.
4. Add OTP generation/expiry table.
5. Add rate limits and resend limits.
6. Test with real numbers.

Production checks:

- OTP expires.
- OTP is rate-limited.
- Wrong OTP is blocked after attempts.
- SMS templates are approved.

## 6. Email / SMTP Provider

Purpose:

- Admin alerts.
- Customer invoices.
- Password reset if email reset is enabled.
- Legal/support replies.

Current status:

- Not mandatory for core app if support is in-app.
- Recommended for production admin/support operations.

Possible providers:

- Hostinger SMTP.
- Gmail Workspace SMTP.
- SendGrid.
- Mailgun.
- Amazon SES.

Needed credentials:

```text
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_FROM_ADDRESS
MAIL_FROM_NAME
MAIL_ENCRYPTION
```

Setup plan:

1. Choose provider.
2. Configure SPF/DKIM/DMARC on domain.
3. Add backend mail config.
4. Test support email and password reset/email notifications if enabled.

Production checks:

- Emails do not land in spam.
- SPF/DKIM pass.
- Support mailbox is monitored.

## 7. WhatsApp Business API

Purpose:

- Optional order/booking notifications.
- Optional support/chat escalation.
- Optional vendor/customer communication.

Current status:

- Not required.
- Current support/chat can operate without WhatsApp.

Possible providers:

- Meta WhatsApp Cloud API.
- Gupshup.
- Interakt.
- WATI.

Needed credentials:

```text
WHATSAPP_PROVIDER
WHATSAPP_ACCESS_TOKEN
WHATSAPP_PHONE_NUMBER_ID
WHATSAPP_BUSINESS_ACCOUNT_ID
WHATSAPP_TEMPLATE_IDS
```

Production checks:

- Templates approved.
- Opt-in captured.
- No private order details sent to wrong number.

## 8. Storage / CDN

Purpose:

- Product images.
- Banners.
- Hotel room images.
- Service media.
- Restaurant media.
- Prescription uploads.
- Floating ads.

Current status:

- Hostinger local `public/uploads` works.
- External object storage is optional but recommended when traffic/media grows.

Options:

- Keep Hostinger local uploads initially.
- Cloudflare R2.
- AWS S3.
- Wasabi.
- DigitalOcean Spaces.

Needed credentials if external storage is enabled:

```text
STORAGE_DRIVER=s3
S3_ENDPOINT
S3_BUCKET
S3_ACCESS_KEY
S3_SECRET_KEY
S3_REGION
S3_PUBLIC_BASE_URL
```

Production checks:

- Upload validation works.
- Prescription uploads are not publicly browsable unless intended.
- Large ads/videos load reliably.
- CDN cache does not serve deleted restricted files.

## 9. App Store Accounts

Android:

- Google Play Console developer account.
- App signing key.
- Package name finalization.
- Store listing assets.
- Privacy policy URL.
- Account deletion URL.
- Data safety form.

iOS if required:

- Apple Developer account.
- Bundle ID.
- Apple signing certificates/profiles.
- App Privacy answers.
- Privacy policy URL.

Required public URLs:

```text
https://YOUR_DOMAIN/privacy-policy
https://YOUR_DOMAIN/terms
https://YOUR_DOMAIN/refund-cancellation
https://YOUR_DOMAIN/medical-compliance
https://YOUR_DOMAIN/account-deletion
```

Production checks:

- Release APK/AAB is signed with production key.
- Google Maps API key includes release SHA-1.
- Privacy policy and account deletion pages are public.
- App name, logo, screenshots and descriptions are final.

## 10. Legal / Business / Compliance Details

Purpose:

- Public website legal pages.
- Payment gateway KYC.
- Play Store Data Safety.
- Medical module compliance.

Needed from client/operator:

```text
Registered business/legal name
Business address
Support email
Support phone
GST/CIN if applicable
Pharmacy/license details if Medical goes live
Refund/cancellation policy approval
Privacy/contact/grievance contact
Bank account/KYC for payment gateway
```

Where configured:

```text
/admin/settings
```

Production checks:

- Legal pages show real legal entity, not placeholder text.
- Medical module has licensed pharmacy/operator review before public launch.
- Payment gateway KYC approved.

## 11. Analytics and Crash Reporting

Purpose:

- Crash visibility.
- Usage funnels.
- Release stability.

Current status:

- Optional but recommended.

Options:

- Firebase Crashlytics.
- Firebase Analytics.
- Sentry.

Needed credentials:

- Firebase project already covers Analytics/Crashlytics.
- Sentry DSN if Sentry is chosen.

Production checks:

- Test crash captured.
- Release version tags are correct.
- No sensitive prescription/payment data is logged.

## 12. Realtime Chat Provider

Purpose:

- True instant customer/admin/vendor/provider chat.

Current status:

- Current support system can operate with polling.
- Realtime chat is optional unless the client requires instant messaging.

Options:

- Keep polling.
- Firebase Firestore.
- Pusher.
- Ably.
- Socket.IO server.

Recommendation:

- Keep polling for launch unless chat volume is high.
- Upgrade to managed realtime later to avoid operating WebSocket infrastructure on basic Hostinger.

## 13. Image / Media Licensing

Purpose:

- Prevent copyright issues in app and public website.

Needed:

- Licensed product images.
- Licensed hotel/restaurant/service photos.
- Brand/vendor/store logos with permission.
- Ad media with permission.

Production checks:

- No scraped Amazon/Flipkart images.
- No unlicensed copyrighted media.
- Keep source/license records for all public assets.

## Final “Ready To Publish” Checklist

Backend:

- Hostinger `.env` production configured.
- MySQL schema imported.
- Admin password changed.
- HTTPS working.
- Health Check clean.

App:

- Release signing configured.
- Google Maps production key restricted.
- Firebase config installed.
- Final app icon/name/package.
- APK/AAB tested on real device.

Admin:

- Business/legal details filled.
- Module policies reviewed.
- Zones configured.
- Vendors/providers/hotels/restaurants approved.
- Payment settings configured or online payment disabled.
- Firebase tested or push disabled.

External services:

- Google Maps billing enabled.
- Firebase service account saved.
- Payment gateway KYC approved.
- Support email/phone active.
- Legal URLs public.

If any required credential is missing, disable that feature in admin before publishing.
