# Firebase Push and Payment Gateway Setup Guide

This guide is for the City Solutions backend hosted on Hostinger and the Flutter app. The app is functionally usable without Firebase and online gateway credentials, but production launch should not enable push notifications or online payments until the checks below pass.

## Current Production Status

Core modules are complete enough for end-to-end testing and controlled production rollout, excluding live Firebase credentials and live online payment gateway credentials.

Working areas:

- Customer auth, account, address, zone flow, wallet, support and complaints.
- Mart, E-Commerce and Medical catalog, cart, checkout, order history, refunds and admin/vendor controls.
- Services booking, reschedule/cancel request, provider/admin flow and reports.
- Hotel booking, cancellation/refund policy, owner/admin flow and reports.
- Restaurant table booking/waitlist and admin flow.
- Real Estate listing, inquiry, site visit and saved activity flows.
- Zone filtering, role permissions, media ads, legal pages and account-level policy access.
- COD/manual/wallet payment paths.

Still external before final production:

- Real Firebase project credentials and device-token validation.
- Real payment gateway account, hosted checkout/SDK decision, webhook mapping and refund reconciliation.
- Final legal/pharmacy/payment review by the client/operator.

## Firebase Push Setup

### 1. Create Firebase project

1. Open Firebase Console.
2. Create a project for City Solutions.
3. Add Android app package name used by the release build.
4. Download `google-services.json`.
5. Add iOS app later if publishing iOS.

### 2. Configure Flutter app

The backend already exposes Firebase config and stores push tokens. Before release:

1. Put `google-services.json` in the Android app path required by Flutter/Firebase.
2. Confirm the app requests notification permission on Android 13+ and iOS.
3. Confirm the app registers device tokens for the correct owner/module.
4. Build release APK and verify a real device token reaches backend `device_tokens`.

### 3. Configure backend admin

Open:

```text
/admin/settings
```

Use Global settings for shared credentials or module settings for module-specific projects.

Recommended production setup:

- `Firebase Push`: enabled only after test succeeds.
- `Firebase Project ID`: Firebase project id.
- `Firebase Sender ID`: Firebase messaging sender id.
- `Firebase App ID`: Firebase app id.
- `Firebase Service Account JSON`: preferred production credential.
- `Firebase Server Key`: only use if legacy FCM is required.
- `Firebase Test Device Token`: paste a token from a real installed app.

The backend prefers service account JSON for FCM HTTP v1. Legacy server key is fallback.

### 4. Generate service account JSON

In Firebase Console:

1. Project Settings.
2. Service Accounts.
3. Generate new private key.
4. Copy the full JSON into admin `Firebase Service Account JSON`.
5. Save settings.

Do not commit this JSON to Git.

### 5. Test Firebase from admin

In:

```text
/admin/settings
```

Use `Test Firebase Push`.

Test modules separately:

- Global
- Mart
- E-Commerce
- Medical
- Services
- Hotels
- Restaurants
- Real Estate

Expected result:

- Admin page says Firebase accepted the notification.
- Real device receives notification.
- Failed push rows do not accumulate in health check.

### 6. Firebase operational checks

Before production:

- Run Admin Health Check.
- Confirm Hostinger PHP has `openssl` enabled.
- Confirm Hostinger PHP has `curl` enabled.
- Confirm app stores device tokens after login.
- Confirm notification permission UX is clear.
- Confirm logout does not keep sending private notifications to wrong user.
- Confirm failed outbox entries are reviewed.

## Payment Gateway Setup

The backend is gateway-flexible. It does not hardcode one provider. Admin can configure payment per module.

Supported admin fields:

- Gateway preset.
- Public key.
- Secret key.
- Webhook secret.
- Merchant/account id.
- Environment: test/live.
- Capture URL.
- Status URL.
- Refund URL.
- Auth header.
- Customer-facing instructions.

### 1. Choose gateway

Recommended India options:

- Razorpay
- Cashfree
- PhonePe
- PayU
- CCAvenue

Pick one first. Do not configure multiple providers until one is stable.

### 2. Decide checkout mode

There are two possible models.

#### Model A: Hosted checkout or app SDK

Best UX, recommended for production.

Flow:

1. App creates order/booking in City Solutions.
2. Backend/gateway creates payment session/order.
3. App opens gateway checkout.
4. Gateway sends webhook to City Solutions.
5. Backend marks payment paid/failed/refunded.
6. App shows final status from backend.

This may require adding the selected gateway SDK/session creation adapter if the provider cannot work with the current generic URLs.

#### Model B: Manual online reference

Already supported.

Flow:

1. User pays through configured external route.
2. User enters reference.
3. Admin verifies or gateway status URL verifies.
4. Backend reconciles payment.

