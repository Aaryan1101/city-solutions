<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\PublicController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\Admin\BannerController;
use App\Controllers\Admin\BrandController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\SubcategoryController;
use App\Controllers\Admin\ComplaintController as AdminComplaintController;
use App\Controllers\Admin\CouponController;
use App\Controllers\Admin\DeliveryManController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\FloatingAdController;
use App\Controllers\Admin\HealthController;
use App\Controllers\Admin\Ecommerce\BannerController as EcommerceAdminBannerController;
use App\Controllers\Admin\Ecommerce\BrandController as EcommerceAdminBrandController;
use App\Controllers\Admin\Ecommerce\CategoryController as EcommerceAdminCategoryController;
use App\Controllers\Admin\Ecommerce\CouponController as EcommerceAdminCouponController;
use App\Controllers\Admin\Ecommerce\OrderController as EcommerceAdminOrderController;
use App\Controllers\Admin\Ecommerce\PaymentController as EcommerceAdminPaymentController;
use App\Controllers\Admin\Ecommerce\PrescriptionController as MedicalPrescriptionController;
use App\Controllers\Admin\Ecommerce\ProductController as EcommerceAdminProductController;
use App\Controllers\Admin\Ecommerce\RefundController as EcommerceAdminRefundController;
use App\Controllers\Admin\Ecommerce\ReportController as EcommerceAdminReportController;
use App\Controllers\Admin\Ecommerce\ReviewController as EcommerceAdminReviewController;
use App\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Controllers\Admin\PosController;
use App\Controllers\Admin\ProductController;
use App\Controllers\Admin\RealEstateController as AdminRealEstateController;
use App\Controllers\Admin\RestaurantController as AdminRestaurantController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\RefundController as AdminRefundController;
use App\Controllers\Admin\ReviewController as AdminReviewController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\ShippingController;
use App\Controllers\Admin\TaxiController as AdminTaxiController;
use App\Controllers\Admin\HotelController as AdminHotelController;
use App\Controllers\Admin\ServiceController as AdminServiceController;
use App\Controllers\Admin\SupportController as AdminSupportController;
use App\Controllers\Admin\VendorController as AdminVendorController;
use App\Controllers\Admin\WalletController as AdminWalletController;
use App\Controllers\Admin\ZoneController as AdminZoneController;
use App\Controllers\HotelOwner\AuthController as HotelOwnerAuthController;
use App\Controllers\HotelOwner\DashboardController as HotelOwnerDashboardController;
use App\Controllers\RealEstateAgent\AuthController as RealEstateAgentAuthController;
use App\Controllers\RealEstateAgent\DashboardController as RealEstateAgentDashboardController;
use App\Controllers\Api\CartController;
use App\Controllers\Api\AppConfigController;
use App\Controllers\Api\ComplaintController;
use App\Controllers\Api\CustomerController;
use App\Controllers\Api\DeviceTokenController;
use App\Controllers\Api\EcommerceController;
use App\Controllers\Api\FirebaseAuthController;
use App\Controllers\Api\Ecommerce\CartController as EcommerceCartController;
use App\Controllers\Api\Ecommerce\CustomerController as EcommerceCustomerController;
use App\Controllers\Api\Ecommerce\NotificationController as EcommerceNotificationController;
use App\Controllers\Api\Ecommerce\OrderController as EcommerceOrderController;
use App\Controllers\Api\Ecommerce\RefundController as EcommerceRefundController;
use App\Controllers\Api\Ecommerce\ReviewController as EcommerceReviewController;
use App\Controllers\Api\Ecommerce\SupportController as EcommerceSupportController;
use App\Controllers\Api\Ecommerce\VendorController as EcommerceVendorController;
use App\Controllers\Api\Ecommerce\WalletController as EcommerceWalletController;
use App\Controllers\Api\Ecommerce\WishlistController as EcommerceWishlistController;
use App\Controllers\Api\HotelController;
use App\Controllers\Api\MartController;
use App\Controllers\Api\MedicalController;
use App\Controllers\Api\NotificationController;
use App\Controllers\Api\OrderController;
use App\Controllers\Api\PaymentWebhookController;
use App\Controllers\Api\RefundController;
use App\Controllers\Api\RealEstateController;
use App\Controllers\Api\RestaurantController;
use App\Controllers\Api\ReviewController;
use App\Controllers\Api\ServiceController;
use App\Controllers\Api\ServicesSupportController;
use App\Controllers\Api\SupportController as ApiSupportController;
use App\Controllers\Api\TaxiController;
use App\Controllers\Api\VendorController;
use App\Controllers\Api\WalletController as ApiWalletController;
use App\Controllers\Api\WorkerController;
use App\Controllers\Api\WishlistController;
use App\Controllers\Api\ZoneController as ApiZoneController;
use App\Controllers\ServiceProvider\AuthController as ServiceProviderAuthController;
use App\Controllers\ServiceProvider\DashboardController as ServiceProviderDashboardController;
use App\Controllers\Vendor\AuthController as VendorAuthController;
use App\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Controllers\Vendor\NotificationController as VendorNotificationController;
use App\Controllers\Vendor\OrderController as VendorOrderController;
use App\Controllers\Vendor\ProductController as VendorProductController;
use App\Controllers\Vendor\RefundController as VendorRefundController;
use App\Controllers\Vendor\ReportController as VendorReportController;
use App\Controllers\Vendor\ReviewController as VendorReviewController;
use App\Controllers\Vendor\SupportController as VendorSupportController;
use App\Controllers\Vendor\WalletController as VendorWalletController;
use App\Support\ErrorLogger;
use App\Support\Auth;
use App\Support\Env;
use App\Support\FirebasePush;
use App\Support\RateLimiter;
use App\Support\Response;
use App\Support\Security;

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = dirname(__DIR__) . '/app/' . $relative . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';
Security::sendHeaders($path);

if (Security::isHttps()) {
    ini_set('session.cookie_secure', '1');
}
session_start();

