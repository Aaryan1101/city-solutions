# City Solutions Mart Roadmap

## Reference System

Reference path: `/home/potato/backup/onlinekiryana`

OnlineKiryana is split into:

- `onlinekiryana_backend`: Laravel 10 backend, web/admin panel, vendor panel, REST APIs.
- `onlinekiryana_user/User app and web`: Flutter customer app.
- `onlinekiryana_vendor/Vendor app`: Flutter vendor app.
- `onlinekiryana_delivery/Delivery Man App`: Flutter delivery app.

The backend is a full marketplace system. It includes admin, vendors, delivery men, products, categories, brands, banners, carts, coupons, orders, shipping, wallets, loyalty, chat, reports, POS, payments, refunds, notifications, and settings.

For City Solutions, the first Mart version should copy the flow and concepts, not the full complexity.

## Reference Architecture Notes

### Backend

OnlineKiryana backend uses:

- Laravel 10
- MySQL
- Blade admin panel
- REST API routes under `/api/v1`
- Admin routes under `/admin`
- Auth with Laravel Passport/Sanctum style token flows
- Uploaded images served from public/storage or public asset paths

Important reference areas:

- API routes: `onlinekiryana_backend/routes/rest_api/v1/api.php`
- Admin routes: `onlinekiryana_backend/routes/admin/routes.php`
- Product admin: `app/Http/Controllers/Admin/Product/ProductController.php`
- Category admin: `app/Http/Controllers/Admin/Product/CategoryController.php`
- Banner admin: `app/Http/Controllers/Admin/Promotion/BannerController.php`
- Order admin: `app/Http/Controllers/Admin/Order/OrderController.php`
- API product: `app/Http/Controllers/RestAPI/v1/ProductController.php`
- API cart: `app/Http/Controllers/RestAPI/v1/CartController.php`
- API order: `app/Http/Controllers/RestAPI/v1/OrderController.php`

### Mobile App

OnlineKiryana user app is feature-sliced:

- `features/home`
- `features/category`
- `features/item`
- `features/cart`
- `features/checkout`
- `features/order`
- `features/address`
- `features/auth`
- `features/profile`
- `features/banner`
- `features/coupon`

The customer flow is:

1. Load config.
2. Load banners, categories, featured/latest/best-selling products.
3. Browse products by category/search.
4. Open product details.
5. Add/update/remove cart items.
6. Select or add delivery address.
7. Place order.
8. Track order and view order history.

## Recommended City Solutions Approach

Build a slim Laravel backend for only the Mart module first.

Do not copy the entire OnlineKiryana backend unless the client explicitly needs marketplace/vendor/delivery-wallet complexity immediately. A custom slim backend will be easier to deploy on Hostinger, maintain, and connect to the existing City Solutions app.

Important: the slim backend is only the starting point. The long-term Mart module should be designed so the larger OnlineKiryana feature set can be added later without rewriting the app. Keep database names, API naming, order statuses, and module boundaries close enough to OnlineKiryana concepts that we can grow toward the full system.

There will be no public company landing page for City Solutions backend deployments. The backend web surface is panel-first:

- Admin panel
- Future vendor panel
- Future delivery panel
- API endpoints for the mobile app

The domain root should redirect to the admin login or a panel selector, not to a marketing homepage.

The backend can later become the shared backend for other modules by namespacing each module:

- `/api/v1/mart/...`
- `/api/v1/services/...`
- `/api/v1/real-estate/...`
- `/api/v1/hotels/...`
- `/api/v1/ecommerce/...`

## Mart MVP Scope

### Admin Panel

Required first:

- Admin login/logout.
- Dashboard with counts: products, orders, customers, low-stock items.
- Category CRUD.
- Product CRUD.
- Product image upload.
- Banner CRUD.
- Order list.
- Order details.
- Order status update.
- Basic settings: app name, currency, delivery charge, minimum order amount.

Useful second phase:

- Coupons.
- Stock report.
- Customer list.
- Push notification.
- Payment settings.
- Delivery assignment.
- Vendor/store separation.

### Customer App

Required first:

- Mart landing/home screen.
- Banner carousel.
- Category grid.
- Product list.
- Product details.
- Search.
- Cart.
- Checkout with customer details/address.
- Cash on delivery order placement.
- Order success screen.
- Order history and order details.