This is acceptable for early operation, but weaker than hosted checkout.

### 3. Configure admin payment settings

Open:

```text
/admin/settings
```

Set global payment settings or module-specific payment settings.

For each live module:

- Enable `Online Payment`.
- Select gateway.
- Set `Environment = test` first.
- Enter public key if app-side checkout needs it.
- Enter secret key or auth header.
- Enter webhook secret.
- Enter merchant/account id.
- Enter capture/status/refund URLs if using generic API calls.
- Save.

Recommended: configure module-specific settings for:

- `mart`
- `ecommerce`
- `medical`
- `services`
- `hotel`
- `restaurant`

Real Estate usually does not need online payment unless paid visits/listings are enabled.

### 4. Webhook endpoints

Use these public URLs on the gateway dashboard:

```text
https://YOUR_DOMAIN/api/v1/mart/payments/webhook
https://YOUR_DOMAIN/api/v1/ecommerce/payments/webhook
https://YOUR_DOMAIN/api/v1/medical/payments/webhook
https://YOUR_DOMAIN/api/v1/services/payments/webhook
https://YOUR_DOMAIN/api/v1/hotels/payments/webhook
https://YOUR_DOMAIN/api/v1/restaurants/payments/webhook
```

Webhook must be `POST` JSON.

The backend accepts signatures in either header:

```text
X-City-Signature
X-Webhook-Signature
```

Signature format:

```text
sha256=HMAC_SHA256_RAW_JSON_BODY_USING_WEBHOOK_SECRET
```

The `sha256=` prefix is optional.

Payload size limit is 1 MB.

### 5. Webhook payload format

For Mart, E-Commerce, Medical:

```json
{
  "event_id": "gateway-event-123",
  "order_id": 123,
  "order_number": "CSM...",
  "reference": "pay_123",
  "status": "paid"
}
```

Either `order_id` or `order_number` is required.

For Services, Hotels, Restaurants:

```json
{
  "event_id": "gateway-event-123",
  "booking_id": 123,
  "booking_number": "CSV...",
  "reference": "pay_123",
  "status": "paid"
}
```

Either `booking_id` or `booking_number` is required.

Accepted successful/refund/failure status values are normalized by backend. Use:

```text
paid
refunded
failed
```

Gateway-specific values should be mapped by adapter or webhook transformer before posting.

### 6. Reconciliation behavior

When webhook is valid:

- Order/booking payment status is updated.
- Payment transaction row is updated.
- Gateway response is stored.
- Reconciled time and reconciled by `webhook` are stored.
- Customer notification is logged.
- Duplicate events are ignored using `payment_webhook_events`.

Failed webhook events are tracked and should be checked in Admin Health Check.

### 7. Refund setup

For online refunds:

1. Configure `Gateway Refund URL`.
2. Configure secret/auth header.
3. Test refund on test gateway.
4. Approve refund from admin.
5. Verify gateway refund response.
6. Verify app shows refunded state.
7. Verify wallet credit/original payment logic matches client policy.

If gateway refund URL is missing, keep online refunds manual/admin-reviewed.

### 8. Required production tests

Run these before enabling live payments:

1. Mart online payment success.
2. E-Commerce online payment success.
3. Medical online payment success after prescription approval.
4. Service booking payment success.
5. Hotel booking payment success.
6. Restaurant booking payment success.
7. Payment failed webhook.
8. Duplicate webhook event.
9. Invalid webhook signature.
10. Refund approved and reconciled.
11. Payment status visible in app.
12. Admin Health Check shows no payment credential or webhook failures.

### 9. Hostinger checks

Required PHP extensions:

- `curl`
- `openssl`
- `pdo_mysql`
- `json`

Required server behavior:

- HTTPS enabled.
- Webhook URLs publicly reachable.
- Hostinger firewall does not block gateway IPs.
- Correct `.htaccess` routing remains active.

### 10. Launch recommendation

Launch order:

1. Keep COD/manual/wallet live.
2. Enable Firebase test notifications.
3. Enable online payment in test mode for one module.
4. Complete gateway test matrix.
5. Enable live payment for Mart/E-Commerce first.
6. Enable Medical only after prescription/payment/refund tests pass.
7. Enable Services/Hotel/Restaurant payments after booking refund tests pass.

## Completion Report Excluding Firebase and Payment

The app and backend are substantially complete for operational testing and controlled launch without live Firebase and online payment.

Not counted as complete until credentials are installed:

- Real push delivery to production devices.
- Gateway hosted checkout or SDK integration, if selected provider requires it.
- Gateway capture/status/refund API mapping.
- Live webhook validation from the selected gateway.
- Real refund-to-original-payment reconciliation.

Everything else should be treated as ready for final client testing, not blindly published without a final smoke test on Hostinger.