try {
    RateLimiter::enforceApiWriteLimit();
    Auth::enforcePanelSecurity($path, $method);
    match (true) {
        $path === '/' && $method === 'GET' => (new PublicController())->page('home'),
        $path === '/about' && $method === 'GET' => (new PublicController())->page('about'),
        $path === '/trust-safety' && $method === 'GET' => (new PublicController())->page('trust-safety'),
        $path === '/privacy-policy' && $method === 'GET' => (new PublicController())->page('privacy-policy'),
        $path === '/terms' && $method === 'GET' => (new PublicController())->page('terms'),
        $path === '/refund-cancellation' && $method === 'GET' => (new PublicController())->page('refund-cancellation'),
        $path === '/medical-compliance' && $method === 'GET' => (new PublicController())->page('medical-compliance'),
        $path === '/account-deletion' && $method === 'GET' => (new PublicController())->page('account-deletion'),
        $path === '/contact' && $method === 'GET' => (new PublicController())->page('contact'),
        $path === '/admin/login' && $method === 'GET' => (new AuthController())->showLogin(),
        $path === '/admin/login' && $method === 'POST' => (new AuthController())->login(),
        $path === '/admin/logout' => (new AuthController())->logout(),
        $path === '/admin' => (new DashboardController())->index(),
        $path === '/admin/health' && $method === 'GET' => (new HealthController())->index(),
        $path === '/admin/admin-users' && $method === 'GET' => (new AdminUserController())->index(),
        $path === '/admin/admin-users' && $method === 'POST' => (new AdminUserController())->store(),
        preg_match('#^/admin/admin-users/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminUserController())->update((int) $m[1]),
        preg_match('#^/admin/admin-users/(\d+)/delete$#', $path, $m) === 1 && $method === 'POST' => (new AdminUserController())->delete((int) $m[1]),
        $path === '/admin/floating-ads' && $method === 'GET' => (new FloatingAdController())->index(),
        $path === '/admin/floating-ads' && $method === 'POST' => (new FloatingAdController())->store(),
        preg_match('#^/admin/floating-ads/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new FloatingAdController())->update((int) $m[1]),
        preg_match('#^/admin/floating-ads/(\d+)/delete$#', $path, $m) === 1 => (new FloatingAdController())->delete((int) $m[1]),
        $path === '/admin/zones' && $method === 'GET' => (new AdminZoneController())->index(),
        $path === '/admin/zones' && $method === 'POST' => (new AdminZoneController())->store(),
        preg_match('#^/admin/zones/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminZoneController())->update((int) $m[1]),
        preg_match('#^/admin/zones/(\d+)/delete$#', $path, $m) === 1 => (new AdminZoneController())->delete((int) $m[1]),
        $path === '/admin/taxi' && $method === 'GET' => (new AdminTaxiController())->index(),
        $path === '/admin/taxi/settings' && $method === 'POST' => (new AdminTaxiController())->updateSettings(),
        $path === '/admin/taxi/vehicle-types' && $method === 'POST' => (new AdminTaxiController())->storeVehicleType(),
        preg_match('#^/admin/taxi/vehicle-types/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminTaxiController())->updateVehicleType((int) $m[1]),
        $path === '/admin/taxi/drivers' && $method === 'POST' => (new AdminTaxiController())->storeDriver(),
        preg_match('#^/admin/taxi/drivers/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminTaxiController())->updateDriver((int) $m[1]),
        preg_match('#^/admin/taxi/rides/(\d+)/assign$#', $path, $m) === 1 && $method === 'POST' => (new AdminTaxiController())->assignRide((int) $m[1]),
        preg_match('#^/admin/taxi/rides/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminTaxiController())->rideStatus((int) $m[1]),
        $path === '/admin/categories' && $method === 'GET' => (new CategoryController())->index(),
        $path === '/admin/categories' && $method === 'POST' => (new CategoryController())->store(),
        preg_match('#^/admin/categories/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new CategoryController())->edit((int) $m[1]),
        preg_match('#^/admin/categories/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new CategoryController())->update((int) $m[1]),
        preg_match('#^/admin/categories/(\d+)/delete$#', $path, $m) === 1 => (new CategoryController())->delete((int) $m[1]),
        $path === '/admin/subcategories' && $method === 'GET' => (new SubcategoryController())->index(),
        $path === '/admin/subcategories' && $method === 'POST' => (new SubcategoryController())->store(),
        preg_match('#^/admin/subcategories/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new SubcategoryController())->edit((int) $m[1]),
        preg_match('#^/admin/subcategories/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new SubcategoryController())->update((int) $m[1]),
        preg_match('#^/admin/subcategories/(\d+)/delete$#', $path, $m) === 1 => (new SubcategoryController())->delete((int) $m[1]),
        $path === '/admin/products' && $method === 'GET' => (new ProductController())->index(),
        $path === '/admin/products' && $method === 'POST' => (new ProductController())->store(),
        preg_match('#^/admin/products/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new ProductController())->edit((int) $m[1]),
        preg_match('#^/admin/products/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new ProductController())->update((int) $m[1]),
        preg_match('#^/admin/products/(\d+)/delete$#', $path, $m) === 1 => (new ProductController())->delete((int) $m[1]),
        $path === '/admin/ecommerce/categories' && $method === 'GET' => (new EcommerceAdminCategoryController())->index(),
        $path === '/admin/ecommerce/categories' && $method === 'POST' => (new EcommerceAdminCategoryController())->store(),
        preg_match('#^/admin/ecommerce/categories/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceAdminCategoryController())->edit((int) $m[1]),
        preg_match('#^/admin/ecommerce/categories/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminCategoryController())->update((int) $m[1]),
        preg_match('#^/admin/ecommerce/categories/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminCategoryController())->delete((int) $m[1]),
        $path === '/admin/ecommerce/subcategories' && $method === 'GET' => (new SubcategoryController('ecommerce', '/admin/ecommerce/subcategories', 'E-Commerce'))->index(),
        $path === '/admin/ecommerce/subcategories' && $method === 'POST' => (new SubcategoryController('ecommerce', '/admin/ecommerce/subcategories', 'E-Commerce'))->store(),
        preg_match('#^/admin/ecommerce/subcategories/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new SubcategoryController('ecommerce', '/admin/ecommerce/subcategories', 'E-Commerce'))->edit((int) $m[1]),
        preg_match('#^/admin/ecommerce/subcategories/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new SubcategoryController('ecommerce', '/admin/ecommerce/subcategories', 'E-Commerce'))->update((int) $m[1]),
        preg_match('#^/admin/ecommerce/subcategories/(\d+)/delete$#', $path, $m) === 1 => (new SubcategoryController('ecommerce', '/admin/ecommerce/subcategories', 'E-Commerce'))->delete((int) $m[1]),
        $path === '/admin/ecommerce/brands' && $method === 'GET' => (new EcommerceAdminBrandController())->index(),
        $path === '/admin/ecommerce/brands' && $method === 'POST' => (new EcommerceAdminBrandController())->store(),
        preg_match('#^/admin/ecommerce/brands/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceAdminBrandController())->edit((int) $m[1]),
        preg_match('#^/admin/ecommerce/brands/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminBrandController())->update((int) $m[1]),
        preg_match('#^/admin/ecommerce/brands/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminBrandController())->delete((int) $m[1]),
        $path === '/admin/ecommerce/products' && $method === 'GET' => (new EcommerceAdminProductController())->index(),
        $path === '/admin/ecommerce/products' && $method === 'POST' => (new EcommerceAdminProductController())->store(),
        preg_match('#^/admin/ecommerce/products/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceAdminProductController())->edit((int) $m[1]),
        preg_match('#^/admin/ecommerce/products/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminProductController())->update((int) $m[1]),
        preg_match('#^/admin/ecommerce/products/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminProductController())->delete((int) $m[1]),
        $path === '/admin/ecommerce/orders' => (new EcommerceAdminOrderController())->index(),
        preg_match('#^/admin/ecommerce/orders/(\d+)$#', $path, $m) === 1 => (new EcommerceAdminOrderController())->show((int) $m[1]),
        preg_match('#^/admin/ecommerce/orders/(\d+)/invoice$#', $path, $m) === 1 => (new EcommerceAdminOrderController())->invoice((int) $m[1]),
        preg_match('#^/admin/ecommerce/orders/(\d+)/status$#', $path, $m) === 1 => (new EcommerceAdminOrderController())->status((int) $m[1]),
        preg_match('#^/admin/ecommerce/orders/(\d+)/delivery$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminOrderController())->assignDelivery((int) $m[1]),
        preg_match('#^/admin/ecommerce/orders/(\d+)/tracking$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminOrderController())->tracking((int) $m[1]),
        $path === '/admin/ecommerce/banners' && $method === 'GET' => (new EcommerceAdminBannerController())->index(),
        $path === '/admin/ecommerce/banners' && $method === 'POST' => (new EcommerceAdminBannerController())->store(),
        preg_match('#^/admin/ecommerce/banners/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceAdminBannerController())->edit((int) $m[1]),
        preg_match('#^/admin/ecommerce/banners/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminBannerController())->update((int) $m[1]),
        preg_match('#^/admin/ecommerce/banners/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminBannerController())->delete((int) $m[1]),
        $path === '/admin/ecommerce/coupons' && $method === 'GET' => (new EcommerceAdminCouponController())->index(),
        $path === '/admin/ecommerce/coupons' && $method === 'POST' => (new EcommerceAdminCouponController())->store(),
        preg_match('#^/admin/ecommerce/coupons/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminCouponController())->update((int) $m[1]),
        preg_match('#^/admin/ecommerce/coupons/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminCouponController())->delete((int) $m[1]),
        $path === '/admin/ecommerce/reviews' && $method === 'GET' => (new EcommerceAdminReviewController())->index(),
        preg_match('#^/admin/ecommerce/reviews/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminReviewController())->status((int) $m[1]),
        $path === '/admin/ecommerce/refunds' && $method === 'GET' => (new EcommerceAdminRefundController())->index(),
        preg_match('#^/admin/ecommerce/refunds/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminRefundController())->status((int) $m[1]),
        $path === '/admin/ecommerce/payments' && $method === 'GET' => (new EcommerceAdminPaymentController())->index(),
        preg_match('#^/admin/ecommerce/payments/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminPaymentController())->status((int) $m[1]),
        $path === '/admin/banners' && $method === 'GET' => (new BannerController())->index(),
        $path === '/admin/banners' && $method === 'POST' => (new BannerController())->store(),
        preg_match('#^/admin/banners/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new BannerController())->edit((int) $m[1]),
        preg_match('#^/admin/banners/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new BannerController())->update((int) $m[1]),
        preg_match('#^/admin/banners/(\d+)/delete$#', $path, $m) === 1 => (new BannerController())->delete((int) $m[1]),
        $path === '/admin/brands' && $method === 'GET' => (new BrandController())->index(),
        $path === '/admin/brands' && $method === 'POST' => (new BrandController())->store(),
        preg_match('#^/admin/brands/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new BrandController())->edit((int) $m[1]),
        preg_match('#^/admin/brands/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new BrandController())->update((int) $m[1]),
        preg_match('#^/admin/brands/(\d+)/delete$#', $path, $m) === 1 => (new BrandController())->delete((int) $m[1]),
        $path === '/admin/coupons' && $method === 'GET' => (new CouponController())->index(),
        $path === '/admin/coupons' && $method === 'POST' => (new CouponController())->store(),
        preg_match('#^/admin/coupons/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new CouponController())->update((int) $m[1]),
        preg_match('#^/admin/coupons/(\d+)/delete$#', $path, $m) === 1 => (new CouponController())->delete((int) $m[1]),
        $path === '/admin/orders' => (new AdminOrderController())->index(),
        preg_match('#^/admin/orders/(\d+)$#', $path, $m) === 1 => (new AdminOrderController())->show((int) $m[1]),
        preg_match('#^/admin/orders/(\d+)/invoice$#', $path, $m) === 1 => (new AdminOrderController())->invoice((int) $m[1]),
        preg_match('#^/admin/orders/(\d+)/status$#', $path, $m) === 1 => (new AdminOrderController())->status((int) $m[1]),
        preg_match('#^/admin/orders/(\d+)/delivery$#', $path, $m) === 1 && $method === 'POST' => (new AdminOrderController())->assignDelivery((int) $m[1]),
        preg_match('#^/admin/orders/(\d+)/tracking$#', $path, $m) === 1 && $method === 'POST' => (new AdminOrderController())->tracking((int) $m[1]),
        $path === '/admin/notifications' && $method === 'GET' => (new AdminNotificationController())->index(),
        $path === '/admin/notifications/read' && $method === 'POST' => (new AdminNotificationController())->markRead(),
        $path === '/admin/reviews' && $method === 'GET' => (new AdminReviewController())->index(),
        preg_match('#^/admin/reviews/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminReviewController())->status((int) $m[1]),
        $path === '/admin/refunds' && $method === 'GET' => (new AdminRefundController())->index(),
        preg_match('#^/admin/refunds/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminRefundController())->status((int) $m[1]),
        $path === '/admin/payments' && $method === 'GET' => (new AdminPaymentController())->index(),
        preg_match('#^/admin/payments/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminPaymentController())->status((int) $m[1]),
        $path === '/admin/pos' && $method === 'GET' => (new PosController())->index(),
        $path === '/admin/pos' && $method === 'POST' => (new PosController())->store(),
        $path === '/admin/wallets' && $method === 'GET' => (new AdminWalletController())->index(),
        preg_match('#^/admin/wallets/withdrawals/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminWalletController())->withdrawalStatus((int) $m[1]),
        $path === '/admin/reports' => (new ReportController())->index(),
        $path === '/admin/reports/export/sales' => (new ReportController())->exportSales(),
        $path === '/admin/reports/export/vendors' => (new ReportController())->exportVendors(),
        $path === '/admin/reports/export/low-stock' => (new ReportController())->exportLowStock(),
        $path === '/admin/ecommerce/reports' => (new EcommerceAdminReportController())->index(),
        $path === '/admin/ecommerce/reports/export/sales' => (new EcommerceAdminReportController())->exportSales(),
        $path === '/admin/ecommerce/reports/export/vendors' => (new EcommerceAdminReportController())->exportVendors(),
        $path === '/admin/ecommerce/reports/export/low-stock' => (new EcommerceAdminReportController())->exportLowStock(),
        $path === '/admin/medical/categories' && $method === 'GET' => (new EcommerceAdminCategoryController('medical', '/admin/medical/categories'))->index(),
        $path === '/admin/medical/categories' && $method === 'POST' => (new EcommerceAdminCategoryController('medical', '/admin/medical/categories'))->store(),
        preg_match('#^/admin/medical/categories/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceAdminCategoryController('medical', '/admin/medical/categories'))->edit((int) $m[1]),
        preg_match('#^/admin/medical/categories/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminCategoryController('medical', '/admin/medical/categories'))->update((int) $m[1]),
        preg_match('#^/admin/medical/categories/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminCategoryController('medical', '/admin/medical/categories'))->delete((int) $m[1]),
        $path === '/admin/medical/subcategories' && $method === 'GET' => (new SubcategoryController('medical', '/admin/medical/subcategories', 'Medical'))->index(),
        $path === '/admin/medical/subcategories' && $method === 'POST' => (new SubcategoryController('medical', '/admin/medical/subcategories', 'Medical'))->store(),
        preg_match('#^/admin/medical/subcategories/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new SubcategoryController('medical', '/admin/medical/subcategories', 'Medical'))->edit((int) $m[1]),
        preg_match('#^/admin/medical/subcategories/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new SubcategoryController('medical', '/admin/medical/subcategories', 'Medical'))->update((int) $m[1]),
        preg_match('#^/admin/medical/subcategories/(\d+)/delete$#', $path, $m) === 1 => (new SubcategoryController('medical', '/admin/medical/subcategories', 'Medical'))->delete((int) $m[1]),
        $path === '/admin/medical/brands' && $method === 'GET' => (new EcommerceAdminBrandController('medical', '/admin/medical/brands'))->index(),
        $path === '/admin/medical/brands' && $method === 'POST' => (new EcommerceAdminBrandController('medical', '/admin/medical/brands'))->store(),
        preg_match('#^/admin/medical/brands/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceAdminBrandController('medical', '/admin/medical/brands'))->edit((int) $m[1]),
        preg_match('#^/admin/medical/brands/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminBrandController('medical', '/admin/medical/brands'))->update((int) $m[1]),
        preg_match('#^/admin/medical/brands/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminBrandController('medical', '/admin/medical/brands'))->delete((int) $m[1]),
        $path === '/admin/medical/products' && $method === 'GET' => (new EcommerceAdminProductController('medical', '/admin/medical/products'))->index(),
        $path === '/admin/medical/products' && $method === 'POST' => (new EcommerceAdminProductController('medical', '/admin/medical/products'))->store(),
        preg_match('#^/admin/medical/products/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceAdminProductController('medical', '/admin/medical/products'))->edit((int) $m[1]),
        preg_match('#^/admin/medical/products/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminProductController('medical', '/admin/medical/products'))->update((int) $m[1]),
        preg_match('#^/admin/medical/products/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminProductController('medical', '/admin/medical/products'))->delete((int) $m[1]),
        $path === '/admin/medical/orders' => (new EcommerceAdminOrderController('medical', '/admin/medical/orders'))->index(),
        preg_match('#^/admin/medical/orders/(\d+)$#', $path, $m) === 1 => (new EcommerceAdminOrderController('medical', '/admin/medical/orders'))->show((int) $m[1]),
        preg_match('#^/admin/medical/orders/(\d+)/invoice$#', $path, $m) === 1 => (new EcommerceAdminOrderController('medical', '/admin/medical/orders'))->invoice((int) $m[1]),
        preg_match('#^/admin/medical/orders/(\d+)/status$#', $path, $m) === 1 => (new EcommerceAdminOrderController('medical', '/admin/medical/orders'))->status((int) $m[1]),
        preg_match('#^/admin/medical/orders/(\d+)/delivery$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminOrderController('medical', '/admin/medical/orders'))->assignDelivery((int) $m[1]),
        preg_match('#^/admin/medical/orders/(\d+)/tracking$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminOrderController('medical', '/admin/medical/orders'))->tracking((int) $m[1]),
        $path === '/admin/medical/banners' && $method === 'GET' => (new EcommerceAdminBannerController('medical', '/admin/medical/banners', 'Medical Banners'))->index(),
        $path === '/admin/medical/banners' && $method === 'POST' => (new EcommerceAdminBannerController('medical', '/admin/medical/banners', 'Medical Banners'))->store(),
        preg_match('#^/admin/medical/banners/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceAdminBannerController('medical', '/admin/medical/banners', 'Medical Banners'))->edit((int) $m[1]),
        preg_match('#^/admin/medical/banners/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminBannerController('medical', '/admin/medical/banners', 'Medical Banners'))->update((int) $m[1]),
        preg_match('#^/admin/medical/banners/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminBannerController('medical', '/admin/medical/banners', 'Medical Banners'))->delete((int) $m[1]),
        $path === '/admin/medical/coupons' && $method === 'GET' => (new EcommerceAdminCouponController('medical', '/admin/medical/coupons'))->index(),
        $path === '/admin/medical/coupons' && $method === 'POST' => (new EcommerceAdminCouponController('medical', '/admin/medical/coupons'))->store(),
        preg_match('#^/admin/medical/coupons/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminCouponController('medical', '/admin/medical/coupons'))->update((int) $m[1]),
        preg_match('#^/admin/medical/coupons/(\d+)/delete$#', $path, $m) === 1 => (new EcommerceAdminCouponController('medical', '/admin/medical/coupons'))->delete((int) $m[1]),
        $path === '/admin/medical/reviews' && $method === 'GET' => (new EcommerceAdminReviewController('medical', '/admin/medical/reviews', 'Medical Reviews'))->index(),
        preg_match('#^/admin/medical/reviews/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminReviewController('medical', '/admin/medical/reviews', 'Medical Reviews'))->status((int) $m[1]),
        $path === '/admin/medical/refunds' && $method === 'GET' => (new EcommerceAdminRefundController('medical', '/admin/medical/refunds', 'Medical Refunds'))->index(),
        preg_match('#^/admin/medical/refunds/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminRefundController('medical', '/admin/medical/refunds', 'Medical Refunds'))->status((int) $m[1]),
        $path === '/admin/medical/prescriptions' && $method === 'GET' => (new MedicalPrescriptionController())->index(),
        preg_match('#^/admin/medical/prescriptions/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new MedicalPrescriptionController())->status((int) $m[1]),
        $path === '/admin/medical/payments' && $method === 'GET' => (new EcommerceAdminPaymentController('medical', '/admin/medical/payments', 'Medical Payments'))->index(),
        preg_match('#^/admin/medical/payments/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceAdminPaymentController('medical', '/admin/medical/payments', 'Medical Payments'))->status((int) $m[1]),
        $path === '/admin/medical/reports' => (new EcommerceAdminReportController('medical', 'Medical'))->index(),
        $path === '/admin/medical/reports/export/sales' => (new EcommerceAdminReportController('medical', 'Medical'))->exportSales(),
        $path === '/admin/medical/reports/export/vendors' => (new EcommerceAdminReportController('medical', 'Medical'))->exportVendors(),
        $path === '/admin/medical/reports/export/low-stock' => (new EcommerceAdminReportController('medical', 'Medical'))->exportLowStock(),
        $path === '/admin/vendors' && $method === 'GET' => (new AdminVendorController())->index(),
        preg_match('#^/admin/vendors/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminVendorController())->status((int) $m[1]),
        $path === '/admin/delivery-men' && $method === 'GET' => (new DeliveryManController())->index(),
        $path === '/admin/delivery-men' && $method === 'POST' => (new DeliveryManController())->store(),
        preg_match('#^/admin/delivery-men/(\d+)$#', $path, $m) === 1 && $method === 'POST' => (new DeliveryManController())->update((int) $m[1]),
        $path === '/admin/shipping' && $method === 'GET' => (new ShippingController())->index(),
        $path === '/admin/shipping' && $method === 'POST' => (new ShippingController())->store(),
        preg_match('#^/admin/shipping/(\d+)$#', $path, $m) === 1 && $method === 'POST' => (new ShippingController())->update((int) $m[1]),
        $path === '/admin/support' && $method === 'GET' => (new AdminSupportController())->index(),
        preg_match('#^/admin/support/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new AdminSupportController())->show((int) $m[1]),
        preg_match('#^/admin/support/(\d+)/reply$#', $path, $m) === 1 && $method === 'POST' => (new AdminSupportController())->reply((int) $m[1]),
        preg_match('#^/admin/support/(\d+)/assign-vendor$#', $path, $m) === 1 && $method === 'POST' => (new AdminSupportController())->assignVendor((int) $m[1]),
        $path === '/admin/settings' && $method === 'GET' => (new SettingsController())->edit(),
        $path === '/admin/settings' && $method === 'POST' => (new SettingsController())->update(),
        $path === '/admin/settings/firebase-test' && $method === 'POST' => (new SettingsController())->testFirebase(),
        $path === '/admin/complaints' && $method === 'GET' => (new AdminComplaintController())->index(),
        preg_match('#^/admin/complaints/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminComplaintController())->status((int) $m[1]),
        $path === '/admin/services' && $method === 'GET' => (new AdminServiceController())->index(),
        $path === '/admin/services' && $method === 'POST' => (new AdminServiceController())->storeService(),
        $path === '/admin/services/categories' && $method === 'POST' => (new AdminServiceController())->storeCategory(),
        preg_match('#^/admin/services/categories/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->updateCategory((int) $m[1]),
        preg_match('#^/admin/services/categories/(\d+)/archive$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->archiveCategory((int) $m[1]),
        preg_match('#^/admin/services/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->updateService((int) $m[1]),
        preg_match('#^/admin/services/(\d+)/archive$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->archiveService((int) $m[1]),
        $path === '/admin/services/providers' && $method === 'POST' => (new AdminServiceController())->storeProvider(),
        preg_match('#^/admin/services/providers/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->updateProvider((int) $m[1]),
        preg_match('#^/admin/services/providers/(\d+)/archive$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->archiveProvider((int) $m[1]),
        $path === '/admin/services/slots' && $method === 'POST' => (new AdminServiceController())->storeSlot(),
        preg_match('#^/admin/services/slots/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->updateSlot((int) $m[1]),
        preg_match('#^/admin/services/slots/(\d+)/archive$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->archiveSlot((int) $m[1]),
        $path === '/admin/services/blackouts' && $method === 'POST' => (new AdminServiceController())->storeBlackout(),
        preg_match('#^/admin/services/blackouts/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->updateBlackout((int) $m[1]),
        preg_match('#^/admin/services/blackouts/(\d+)/archive$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->archiveBlackout((int) $m[1]),
        $path === '/admin/services/addons' && $method === 'POST' => (new AdminServiceController())->storeAddon(),
        preg_match('#^/admin/services/addons/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->updateAddon((int) $m[1]),
        preg_match('#^/admin/services/addons/(\d+)/archive$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->archiveAddon((int) $m[1]),
        $path === '/admin/services/settlements' && $method === 'POST' => (new AdminServiceController())->storeSettlement(),
        preg_match('#^/admin/services/settlements/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->settlementStatus((int) $m[1]),
        $path === '/admin/services/bookings/export' => (new AdminServiceController())->exportBookings(),
        $path === '/admin/services/settlements/export' => (new AdminServiceController())->exportSettlements(),
        $path === '/admin/services/reports/providers/export' => (new AdminServiceController())->exportProviderReports(),
        $path === '/admin/services/reports/categories/export' => (new AdminServiceController())->exportCategoryReports(),
        $path === '/admin/services/reports/status/export' => (new AdminServiceController())->exportStatusReports(),
        preg_match('#^/admin/services/bookings/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->bookingStatus((int) $m[1]),
        preg_match('#^/admin/services/payments/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminServiceController())->paymentStatus((int) $m[1]),
        $path === '/admin/hotels' && $method === 'GET' => (new AdminHotelController())->index(),
        $path === '/admin/hotels/categories' && $method === 'POST' => (new AdminHotelController())->storeCategory(),
        preg_match('#^/admin/hotels/categories/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminHotelController())->updateCategory((int) $m[1]),
        $path === '/admin/hotels/owners' && $method === 'POST' => (new AdminHotelController())->storeOwner(),
        preg_match('#^/admin/hotels/owners/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminHotelController())->updateOwner((int) $m[1]),
        $path === '/admin/hotels/hotels' && $method === 'POST' => (new AdminHotelController())->storeHotel(),
        preg_match('#^/admin/hotels/hotels/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminHotelController())->updateHotel((int) $m[1]),
        $path === '/admin/hotels/rooms' && $method === 'POST' => (new AdminHotelController())->storeRoom(),
        preg_match('#^/admin/hotels/rooms/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminHotelController())->updateRoom((int) $m[1]),
        $path === '/admin/hotels/blackouts' && $method === 'POST' => (new AdminHotelController())->storeBlackout(),
        preg_match('#^/admin/hotels/blackouts/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminHotelController())->updateBlackout((int) $m[1]),
        preg_match('#^/admin/hotels/bookings/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminHotelController())->bookingStatus((int) $m[1]),
        $path === '/admin/real-estate' && $method === 'GET' => (new AdminRealEstateController())->index(),
        $path === '/admin/real-estate/agents' && $method === 'POST' => (new AdminRealEstateController())->storeAgent(),
        preg_match('#^/admin/real-estate/agents/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminRealEstateController())->updateAgent((int) $m[1]),
        $path === '/admin/real-estate/amenities' && $method === 'POST' => (new AdminRealEstateController())->storeAmenity(),
        preg_match('#^/admin/real-estate/amenities/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminRealEstateController())->updateAmenity((int) $m[1]),
        $path === '/admin/real-estate/properties' && $method === 'POST' => (new AdminRealEstateController())->storeProperty(),
        preg_match('#^/admin/real-estate/properties/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminRealEstateController())->updateProperty((int) $m[1]),
        $path === '/admin/real-estate/projects' && $method === 'POST' => (new AdminRealEstateController())->storeProject(),
        preg_match('#^/admin/real-estate/projects/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminRealEstateController())->updateProject((int) $m[1]),
        $path === '/admin/real-estate/project-units' && $method === 'POST' => (new AdminRealEstateController())->storeProjectUnit(),
        preg_match('#^/admin/real-estate/project-units/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new AdminRealEstateController())->updateProjectUnit((int) $m[1]),
        preg_match('#^/admin/real-estate/inquiries/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminRealEstateController())->inquiryStatus((int) $m[1]),
        preg_match('#^/admin/real-estate/site-visits/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminRealEstateController())->visitStatus((int) $m[1]),
        preg_match('#^/admin/real-estate/complaints/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminRealEstateController())->complaintStatus((int) $m[1]),
        $path === '/admin/restaurants' && $method === 'GET' => (new AdminRestaurantController())->index(),
        $path === '/admin/restaurants/food-items' && $method === 'POST' => (new AdminRestaurantController())->storeFoodItem(),
        preg_match('#^/admin/restaurants/food-orders/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminRestaurantController())->foodOrderStatus((int) $m[1]),
        preg_match('#^/admin/restaurants/bookings/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminRestaurantController())->bookingStatus((int) $m[1]),
        preg_match('#^/admin/restaurants/waitlist/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new AdminRestaurantController())->waitlistStatus((int) $m[1]),

        $path === '/hotel-owner/login' && $method === 'GET' => (new HotelOwnerAuthController())->showLogin(),
        $path === '/hotel-owner/login' && $method === 'POST' => (new HotelOwnerAuthController())->login(),
        $path === '/hotel-owner/logout' => (new HotelOwnerAuthController())->logout(),
        $path === '/hotel-owner' => (new HotelOwnerDashboardController())->index(),
        $path === '/hotel-owner/profile' && $method === 'POST' => (new HotelOwnerDashboardController())->profile(),
        preg_match('#^/hotel-owner/rooms/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new HotelOwnerDashboardController())->updateRoom((int) $m[1]),
        preg_match('#^/hotel-owner/bookings/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new HotelOwnerDashboardController())->status((int) $m[1]),
        $path === '/hotel-owner/wallet/withdrawals' && $method === 'POST' => (new HotelOwnerDashboardController())->withdrawal(),
        $path === '/hotel-owner/blackouts' && $method === 'POST' => (new HotelOwnerDashboardController())->storeBlackout(),
        preg_match('#^/hotel-owner/blackouts/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new HotelOwnerDashboardController())->updateBlackout((int) $m[1]),

        $path === '/real-estate-agent/login' && $method === 'GET' => (new RealEstateAgentAuthController())->showLogin(),
        $path === '/real-estate-agent/login' && $method === 'POST' => (new RealEstateAgentAuthController())->login(),
        $path === '/real-estate-agent/logout' => (new RealEstateAgentAuthController())->logout(),
        $path === '/real-estate-agent' => (new RealEstateAgentDashboardController())->index(),
        $path === '/real-estate-agent/profile' && $method === 'POST' => (new RealEstateAgentDashboardController())->updateProfile(),
        $path === '/real-estate-agent/properties' && $method === 'POST' => (new RealEstateAgentDashboardController())->storeProperty(),
        preg_match('#^/real-estate-agent/properties/(\d+)/update$#', $path, $m) === 1 && $method === 'POST' => (new RealEstateAgentDashboardController())->updateProperty((int) $m[1]),
        $path === '/real-estate-agent/projects' && $method === 'POST' => (new RealEstateAgentDashboardController())->storeProject(),
        $path === '/real-estate-agent/project-units' && $method === 'POST' => (new RealEstateAgentDashboardController())->storeProjectUnit(),
        preg_match('#^/real-estate-agent/inquiries/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new RealEstateAgentDashboardController())->inquiryStatus((int) $m[1]),
        preg_match('#^/real-estate-agent/site-visits/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new RealEstateAgentDashboardController())->visitStatus((int) $m[1]),

        $path === '/service-provider/login' && $method === 'GET' => (new ServiceProviderAuthController())->showLogin(),
        $path === '/service-provider/login' && $method === 'POST' => (new ServiceProviderAuthController())->login(),
        $path === '/service-provider/logout' => (new ServiceProviderAuthController())->logout(),
        $path === '/service-provider' => (new ServiceProviderDashboardController())->index(),
        $path === '/service-provider/profile' && $method === 'POST' => (new ServiceProviderDashboardController())->profile(),
        $path === '/service-provider/blackouts' && $method === 'POST' => (new ServiceProviderDashboardController())->storeBlackout(),
        preg_match('#^/service-provider/blackouts/(\d+)/archive$#', $path, $m) === 1 && $method === 'POST' => (new ServiceProviderDashboardController())->archiveBlackout((int) $m[1]),
        preg_match('#^/service-provider/bookings/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new ServiceProviderDashboardController())->status((int) $m[1]),

        $path === '/vendor/login' && $method === 'GET' => (new VendorAuthController())->showLogin(),
        $path === '/vendor/login' && $method === 'POST' => (new VendorAuthController())->login(),
        $path === '/vendor/logout' => (new VendorAuthController())->logout(),
        $path === '/vendor' => (new VendorDashboardController())->index(),
        $path === '/vendor/profile' && $method === 'POST' => (new VendorDashboardController())->updateProfile(),
        $path === '/vendor/products' && $method === 'GET' => (new VendorProductController())->index(),
        $path === '/vendor/products' && $method === 'POST' => (new VendorProductController())->store(),
        preg_match('#^/vendor/products/(\d+)/edit$#', $path, $m) === 1 && $method === 'GET' => (new VendorProductController())->edit((int) $m[1]),
        preg_match('#^/vendor/products/(\d+)/edit$#', $path, $m) === 1 && $method === 'POST' => (new VendorProductController())->update((int) $m[1]),
        preg_match('#^/vendor/products/(\d+)/delete$#', $path, $m) === 1 => (new VendorProductController())->delete((int) $m[1]),
        $path === '/vendor/orders' => (new VendorOrderController())->index(),
        $path === '/vendor/reports/orders.csv' => (new VendorReportController())->exportOrders(),
        $path === '/vendor/reports/settlements.csv' => (new VendorReportController())->exportSettlements(),
        $path === '/vendor/reports/products.csv' => (new VendorReportController())->exportProducts(),
        $path === '/vendor/reports/ledger.csv' => (new VendorReportController())->exportLedger(),
        $path === '/vendor/reports/summary.csv' => (new VendorReportController())->exportSummary(),
        preg_match('#^/vendor/order-items/(\d+)/status$#', $path, $m) === 1 && $method === 'POST' => (new VendorOrderController())->status((int) $m[1]),
        $path === '/vendor/notifications' && $method === 'GET' => (new VendorNotificationController())->index(),
        $path === '/vendor/notifications/read' && $method === 'POST' => (new VendorNotificationController())->markRead(),
        $path === '/vendor/reviews' && $method === 'GET' => (new VendorReviewController())->index(),
        preg_match('#^/vendor/reviews/(\d+)/reply$#', $path, $m) === 1 && $method === 'POST' => (new VendorReviewController())->reply((int) $m[1]),
        $path === '/vendor/refunds' && $method === 'GET' => (new VendorRefundController())->index(),
        $path === '/vendor/support' && $method === 'GET' => (new VendorSupportController())->index(),
        preg_match('#^/vendor/support/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new VendorSupportController())->show((int) $m[1]),
        preg_match('#^/vendor/support/(\d+)/reply$#', $path, $m) === 1 && $method === 'POST' => (new VendorSupportController())->reply((int) $m[1]),
        $path === '/vendor/wallet' && $method === 'GET' => (new VendorWalletController())->index(),
        $path === '/vendor/wallet/withdrawals' && $method === 'POST' => (new VendorWalletController())->requestWithdrawal(),

        $path === '/api/v1/auth/config' && $method === 'GET' => (new FirebaseAuthController())->config(),
        $path === '/api/v1/auth/firebase' && $method === 'POST' => (new FirebaseAuthController())->session(),
        $path === '/api/v1/mart/config' => (new MartController())->config(),
        $path === '/api/v1/device-token' && $method === 'POST' => (new DeviceTokenController('global'))->register(),
        $path === '/api/v1/mart/home' => (new MartController())->home(),
        $path === '/api/v1/mart/banners' => (new MartController())->banners(),
        $path === '/api/v1/mart/categories' => (new MartController())->categories(),
        $path === '/api/v1/mart/subcategories' => (new MartController())->subcategories(),
        $path === '/api/v1/mart/brands' => (new MartController())->brands(),
        $path === '/api/v1/mart/vendors' && $method === 'GET' => (new MartController())->vendors(),
        $path === '/api/v1/mart/products' => (new MartController())->products(),
        $path === '/api/v1/mart/products/featured' => (new MartController())->featuredProducts(),
        $path === '/api/v1/mart/products/flash-deals' => (new MartController())->flashDeals(),
        $path === '/api/v1/mart/products/clearance' => (new MartController())->clearanceProducts(),
        $path === '/api/v1/mart/products/top-rated' => (new MartController())->topRatedProducts(),
        $path === '/api/v1/mart/products/best-selling' => (new MartController())->bestSellingProducts(),
        $path === '/api/v1/mart/products/latest' => (new MartController())->latestProducts(),
        $path === '/api/v1/mart/products/search' => (new MartController())->searchProducts(),
        preg_match('#^/api/v1/mart/products/(\d+)$#', $path, $m) === 1 => (new MartController())->product((int) $m[1]),
        preg_match('#^/api/v1/mart/categories/(\d+)/products$#', $path, $m) === 1 => (new MartController())->categoryProducts((int) $m[1]),
        preg_match('#^/api/v1/mart/subcategories/(\d+)/products$#', $path, $m) === 1 => (new MartController())->subcategoryProducts((int) $m[1]),
        preg_match('#^/api/v1/mart/brands/(\d+)/products$#', $path, $m) === 1 => (new MartController())->brandProducts((int) $m[1]),
        preg_match('#^/api/v1/mart/vendors/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new MartController())->vendor((int) $m[1]),
        preg_match('#^/api/v1/mart/vendors/(\d+)/products$#', $path, $m) === 1 && $method === 'GET' => (new MartController())->vendorProducts((int) $m[1]),
        $path === '/api/v1/mart/cart' && $method === 'GET' => (new CartController())->index(),
        $path === '/api/v1/mart/cart/add' && $method === 'POST' => (new CartController())->add(),
        $path === '/api/v1/mart/cart/update' && $method === 'PUT' => (new CartController())->update(),
        $path === '/api/v1/mart/cart/remove' && $method === 'DELETE' => (new CartController())->remove(),
        $path === '/api/v1/mart/cart/remove-all' && $method === 'DELETE' => (new CartController())->removeAll(),
        $path === '/api/v1/mart/cart/coupon' && $method === 'POST' => (new CartController())->applyCoupon(),
        $path === '/api/v1/mart/cart/coupon' && $method === 'DELETE' => (new CartController())->removeCoupon(),
        $path === '/api/v1/mart/customers/register' && $method === 'POST' => (new CustomerController())->register(),
        $path === '/api/v1/mart/customers/login' && $method === 'POST' => (new CustomerController())->login(),
        $path === '/api/v1/mart/customers/profile' && $method === 'GET' => (new CustomerController())->profile(),
        $path === '/api/v1/mart/customers/wallet' && $method === 'GET' => (new ApiWalletController())->show(),
        $path === '/api/v1/mart/customers/wallet/withdrawals' && $method === 'POST' => (new ApiWalletController())->requestWithdrawal(),
        $path === '/api/v1/mart/customers/addresses' && $method === 'GET' => (new CustomerController())->addresses(),
        $path === '/api/v1/mart/customers/addresses' && $method === 'POST' => (new CustomerController())->saveAddress(),
        preg_match('#^/api/v1/mart/customers/addresses/(\d+)/default$#', $path, $m) === 1 && $method === 'POST' => (new CustomerController())->defaultAddress((int) $m[1]),
        preg_match('#^/api/v1/mart/customers/addresses/(\d+)$#', $path, $m) === 1 && $method === 'DELETE' => (new CustomerController())->deleteAddress((int) $m[1]),
        $path === '/api/v1/mart/vendors/register' && $method === 'POST' => (new VendorController())->register(),
        $path === '/api/v1/mart/notifications' && $method === 'GET' => (new NotificationController())->index(),
        $path === '/api/v1/mart/notifications/read' && $method === 'POST' => (new NotificationController())->markRead(),
        $path === '/api/v1/mart/device-token' && $method === 'POST' => (new DeviceTokenController('mart'))->register(),
        $path === '/api/v1/mart/support' && $method === 'GET' => (new ApiSupportController())->index(),
        $path === '/api/v1/mart/support' && $method === 'POST' => (new ApiSupportController())->store(),
        preg_match('#^/api/v1/mart/support/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new ApiSupportController())->show((int) $m[1]),
        preg_match('#^/api/v1/mart/support/(\d+)/reply$#', $path, $m) === 1 && $method === 'POST' => (new ApiSupportController())->reply((int) $m[1]),
        $path === '/api/v1/mart/wishlist' && $method === 'GET' => (new WishlistController())->index(),
        $path === '/api/v1/mart/wishlist/toggle' && $method === 'POST' => (new WishlistController())->toggle(),
        preg_match('#^/api/v1/mart/wishlist/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new WishlistController())->check((int) $m[1]),
        preg_match('#^/api/v1/mart/products/(\d+)/reviews$#', $path, $m) === 1 && $method === 'GET' => (new ReviewController())->index((int) $m[1]),
        preg_match('#^/api/v1/mart/products/(\d+)/reviews$#', $path, $m) === 1 && $method === 'POST' => (new ReviewController())->store((int) $m[1]),
        $path === '/api/v1/mart/refunds' && $method === 'GET' => (new RefundController())->index(),
        preg_match('#^/api/v1/mart/orders/(\d+)/refunds$#', $path, $m) === 1 && $method === 'POST' => (new RefundController())->store((int) $m[1]),
        $path === '/api/v1/mart/orders' && $method === 'GET' => (new OrderController())->index(),
        $path === '/api/v1/mart/orders/place' && $method === 'POST' => (new OrderController())->place(),
        $path === '/api/v1/mart/orders/track' => (new OrderController())->track(),
        preg_match('#^/api/v1/mart/orders/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST' => (new OrderController())->cancel((int) $m[1]),
        preg_match('#^/api/v1/mart/orders/(\d+)$#', $path, $m) === 1 => (new OrderController())->show((int) $m[1]),
        $path === '/api/v1/mart/payments/webhook' && $method === 'POST' => (new PaymentWebhookController())->order('mart'),

        $path === '/api/v1/ecommerce/config' => (new EcommerceController())->config(),
        $path === '/api/v1/ecommerce/home' => (new EcommerceController())->home(),
        $path === '/api/v1/ecommerce/banners' => (new EcommerceController())->banners(),
        $path === '/api/v1/ecommerce/categories' => (new EcommerceController())->categories(),
        $path === '/api/v1/ecommerce/subcategories' => (new EcommerceController())->subcategories(),
        $path === '/api/v1/ecommerce/brands' => (new EcommerceController())->brands(),
        $path === '/api/v1/ecommerce/vendors' && $method === 'GET' => (new EcommerceController())->vendors(),
        $path === '/api/v1/ecommerce/products' => (new EcommerceController())->products(),
        $path === '/api/v1/ecommerce/products/featured' => (new EcommerceController())->featuredProducts(),
        $path === '/api/v1/ecommerce/products/flash-deals' => (new EcommerceController())->flashDeals(),
        $path === '/api/v1/ecommerce/products/clearance' => (new EcommerceController())->clearanceProducts(),
        $path === '/api/v1/ecommerce/products/top-rated' => (new EcommerceController())->topRatedProducts(),
        $path === '/api/v1/ecommerce/products/best-selling' => (new EcommerceController())->bestSellingProducts(),
        $path === '/api/v1/ecommerce/products/latest' => (new EcommerceController())->latestProducts(),
        $path === '/api/v1/ecommerce/products/search' => (new EcommerceController())->searchProducts(),
        preg_match('#^/api/v1/ecommerce/products/(\d+)$#', $path, $m) === 1 => (new EcommerceController())->product((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/categories/(\d+)/products$#', $path, $m) === 1 => (new EcommerceController())->categoryProducts((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/subcategories/(\d+)/products$#', $path, $m) === 1 => (new EcommerceController())->subcategoryProducts((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/brands/(\d+)/products$#', $path, $m) === 1 => (new EcommerceController())->brandProducts((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/vendors/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceController())->vendor((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/vendors/(\d+)/products$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceController())->vendorProducts((int) $m[1]),
        $path === '/api/v1/ecommerce/cart' && $method === 'GET' => (new EcommerceCartController())->index(),
        $path === '/api/v1/ecommerce/cart/add' && $method === 'POST' => (new EcommerceCartController())->add(),
        $path === '/api/v1/ecommerce/cart/update' && $method === 'PUT' => (new EcommerceCartController())->update(),
        $path === '/api/v1/ecommerce/cart/remove' && $method === 'DELETE' => (new EcommerceCartController())->remove(),
        $path === '/api/v1/ecommerce/cart/remove-all' && $method === 'DELETE' => (new EcommerceCartController())->removeAll(),
        $path === '/api/v1/ecommerce/cart/coupon' && $method === 'POST' => (new EcommerceCartController())->applyCoupon(),
        $path === '/api/v1/ecommerce/cart/coupon' && $method === 'DELETE' => (new EcommerceCartController())->removeCoupon(),
        $path === '/api/v1/ecommerce/customers/register' && $method === 'POST' => (new EcommerceCustomerController())->register(),
        $path === '/api/v1/ecommerce/customers/login' && $method === 'POST' => (new EcommerceCustomerController())->login(),
        $path === '/api/v1/ecommerce/customers/profile' && $method === 'GET' => (new EcommerceCustomerController())->profile(),
        $path === '/api/v1/ecommerce/customers/wallet' && $method === 'GET' => (new EcommerceWalletController())->show(),
        $path === '/api/v1/ecommerce/customers/wallet/withdrawals' && $method === 'POST' => (new EcommerceWalletController())->requestWithdrawal(),
        $path === '/api/v1/ecommerce/customers/addresses' && $method === 'GET' => (new EcommerceCustomerController())->addresses(),
        $path === '/api/v1/ecommerce/customers/addresses' && $method === 'POST' => (new EcommerceCustomerController())->saveAddress(),
        preg_match('#^/api/v1/ecommerce/customers/addresses/(\d+)/default$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceCustomerController())->defaultAddress((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/customers/addresses/(\d+)$#', $path, $m) === 1 && $method === 'DELETE' => (new EcommerceCustomerController())->deleteAddress((int) $m[1]),
        $path === '/api/v1/ecommerce/vendors/register' && $method === 'POST' => (new EcommerceVendorController())->register(),
        $path === '/api/v1/ecommerce/notifications' && $method === 'GET' => (new EcommerceNotificationController())->index(),
        $path === '/api/v1/ecommerce/notifications/read' && $method === 'POST' => (new EcommerceNotificationController())->markRead(),
        $path === '/api/v1/ecommerce/device-token' && $method === 'POST' => (new DeviceTokenController('ecommerce'))->register(),
        $path === '/api/v1/ecommerce/support' && $method === 'GET' => (new EcommerceSupportController())->index(),
        $path === '/api/v1/ecommerce/support' && $method === 'POST' => (new EcommerceSupportController())->store(),
        preg_match('#^/api/v1/ecommerce/support/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceSupportController())->show((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/support/(\d+)/reply$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceSupportController())->reply((int) $m[1]),
        $path === '/api/v1/ecommerce/wishlist' && $method === 'GET' => (new EcommerceWishlistController())->index(),
        $path === '/api/v1/ecommerce/wishlist/toggle' && $method === 'POST' => (new EcommerceWishlistController())->toggle(),
        preg_match('#^/api/v1/ecommerce/wishlist/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceWishlistController())->check((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/products/(\d+)/reviews$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceReviewController())->index((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/products/(\d+)/reviews$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceReviewController())->store((int) $m[1]),
        $path === '/api/v1/ecommerce/refunds' && $method === 'GET' => (new EcommerceRefundController())->index(),
        preg_match('#^/api/v1/ecommerce/orders/(\d+)/refunds$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceRefundController())->store((int) $m[1]),
        $path === '/api/v1/ecommerce/orders' && $method === 'GET' => (new EcommerceOrderController())->index(),
        $path === '/api/v1/ecommerce/orders/place' && $method === 'POST' => (new EcommerceOrderController())->place(),
        $path === '/api/v1/ecommerce/orders/track' => (new EcommerceOrderController())->track(),
        preg_match('#^/api/v1/ecommerce/orders/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceOrderController())->cancel((int) $m[1]),
        preg_match('#^/api/v1/ecommerce/orders/(\d+)$#', $path, $m) === 1 => (new EcommerceOrderController())->show((int) $m[1]),
        $path === '/api/v1/ecommerce/payments/webhook' && $method === 'POST' => (new PaymentWebhookController())->order('ecommerce'),

        $path === '/api/v1/app/config' && $method === 'GET' => (new AppConfigController())->show(),
        $path === '/api/v1/zones' && $method === 'GET' => (new ApiZoneController())->index(),
        $path === '/api/v1/zones/resolve' && $method === 'POST' => (new ApiZoneController())->resolve(),
        $path === '/api/v1/zones/reverse-geocode' && $method === 'POST' => (new ApiZoneController())->reverseGeocode(),
        $path === '/api/v1/zones/search' && $method === 'POST' => (new ApiZoneController())->search(),

        $path === '/api/v1/workers/login' && $method === 'POST' => (new WorkerController())->login(),
        $path === '/api/v1/workers/me' && $method === 'GET' => (new WorkerController())->me(),
        $path === '/api/v1/workers/assignments' && $method === 'GET' => (new WorkerController())->assignments(),
        $path === '/api/v1/workers/assignments/status' && $method === 'POST' => (new WorkerController())->updateAssignment(),
        $path === '/api/v1/workers/location' && $method === 'POST' => (new WorkerController())->updateLocation(),
        $path === '/api/v1/workers/device-token' && $method === 'POST' => (new DeviceTokenController('worker'))->register(),

        $path === '/api/v1/taxi/config' && $method === 'GET' => (new TaxiController())->config(),
        $path === '/api/v1/taxi/quote' && $method === 'POST' => (new TaxiController())->quote(),
        $path === '/api/v1/taxi/estimate' => (new TaxiController())->estimate(),
        $path === '/api/v1/taxi/rides' && $method === 'GET' => (new TaxiController())->rides(),
        $path === '/api/v1/taxi/rides' && $method === 'POST' => (new TaxiController())->book(),
        preg_match('#^/api/v1/taxi/rides/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new TaxiController())->show((int) $m[1]),
        preg_match('#^/api/v1/taxi/rides/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST' => (new TaxiController())->cancel((int) $m[1]),
        preg_match('#^/api/v1/taxi/rides/(\d+)/rate$#', $path, $m) === 1 && $method === 'POST' => (new TaxiController())->rate((int) $m[1]),

        $path === '/api/v1/medical/config' => (new MedicalController())->config(),
        $path === '/api/v1/medical/home' => (new MedicalController())->home(),
        $path === '/api/v1/medical/banners' => (new MedicalController())->banners(),
        $path === '/api/v1/medical/categories' => (new MedicalController())->categories(),
        $path === '/api/v1/medical/subcategories' => (new MedicalController())->subcategories(),
        $path === '/api/v1/medical/brands' => (new MedicalController())->brands(),
        $path === '/api/v1/medical/vendors' && $method === 'GET' => (new MedicalController())->vendors(),
        $path === '/api/v1/medical/products' => (new MedicalController())->products(),
        $path === '/api/v1/medical/products/featured' => (new MedicalController())->featuredProducts(),
        $path === '/api/v1/medical/products/flash-deals' => (new MedicalController())->flashDeals(),
        $path === '/api/v1/medical/products/clearance' => (new MedicalController())->clearanceProducts(),
        $path === '/api/v1/medical/products/top-rated' => (new MedicalController())->topRatedProducts(),
        $path === '/api/v1/medical/products/best-selling' => (new MedicalController())->bestSellingProducts(),
        $path === '/api/v1/medical/products/latest' => (new MedicalController())->latestProducts(),
        $path === '/api/v1/medical/products/search' => (new MedicalController())->searchProducts(),
        preg_match('#^/api/v1/medical/products/(\d+)$#', $path, $m) === 1 => (new MedicalController())->product((int) $m[1]),
        preg_match('#^/api/v1/medical/categories/(\d+)/products$#', $path, $m) === 1 => (new MedicalController())->categoryProducts((int) $m[1]),
        preg_match('#^/api/v1/medical/subcategories/(\d+)/products$#', $path, $m) === 1 => (new MedicalController())->subcategoryProducts((int) $m[1]),
        preg_match('#^/api/v1/medical/brands/(\d+)/products$#', $path, $m) === 1 => (new MedicalController())->brandProducts((int) $m[1]),
        preg_match('#^/api/v1/medical/vendors/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new MedicalController())->vendor((int) $m[1]),
        preg_match('#^/api/v1/medical/vendors/(\d+)/products$#', $path, $m) === 1 && $method === 'GET' => (new MedicalController())->vendorProducts((int) $m[1]),
        $path === '/api/v1/medical/cart' && $method === 'GET' => (new EcommerceCartController('medical'))->index(),
        $path === '/api/v1/medical/cart/add' && $method === 'POST' => (new EcommerceCartController('medical'))->add(),
        $path === '/api/v1/medical/cart/update' && $method === 'PUT' => (new EcommerceCartController('medical'))->update(),
        $path === '/api/v1/medical/cart/remove' && $method === 'DELETE' => (new EcommerceCartController('medical'))->remove(),
        $path === '/api/v1/medical/cart/remove-all' && $method === 'DELETE' => (new EcommerceCartController('medical'))->removeAll(),
        $path === '/api/v1/medical/cart/coupon' && $method === 'POST' => (new EcommerceCartController('medical'))->applyCoupon(),
        $path === '/api/v1/medical/cart/coupon' && $method === 'DELETE' => (new EcommerceCartController('medical'))->removeCoupon(),
        $path === '/api/v1/medical/customers/register' && $method === 'POST' => (new EcommerceCustomerController())->register(),
        $path === '/api/v1/medical/customers/login' && $method === 'POST' => (new EcommerceCustomerController())->login(),
        $path === '/api/v1/medical/customers/profile' && $method === 'GET' => (new EcommerceCustomerController())->profile(),
        $path === '/api/v1/medical/customers/wallet' && $method === 'GET' => (new EcommerceWalletController())->show(),
        $path === '/api/v1/medical/customers/wallet/withdrawals' && $method === 'POST' => (new EcommerceWalletController())->requestWithdrawal(),
        $path === '/api/v1/medical/customers/addresses' && $method === 'GET' => (new EcommerceCustomerController())->addresses(),
        $path === '/api/v1/medical/customers/addresses' && $method === 'POST' => (new EcommerceCustomerController())->saveAddress(),
        preg_match('#^/api/v1/medical/customers/addresses/(\d+)/default$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceCustomerController())->defaultAddress((int) $m[1]),
        preg_match('#^/api/v1/medical/customers/addresses/(\d+)$#', $path, $m) === 1 && $method === 'DELETE' => (new EcommerceCustomerController())->deleteAddress((int) $m[1]),
        $path === '/api/v1/medical/vendors/register' && $method === 'POST' => (new EcommerceVendorController('medical'))->register(),
        $path === '/api/v1/medical/notifications' && $method === 'GET' => (new EcommerceNotificationController())->index(),
        $path === '/api/v1/medical/notifications/read' && $method === 'POST' => (new EcommerceNotificationController())->markRead(),
        $path === '/api/v1/medical/device-token' && $method === 'POST' => (new DeviceTokenController('medical'))->register(),
        $path === '/api/v1/medical/support' && $method === 'GET' => (new EcommerceSupportController('medical'))->index(),
        $path === '/api/v1/medical/support' && $method === 'POST' => (new EcommerceSupportController('medical'))->store(),
        preg_match('#^/api/v1/medical/support/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceSupportController('medical'))->show((int) $m[1]),
        preg_match('#^/api/v1/medical/support/(\d+)/reply$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceSupportController('medical'))->reply((int) $m[1]),
        $path === '/api/v1/medical/wishlist' && $method === 'GET' => (new EcommerceWishlistController('medical'))->index(),
        $path === '/api/v1/medical/wishlist/toggle' && $method === 'POST' => (new EcommerceWishlistController('medical'))->toggle(),
        preg_match('#^/api/v1/medical/wishlist/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceWishlistController('medical'))->check((int) $m[1]),
        preg_match('#^/api/v1/medical/products/(\d+)/reviews$#', $path, $m) === 1 && $method === 'GET' => (new EcommerceReviewController('medical'))->index((int) $m[1]),
        preg_match('#^/api/v1/medical/products/(\d+)/reviews$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceReviewController('medical'))->store((int) $m[1]),
        $path === '/api/v1/medical/refunds' && $method === 'GET' => (new EcommerceRefundController('medical'))->index(),
        preg_match('#^/api/v1/medical/orders/(\d+)/refunds$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceRefundController('medical'))->store((int) $m[1]),
        $path === '/api/v1/medical/orders' && $method === 'GET' => (new EcommerceOrderController('medical'))->index(),
        $path === '/api/v1/medical/orders/place' && $method === 'POST' => (new EcommerceOrderController('medical'))->place(),
        $path === '/api/v1/medical/orders/track' => (new EcommerceOrderController('medical'))->track(),
        preg_match('#^/api/v1/medical/orders/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST' => (new EcommerceOrderController('medical'))->cancel((int) $m[1]),
        preg_match('#^/api/v1/medical/orders/(\d+)$#', $path, $m) === 1 => (new EcommerceOrderController('medical'))->show((int) $m[1]),
        $path === '/api/v1/medical/payments/webhook' && $method === 'POST' => (new PaymentWebhookController())->order('medical'),

        $path === '/api/v1/services/config' => (new ServiceController())->config(),
        $path === '/api/v1/services/categories' => (new ServiceController())->categories(),
        $path === '/api/v1/services/services' => (new ServiceController())->services(),
        preg_match('#^/api/v1/services/services/(\d+)/slots$#', $path, $m) === 1 => (new ServiceController())->slots((int) $m[1]),
        $path === '/api/v1/services/bookings' && $method === 'GET' => (new ServiceController())->bookings(),
        $path === '/api/v1/services/bookings' && $method === 'POST' => (new ServiceController())->placeBooking(),
        $path === '/api/v1/services/payments/webhook' && $method === 'POST' => (new PaymentWebhookController())->service(),
        $path === '/api/v1/services/device-token' && $method === 'POST' => (new DeviceTokenController('services'))->register(),
        preg_match('#^/api/v1/services/bookings/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new ServiceController())->booking((int) $m[1]),
        preg_match('#^/api/v1/services/bookings/(\d+)/reschedule$#', $path, $m) === 1 && $method === 'POST' => (new ServiceController())->rescheduleBooking((int) $m[1]),
        preg_match('#^/api/v1/services/bookings/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST' => (new ServiceController())->cancelBooking((int) $m[1]),
        $path === '/api/v1/services/support' && $method === 'GET' => (new ServicesSupportController())->index(),
        $path === '/api/v1/services/support' && $method === 'POST' => (new ServicesSupportController())->store(),
        preg_match('#^/api/v1/services/support/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new ServicesSupportController())->show((int) $m[1]),
        preg_match('#^/api/v1/services/support/(\d+)/reply$#', $path, $m) === 1 && $method === 'POST' => (new ServicesSupportController())->reply((int) $m[1]),
        $path === '/api/v1/hotels/config' => (new HotelController())->config(),
        $path === '/api/v1/hotels/home' => (new HotelController())->home(),
        $path === '/api/v1/hotels/categories' => (new HotelController())->categories(),
        $path === '/api/v1/hotels/search' => (new HotelController())->hotels(),
        preg_match('#^/api/v1/hotels/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new HotelController())->show((int) $m[1]),
        preg_match('#^/api/v1/hotels/(\d+)/rooms$#', $path, $m) === 1 && $method === 'GET' => (new HotelController())->rooms((int) $m[1]),
        preg_match('#^/api/v1/hotels/(\d+)/reviews$#', $path, $m) === 1 && $method === 'POST' => (new HotelController())->review((int) $m[1]),
        $path === '/api/v1/hotels/bookings' && $method === 'GET' => (new HotelController())->bookings(),
        $path === '/api/v1/hotels/bookings' && $method === 'POST' => (new HotelController())->placeBooking(),
        preg_match('#^/api/v1/hotels/bookings/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new HotelController())->booking((int) $m[1]),
        preg_match('#^/api/v1/hotels/bookings/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST' => (new HotelController())->cancelBooking((int) $m[1]),
        $path === '/api/v1/hotels/payments/webhook' && $method === 'POST' => (new PaymentWebhookController())->hotel(),
        $path === '/api/v1/restaurants/config' && $method === 'GET' => (new RestaurantController())->config(),
        $path === '/api/v1/restaurants/home' && $method === 'GET' => (new RestaurantController())->home(),
        $path === '/api/v1/restaurants/search' && $method === 'GET' => (new RestaurantController())->restaurants(),
        $path === '/api/v1/restaurants/food-items' && $method === 'GET' => (new RestaurantController())->foodItems(),
        $path === '/api/v1/restaurants/orders' && $method === 'GET' => (new RestaurantController())->foodOrders(),
        $path === '/api/v1/restaurants/orders' && $method === 'POST' => (new RestaurantController())->placeFoodOrder(),
        preg_match('#^/api/v1/restaurants/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new RestaurantController())->show((int) $m[1]),
        preg_match('#^/api/v1/restaurants/(\d+)/slots$#', $path, $m) === 1 && $method === 'GET' => (new RestaurantController())->slots((int) $m[1]),
        preg_match('#^/api/v1/restaurants/(\d+)/reviews$#', $path, $m) === 1 && $method === 'POST' => (new RestaurantController())->review((int) $m[1]),
        $path === '/api/v1/restaurants/bookings' && $method === 'GET' => (new RestaurantController())->bookings(),
        $path === '/api/v1/restaurants/bookings' && $method === 'POST' => (new RestaurantController())->placeBooking(),
        $path === '/api/v1/restaurants/payments/webhook' && $method === 'POST' => (new PaymentWebhookController())->restaurant(),
        $path === '/api/v1/restaurants/waitlist' && $method === 'GET' => (new RestaurantController())->waitlist(),
        $path === '/api/v1/restaurants/waitlist' && $method === 'POST' => (new RestaurantController())->joinWaitlist(),
        preg_match('#^/api/v1/restaurants/bookings/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new RestaurantController())->booking((int) $m[1]),
        preg_match('#^/api/v1/restaurants/bookings/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST' => (new RestaurantController())->cancelBooking((int) $m[1]),
        $path === '/api/v1/real-estate/config' && $method === 'GET' => (new RealEstateController())->config(),
        $path === '/api/v1/real-estate/home' && $method === 'GET' => (new RealEstateController())->home(),
        $path === '/api/v1/real-estate/properties' && $method === 'GET' => (new RealEstateController())->properties(),
        preg_match('#^/api/v1/real-estate/properties/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new RealEstateController())->show((int) $m[1]),
        $path === '/api/v1/real-estate/projects' && $method === 'GET' => (new RealEstateController())->projects(),
        preg_match('#^/api/v1/real-estate/projects/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new RealEstateController())->project((int) $m[1]),
        $path === '/api/v1/real-estate/amenities' && $method === 'GET' => (new RealEstateController())->amenities(),
        preg_match('#^/api/v1/real-estate/agents/(\d+)$#', $path, $m) === 1 && $method === 'GET' => (new RealEstateController())->agent((int) $m[1]),
        $path === '/api/v1/real-estate/favorites' && $method === 'GET' => (new RealEstateController())->favorites(),
        $path === '/api/v1/real-estate/favorites/toggle' && $method === 'POST' => (new RealEstateController())->toggleFavorite(),
        $path === '/api/v1/real-estate/saved-searches' && $method === 'GET' => (new RealEstateController())->savedSearches(),
        $path === '/api/v1/real-estate/saved-searches' && $method === 'POST' => (new RealEstateController())->saveSearch(),
        $path === '/api/v1/real-estate/inquiries' && $method === 'GET' => (new RealEstateController())->inquiries(),
        $path === '/api/v1/real-estate/inquiries' && $method === 'POST' => (new RealEstateController())->inquiry(),
        $path === '/api/v1/real-estate/site-visits' && $method === 'GET' => (new RealEstateController())->siteVisits(),
        $path === '/api/v1/real-estate/site-visits' && $method === 'POST' => (new RealEstateController())->siteVisit(),
        $path === '/api/v1/real-estate/complaints' && $method === 'POST' => (new RealEstateController())->complaint(),
        $path === '/api/v1/complaints' && $method === 'POST' => (new ComplaintController())->store(),
        default => Response::json(['message' => 'Not found'], 404),
    };
    FirebasePush::processOutbox();
} catch (Throwable $e) {
    ErrorLogger::log($e);
    $debug = strtolower((string) Env::get('APP_DEBUG', 'false')) === 'true';
    $payload = ['message' => 'Server error'];
    if ($debug) {
        $payload['error'] = $e->getMessage();
    }
    Response::json($payload, 500);
}