Useful second phase:

- Login/register.
- Saved addresses.
- Wishlist.
- Coupons.
- Online payment.
- Order tracking.
- Notifications.

## Database Outline

Use MySQL on Hostinger.

Core tables:

- `admins`
- `customers`
- `categories`
- `products`
- `product_images`
- `banners`
- `carts`
- `orders`
- `order_items`
- `settings`

Second phase tables:

- `coupons`
- `customer_addresses`
- `payments`
- `order_status_histories`
- `delivery_men`
- `notifications`

### Product Fields

Minimum product fields:

- `id`
- `category_id`
- `name`
- `slug`
- `description`
- `unit`
- `price`
- `discount_price`
- `stock`
- `sku`
- `thumbnail`
- `status`
- `is_featured`
- `created_at`
- `updated_at`

### Order Statuses

Start with:

- `pending`
- `confirmed`
- `processing`
- `out_for_delivery`
- `delivered`
- `cancelled`

## API Contract

Base path:

`https://your-domain.com/api/v1/mart`

Public APIs:

- `GET /config`
- `GET /banners`
- `GET /categories`
- `GET /products?offset=1&limit=20`
- `GET /products/featured`
- `GET /products/latest`
- `GET /products/search?query=milk`
- `GET /products/{id}`
- `GET /categories/{id}/products`

Cart APIs:

- `GET /cart?guest_id=...`
- `POST /cart/add`
- `PUT /cart/update`
- `DELETE /cart/remove`
- `DELETE /cart/remove-all`

Order APIs:

- `POST /orders/place`
- `GET /orders/track?order_id=...`
- `GET /orders?customer_phone=...`
- `GET /orders/{id}`

Admin APIs or web routes:

- Product/category/banner/order management should be available through Blade admin screens first.
- JSON admin APIs can be added later if we build a separate admin frontend.

## Hostinger Deployment Notes

Recommended stack:

- Laravel 10 or 11, depending on Hostinger PHP version.
- PHP 8.1+.
- MySQL database.
- Public document root should point to Laravel `public`.
- Use `.env` for database, app URL, mail, and storage config.
- Uploaded product images should live under `storage/app/public`, exposed with `php artisan storage:link`.

If Hostinger shared hosting does not allow easy queue workers, avoid queues in phase 1.

## Flutter Integration Plan

Current City Solutions app already has a `Mart` module card.

Recommended new structure:

- `lib/features/mart/data`
- `lib/features/mart/domain`
- `lib/features/mart/presentation`
- `lib/features/mart/presentation/screens`
- `lib/features/mart/presentation/widgets`

Core Flutter files to add:

- `mart_api_client.dart`
- `mart_repository.dart`
- `mart_models.dart`
- `mart_home_screen.dart`
- `mart_category_screen.dart`
- `mart_product_details_screen.dart`
- `mart_cart_screen.dart`
- `mart_checkout_screen.dart`
- `mart_order_success_screen.dart`
- `mart_order_history_screen.dart`

State management can start simple with `ChangeNotifier` or local `StatefulWidget` state. If the module grows, move to a clear controller/provider layer.

## Milestones

### Milestone 1: Backend Foundation

- Create Laravel backend project under `backend/`.
- Add admin auth.
- Add migrations/models for categories, products, banners, orders.
- Add seed admin account.
- Add basic admin dashboard.

### Milestone 2: Catalog Admin

- Category CRUD.
- Product CRUD with image upload.
- Banner CRUD.
- Product status/stock management.

### Milestone 3: Public Mart APIs

- Config API.
- Banner API.
- Category API.
- Product listing/search/details APIs.
- Standard image URL formatting.

### Milestone 4: Flutter Mart Browsing

- Replace Mart placeholder with Mart home.
- Show banners, categories, products.
- Add product details and search.

### Milestone 5: Cart and Checkout

- Local/guest cart.
- Cart API sync.
- Checkout form.
- Cash on delivery order placement.

### Milestone 6: Admin Order Management

- Admin order list/details.
- Status update.
- Order history in app.

### Milestone 7: Hostinger Deployment

