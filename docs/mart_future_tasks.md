# Mart Module Future Tasks

Delivery-man system is intentionally deferred for now.

## Remaining Production Work

- Native file upload manager for support attachments, product files, banners, and documents with MIME validation, size limits, storage cleanup, and secure download URLs.
- Full payment gateway capture flow for selected provider such as Razorpay, Stripe, or PayU, including checkout SDK integration, webhook verification, failed-payment recovery, and automatic refund sync.
- WebSocket or managed realtime chat service if true instant chat is required beyond the current polling-based support system.
- Firebase production device-token registration in the Flutter app, customer/vendor/admin targeting, topic subscriptions, and notification permission UX.
- Inventory depth: stock movement ledger, supplier/purchase entries, damaged/returned stock adjustment, and low-stock notifications per vendor.
- POS maturity if needed: barcode scanning, receipt printing, cashier sessions, offline mode, and inventory sync.
- Full role/permission system for admin staff accounts.
- Scheduled jobs or queue worker for retries, push notifications, cleanup, reports, and webhook processing.
- Backup/restore process for database and uploaded media.
- Production observability: admin-visible error log viewer, audit log search, and uptime/health checks.

## Cross-Module Work

- Unified customer cart overview is implemented in the app header. It shows active Mart, E-Commerce, and Medical cart items in one place while keeping each module's cart/order tables independent internally.
- E-Commerce now has independent app/API/admin entry points and module-scoped catalog, cart, order, refund, wishlist, review, and report data using `module_key = ecommerce`. Keep future E-Commerce work module-scoped; do not reuse Mart records directly.
- The app My Account and Orders tab now expose separate Mart and E-Commerce order/refund/wishlist flows. Preserve the API base URL when adding new shared commerce screens.
- Services now has its own service categories, providers, provider login/dashboard, services, service add-ons, reusable provider slots, blackout/holiday dates, slot-capacity booking validation, booking reschedule limits, bookings, cancellation flow, provider-side booking progress updates, admin status notes, summary counters, provider commission and settlement workflow basics, booking/settlement CSV exports, and inline admin update/archive controls for services and service categories. Next service-specific depth should include provider notification automation and advanced recurring/partial-day blackout rules.
- Admin navigation is grouped module-wise with collapsible sidebar sections and independent sidebar scrolling. New module screens should stay under their own sidebar section and should not add shared hidden coupling unless there is an explicit shared-platform reason.