- Configure `.env`.
- Upload Laravel backend.
- Configure domain/subdomain.
- Run migrations.
- Test APIs from the app.
- Build APK with production API URL.

## Future Work From OnlineKiryana

These are not phase-1 requirements, but they should remain on the roadmap so City Solutions Mart can mature into a stronger grocery/e-commerce system.

### Marketplace And Vendor Features

- Vendor registration.
- Vendor approval/rejection by admin.
- Vendor dashboard.
- Vendor product management.
- Vendor shop profile.
- Vendor order management.
- Vendor sales reports.
- Seller wallet and withdrawal requests.
- Vendor-wise coupons.
- Vendor-wise shipping settings.
- Shop followers.
- Shop vacation/temporary close.
- Multi-vendor product listing and filtering.

### Delivery Features

- Delivery man management.
- Delivery man app/API.
- Assign delivery man to order.
- Delivery status flow.
- Delivery verification code.
- Delivery man wallet.
- Cash collection from delivery man.
- Delivery man withdrawal.
- Emergency contact management.
- Delivery history.
- Delivery charge rules.
- Delivery country/zip restrictions.

### Catalog Enhancements

- Brands.
- Product attributes.
- Product variations.
- SKU combinations.
- Product stock records.
- Bulk product import/export.
- Product gallery.
- Low-stock reporting.
- Product reviews and ratings.
- Review replies.
- Product compare.
- Wishlist.
- Restock request.
- Tags.
- SEO fields for products.
- Digital products only if required later.

### Promotions And Marketing

- Flash deals.
- Featured deals.
- Deal of the day.
- Most demanded products.
- Clearance sale.
- Coupons.
- Cashback offers.
- Banners by placement.
- Push notifications.
- Notification seen/unseen tracking.
- Newsletter/subscription.
- Referral and earn.
- Loyalty points.

### Customer Account Features

- Login/register.
- OTP verification.
- Social login.
- Firebase auth.
- Forgot/reset password.
- Customer profile.
- Saved addresses.
- Wallet.
- Loyalty point history.
- Support tickets.
- Chat with admin/vendor.
- Account deletion.
- Guest cart merge after login.

### Order And Payment Features

- Online payments.
- Offline payment methods.
- Wallet payment.
- Payment gateway settings.
- Payment status management.
- Refund requests.
- Refund transactions.
- Order invoice generation.
- Order status history.
- Order cancellation reasons.
- Reorder/order again.
- Shipping methods.
- Shipping responsibility rules.
- Free delivery rules.
- Tax calculation.
- Admin commission.
- POS order flow.

### Admin And Reporting Features

- Advanced dashboard metrics.
- Order reports.
- Product reports.
- Stock reports.
- Transaction reports.
- Expense reports.
- Customer reports.
- Wishlist reports.
- Vendor sale reports.
- Inhouse sale reports.
- Employee roles and permissions.
- File manager.
- Business settings.
- Mail/SMS/payment settings.
- Firebase notification settings.
- Language/currency settings.
- SEO settings.
- Maintenance/update settings.
- Error logs.
- Database backup/settings.

### Web And Public Site Features

- Customer web storefront.
- Product detail pages.
- Category/brand pages.
- Shop pages.
- Cart and checkout on web.
- CMS pages: about, privacy, terms, refund, cancellation, shipping.
- Contact us.
- Sitemap/robots/metadata.

### Technical Growth Items

- Queue jobs if the hosting plan supports them.
- Cache strategy for catalog APIs.
- API rate limiting.
- Image optimization.
- Storage abstraction for local/S3 later.
- Admin audit logs.
- Better role permissions.
- Production logging and monitoring.
- Versioned API compatibility for old APKs.

## Long-Term Target

The final Mart module can eventually match OnlineKiryana-level capability:

- Customer app
- Admin panel
- Vendor panel/app
- Delivery app
- Public web storefront
- Full product, cart, order, promotion, payment, delivery, wallet, report, and support systems

The phase-1 backend should therefore avoid shortcuts that block multi-vendor, delivery assignment, coupons, payments, or customer accounts later.

## Key Decision

For phase 1, build City Solutions Mart as a single-store grocery/mart module. Add multi-vendor support only after the basic customer ordering flow is stable.
