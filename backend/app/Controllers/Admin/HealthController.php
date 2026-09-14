<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Env;
use App\Support\Response;
use App\Support\Security;
use App\Support\Settings;
use App\Support\View;

final class HealthController
{
    public function index(): void
    {
        Auth::requireAdmin();
        if (Auth::role() !== 'super_admin') {
            Response::json(['message' => 'Permission denied'], 403);
            exit;
        }

        $checks = array_merge(
            $this->environmentChecks(),
            $this->schemaChecks(),
            $this->moduleConfigChecks('mart'),
            $this->moduleConfigChecks('ecommerce'),
            $this->moduleConfigChecks('medical'),
            $this->moduleConfigChecks('services'),
            $this->moduleConfigChecks('hotel'),
            $this->moduleConfigChecks('restaurant'),
            $this->credentialChecks(),
            $this->commerceContentChecks('mart'),
            $this->commerceContentChecks('ecommerce'),
            $this->commerceContentChecks('medical'),
            $this->servicesChecks(),
            $this->hotelChecks(),
            $this->realEstateChecks(),
            $this->restaurantChecks(),
            $this->taxiChecks(),
            $this->legalPolicyChecks(),
            $this->publicContactChecks(),
            $this->complaintChecks(),
            $this->floatingAdChecks(),
            $this->queueChecks()
        );

        View::render('admin/health', [
            'title' => 'Production Health',
            'checks' => $checks,
        ]);
    }

    private function environmentChecks(): array
    {
        $appUrl = (string) Env::get('APP_URL', '');
        $dbDriver = $this->databaseDriver();
        $uploadDir = dirname(__DIR__, 3) . '/public/uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $rateLimitDir = dirname(__DIR__, 3) . '/storage/rate-limit';
        if (!is_dir($rateLimitDir)) {
            @mkdir($rateLimitDir, 0775, true);
        }

        return [
            $this->check('Environment', '.env file', is_file(dirname(__DIR__, 3) . '/.env'), 'Create backend/.env on Hostinger.'),
            $this->check('Environment', 'Debug mode disabled', $this->debugDisabled(), 'Set APP_DEBUG=false in backend/.env before launch.'),
            $this->check('Environment', 'Production APP_URL', $this->appUrlIsProductionReady($appUrl), 'Set APP_URL to the public HTTPS Hostinger domain, not localhost or the example domain.', ['value' => $appUrl === '' ? 'missing' : $appUrl]),
            $this->check('Environment', 'Database connection', $this->canConnect(), 'Check DB credentials in backend/.env.'),
            $this->check('Environment', 'MySQL database driver', $dbDriver === 'mysql', 'Set DB_CONNECTION=mysql on Hostinger and import schema_mysql.sql.', ['driver' => $dbDriver === '' ? 'unknown' : $dbDriver]),
            $this->check('Environment', 'cURL extension', function_exists('curl_init'), 'Required for gateway/Firebase HTTP calls.'),
            $this->check('Environment', 'OpenSSL extension', function_exists('openssl_sign'), 'Required for Firebase HTTP v1 service account auth.'),
            $this->check('Environment', 'Fileinfo extension', class_exists(\finfo::class), 'Required for secure upload MIME validation.'),
            $this->check('Environment', 'Uploads writable', is_writable($uploadDir), 'Make backend/public/uploads writable.'),
            $this->check('Environment', 'Uploads script protection', $this->uploadsProtected($uploadDir), 'Upload backend/public/uploads/.htaccess with PHP/PHAR execution blocking.'),
            $this->check('Environment', 'Backend root protection', is_file(dirname(__DIR__, 3) . '/.htaccess'), 'Upload backend/.htaccess to block app/storage/database exposure.'),
            $this->check('Environment', 'Public root protection', is_file(dirname(__DIR__, 3) . '/public/.htaccess'), 'Upload backend/public/.htaccess to route safely and block backups.'),
            $this->check('Environment', 'Rate limit storage writable', is_writable($rateLimitDir), 'Make backend/storage/rate-limit writable.'),
        ];
    }

    private function schemaChecks(): array
    {
        $requirements = [
            ['admins', 'role'],
            ['payment_transactions', 'gateway_response'],
            ['payment_transactions', 'reconciled_at'],
            ['payment_transactions', 'reconciled_by'],
            ['payment_webhook_events', 'event_key'],
            ['payment_webhook_events', 'payload_hash'],
            ['payment_webhook_events', 'status'],
            ['payment_webhook_events', 'received_at'],
            ['payment_webhook_events', 'processed_at'],
            ['orders', 'substitution_preference'],
            ['hotel_payment_transactions', 'gateway_response'],
            ['hotel_payment_transactions', 'reconciled_at'],
            ['hotel_payment_transactions', 'reconciled_by'],
            ['service_payment_transactions', 'gateway_response'],
            ['service_payment_transactions', 'reconciled_at'],
            ['service_payment_transactions', 'reconciled_by'],
            ['service_payment_transactions', 'verified_at'],
            ['restaurant_payment_transactions', 'gateway_response'],
            ['restaurant_payment_transactions', 'reconciled_at'],
            ['restaurant_payment_transactions', 'reconciled_by'],
            ['restaurant_payment_transactions', 'verified_at'],
            ['hotel_bookings', 'refund_status'],
            ['hotel_bookings', 'refund_amount'],
            ['hotel_bookings', 'refund_note'],
            ['restaurant_bookings', 'booking_status'],
            ['restaurant_bookings', 'payment_status'],
            ['restaurant_waitlists', 'status'],
            ['restaurant_tables', 'table_count'],
            ['restaurant_shifts', 'slot_interval_minutes'],
            ['medical_prescriptions', 'status'],
            ['products', 'freshness_note'],
            ['products', 'expiry_date'],
            ['products', 'shelf_life'],
            ['products', 'warranty_note'],
            ['products', 'return_policy'],
            ['products', 'medicine_type'],
            ['products', 'max_qty_per_order'],
            ['products', 'max_qty_per_month'],
            ['products', 'requires_pharmacist_review'],
            ['products', 'requires_age_confirmation'],
            ['services', 'warranty_days'],
            ['services', 'checklist_json'],
            ['service_bookings', 'warranty_days'],
            ['service_bookings', 'warranty_until'],
            ['complaints', 'module_key'],
            ['complaints', 'severity'],
            ['complaints', 'zone_id'],
            ['complaints', 'order_id'],
            ['complaints', 'booking_id'],
            ['complaints', 'vendor_id'],
            ['complaints', 'provider_id'],
            ['complaints', 'hotel_id'],
            ['complaints', 'real_estate_property_id'],
            ['re_complaints', 'status'],
            ['support_threads', 'booking_id'],
            ['floating_ads', 'media_type'],
            ['floating_ads', 'media_url'],
            ['floating_ads', 'target_module'],
            ['floating_ads', 'target_type'],
            ['floating_ads', 'target_value'],
            ['zones', 'latitude'],
            ['zones', 'longitude'],
            ['zones', 'radius_km'],
            ['push_outbox', 'attempts'],
            ['push_outbox', 'last_error'],
            ['push_outbox', 'sent_at'],
            ['taxi_rides', 'customer_id'],
            ['taxi_rides', 'quote_token'],
            ['taxi_rides', 'route_polyline'],
            ['taxi_drivers', 'license_document'],
            ['taxi_drivers', 'insurance_expiry'],
            ['taxi_quotes', 'expires_at'],
        ];
        $checks = [];
        foreach ($requirements as [$table, $column]) {
            $checks[] = $this->check(
                'Database Schema',
                $table . '.' . $column,
                $this->columnExists($table, $column),
                'Import updated schema_mysql.sql or let runtime migrations create it where available.'
            );
        }
        return $checks;
    }

    private function credentialChecks(): array
    {
        $envEmail = trim((string) Env::get('ADMIN_EMAIL', ''));
        $envPassword = (string) Env::get('ADMIN_PASSWORD', '');
        $usesDefaultEnvAdmin = $envEmail === 'admin@citysolutions.local'
            || $envPassword === ''
            || $envPassword === 'password'
            || strlen($envPassword) < 12;

        return [
            $this->check('Credentials', 'Environment fallback admin changed', !$usesDefaultEnvAdmin, 'Change ADMIN_EMAIL and ADMIN_PASSWORD in backend/.env, or set a strong fallback password and non-demo email.'),
            $this->check('Credentials', 'Admin demo passwords removed', $this->countDefaultPasswords('admins') === 0, 'Change every seeded admin password, especially admin@citysolutions.local.'),
            $this->check('Credentials', 'Vendor demo passwords removed', $this->countDefaultPasswords('vendors') === 0, 'Change demo vendor passwords before launch.'),
            $this->check('Credentials', 'Service provider demo passwords removed', $this->countDefaultPasswords('service_providers') === 0, 'Change demo service provider passwords before launch.'),
            $this->check('Credentials', 'Hotel owner demo passwords removed', $this->countDefaultPasswords('hotel_owners') === 0, 'Change demo hotel owner passwords before launch.'),
            $this->check('Credentials', 'Real estate agent demo passwords removed', $this->countDefaultPasswords('re_agents') === 0, 'Change demo real estate agent passwords before launch.'),
        ];
    }

    private function commerceContentChecks(string $moduleKey): array
    {
        $label = $this->moduleLabel($moduleKey) . ' Content';
        $checks = [
            $this->check($label, 'Active categories', $this->countRows('categories', 'module_key = ' . $this->quote($moduleKey) . ' and status = 1') > 0, 'Add at least one active category.'),
            $this->check($label, 'Approved vendors', $this->countRows('vendors', 'module_key = ' . $this->quote($moduleKey) . ' and status = \'approved\'') > 0, 'Approve at least one vendor for this module.'),
            $this->check($label, 'Active products', $this->countRows('products', 'module_key = ' . $this->quote($moduleKey) . ' and status = 1') > 0, 'Add at least one active product.'),
            $this->check($label, 'Zone assigned products', $this->countRows('products', 'module_key = ' . $this->quote($moduleKey) . ' and status = 1 and zone_id is not null') > 0, 'Assign zones to products/vendors for location filtering.'),
            $this->check($label, 'Product images', $this->countRows('products', 'module_key = ' . $this->quote($moduleKey) . ' and status = 1 and thumbnail is not null and thumbnail != \'\'') > 0, 'Upload product/category media so the app is not visually empty.'),
        ];

        if ($moduleKey === 'medical') {
            $checks[] = $this->check($label, 'Prescription-controlled products', $this->countRows('products', 'module_key = \'medical\' and status = 1 and medicine_type in (\'prescription_required\', \'restricted\')') > 0, 'Configure prescription/restricted medicine examples before launch.');
            $checks[] = $this->check($label, 'Quantity/risk controls', $this->countRows('products', 'module_key = \'medical\' and status = 1 and (coalesce(max_qty_per_order, 0) > 0 or coalesce(max_qty_per_month, 0) > 0 or requires_pharmacist_review = 1 or requires_age_confirmation = 1)') > 0, 'Set quantity limits, pharmacist review, or age confirmation on risky medicines.');
        }

        return $checks;
    }

    private function servicesChecks(): array
    {
        return [
            $this->check('Services Content', 'Active providers', $this->countRows('service_providers', 'status = 1') > 0, 'Add at least one active service provider.'),
            $this->check('Services Content', 'Active services', $this->countRows('services', 'status = 1') > 0, 'Add at least one active service.'),
            $this->check('Services Content', 'Bookable slots', $this->countRows('service_slots', 'status = 1') > 0, 'Add active provider/service slots.'),
            $this->check('Services Content', 'Zone assigned services', $this->countRows('services', 'status = 1 and zone_id is not null') > 0, 'Assign zones to services for location filtering.'),
            $this->check('Services Content', 'Warranty configured', $this->countRows('services', 'status = 1 and coalesce(warranty_days, 0) > 0') > 0, 'Set warranty days for services that include post-service support.'),
            $this->check('Services Content', 'Customer checklist configured', $this->countRows('services', 'status = 1 and checklist_json is not null and checklist_json != \'\'') > 0, 'Add customer preparation checklist items to services.'),
        ];
    }

    private function hotelChecks(): array
    {
        return [
            $this->check('Hotels Content', 'Active hotels', $this->countRows('hotels', 'status = 1') > 0, 'Add at least one active hotel.'),
            $this->check('Hotels Content', 'Active rooms', $this->countRows('hotel_rooms', 'status = 1') > 0, 'Add active room inventory.'),
            $this->check('Hotels Content', 'Zone assigned hotels', $this->countRows('hotels', 'status = 1 and zone_id is not null') > 0, 'Assign zones to hotels for location filtering.'),
            $this->check('Hotels Content', 'Room images', $this->countRows('hotel_rooms', 'status = 1 and thumbnail is not null and thumbnail != \'\'') > 0, 'Upload hotel/room media before publishing.'),
        ];
    }

    private function moduleConfigChecks(string $moduleKey): array
    {
        $label = $this->moduleLabel($moduleKey) . ' Config';
        $onlineEnabled = $this->boolSetting($moduleKey, 'online_payment_enabled', true);
        $firebaseEnabled = $this->boolSetting($moduleKey, 'firebase_push_enabled', false);
        $usesWebhookOnlyPayments = in_array($moduleKey, ['services', 'restaurant'], true);
        $checks = [
            $this->check($label, 'Online payment mode', true, '', ['enabled' => $onlineEnabled ? 'yes' : 'no']),
            $this->check($label, 'Webhook secret', !$onlineEnabled || $this->moduleSetting($moduleKey, 'online_payment_webhook_secret') !== '', 'Set webhook secret in Settings, or disable online payment until ready.'),
            $this->check($label, 'Firebase credentials', !$firebaseEnabled || $this->hasFirebase($moduleKey), 'Set Firebase server key/service account JSON, or disable push until ready.', ['enabled' => $firebaseEnabled ? 'yes' : 'no']),
        ];
        if ($usesWebhookOnlyPayments) {
            $checks[] = $this->check($label, 'Booking payment webhook', true, '', ['endpoint' => '/api/v1/' . ($moduleKey === 'services' ? 'services' : 'restaurants') . '/payments/webhook']);
        } else {
            $checks[] = $this->check($label, 'Gateway capture URL', !$onlineEnabled || $this->validOutboundUrl($this->moduleSetting($moduleKey, 'online_payment_capture_url')), 'Set a valid HTTPS capture URL, or disable online payment until ready.');
            $checks[] = $this->check($label, 'Gateway status URL', !$onlineEnabled || $this->validOutboundUrl($this->moduleSetting($moduleKey, 'online_payment_status_url')), 'Set a valid HTTPS status URL for reconciliation, or disable online payment until ready.');
            $checks[] = $this->check($label, 'Gateway refund URL', !$onlineEnabled || $this->validOutboundUrl($this->moduleSetting($moduleKey, 'online_payment_refund_url')), 'Set a valid HTTPS refund URL for production refunds, or disable online payment until ready.');
            $checks[] = $this->check($label, 'Gateway auth header', !$onlineEnabled || $this->hasGatewayAuth($moduleKey), 'Set a valid single-line auth header or secret key for gateway calls.');
        }
        $failedWebhookEvents = $this->countRows('payment_webhook_events', 'module_key = ' . $this->quote($moduleKey) . ' and status = \'failed\'');
        $checks[] = $this->check($label, 'Failed webhook events', $failedWebhookEvents === 0, 'Retry or inspect failed payment webhook events before launch.', ['failed' => (string) $failedWebhookEvents]);

        if ($moduleKey === 'medical') {
            $checks[] = $this->check($label, 'Prescription policy', $this->moduleSetting('medical', 'prescription_policy') !== '', 'Set medical prescription policy.');
            $checks[] = $this->check($label, 'Medical privacy policy', $this->moduleSetting('medical', 'privacy_policy') !== '', 'Set medical privacy policy.');
        }
        if ($moduleKey === 'hotel') {
            $checks[] = $this->check($label, 'Cancellation free hours', $this->setting('hotel_cancellation_free_hours') !== '', 'Set hotel cancellation free hours.');
            $checks[] = $this->check($label, 'Late refund percent', $this->setting('hotel_late_cancellation_refund_percent') !== '', 'Set late cancellation refund percent.');
        }

        return $checks;
    }

    private function publicContactChecks(): array
    {
        $email = $this->setting('public_support_email');
        return [
            $this->check('Public Website', 'Business name', $this->setting('public_business_name') !== '', 'Set Public Website Contact business name in Settings.'),
            $this->check('Public Website', 'Legal entity', $this->setting('public_legal_entity') !== '', 'Set Public Website Contact legal entity in Settings.'),
            $this->check('Public Website', 'Support email', filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 'Set a valid support email in Settings.'),
            $this->check('Public Website', 'Business address', $this->setting('public_business_address') !== '', 'Set business address/location in Settings.'),
        ];
    }

    private function realEstateChecks(): array
    {
        $firebaseEnabled = $this->boolSetting('real_estate', 'firebase_push_enabled', false);
        return [
            $this->check('Real Estate Config', 'Firebase credentials', !$firebaseEnabled || $this->hasFirebase('real_estate'), 'Set Real Estate Firebase credentials or disable push until ready.', ['enabled' => $firebaseEnabled ? 'yes' : 'no']),
            $this->check('Real Estate Config', 'Approved agents/builders', $this->countRows('re_agents', 'status = \'approved\'') > 0, 'Approve at least one real estate agent/builder before launch.'),
            $this->check('Real Estate Config', 'Approved properties', $this->countRows('re_properties', 'status = \'approved\'') > 0, 'Add and approve at least one property listing.'),
            $this->check('Real Estate Config', 'Active zones assigned', $this->countRows('re_properties', 'status = \'approved\' and zone_id is not null') > 0, 'Assign zones to real estate listings for location filtering.'),
            $this->check('Real Estate Config', 'Listing report queue ready', $this->tableExists('re_complaints'), 'Open /admin/real-estate after upload to run lazy schema setup.'),
        ];
    }

    private function restaurantChecks(): array
    {
        $firebaseEnabled = $this->boolSetting('restaurant', 'firebase_push_enabled', false);
        return [
            $this->check('Restaurants Config', 'Firebase credentials', !$firebaseEnabled || $this->hasFirebase('restaurant'), 'Set Restaurant Firebase credentials or disable push until ready.', ['enabled' => $firebaseEnabled ? 'yes' : 'no']),
            $this->check('Restaurants Config', 'Active restaurants', $this->countRows('restaurants', 'status = 1') > 0, 'Add at least one active restaurant.'),
            $this->check('Restaurants Config', 'Active tables', $this->countRows('restaurant_tables', 'status = 1') > 0, 'Add table inventory for restaurants.'),
            $this->check('Restaurants Config', 'Active shifts', $this->countRows('restaurant_shifts', 'status = 1') > 0, 'Add booking shifts for restaurants.'),
            $this->check('Restaurants Config', 'Active zones assigned', $this->countRows('restaurants', 'status = 1 and zone_id is not null') > 0, 'Assign zones to restaurants for location filtering.'),
            $this->check('Restaurants Config', 'Waitlist table ready', $this->tableExists('restaurant_waitlists'), 'Open restaurant admin/API once after upload to run lazy schema setup.'),
        ];
    }

    private function taxiChecks(): array
    {
        $routesKey = trim($this->moduleSetting('taxi', 'google_routes_api_key'));
        return [
            $this->check('Taxi Config', 'Google Routes key', $routesKey !== '', 'Add a server-restricted Google Routes API key in Taxi Operations.'),
            $this->check('Taxi Config', 'Approximate fallback disabled', !$this->boolSetting('taxi', 'allow_fallback_quotes', false), 'Disable approximate geographic quotes before public launch.'),
            $this->check('Taxi Config', 'Active vehicle types', $this->countRows('taxi_vehicle_types', 'status = 1') > 0, 'Add at least one active taxi vehicle type and fare rule.'),
            $this->check('Taxi Config', 'Approved drivers', $this->countRows('taxi_drivers', 'status = \'approved\'') > 0, 'Approve at least one taxi driver.'),
            $this->check('Taxi Config', 'Zone assigned drivers', $this->countRows('taxi_drivers', 'status = \'approved\' and zone_id is not null') > 0, 'Assign approved drivers to service zones.'),
            $this->check('Taxi Config', 'Driver KYC documents', $this->countRows('taxi_drivers', 'status = \'approved\' and license_document is not null and vehicle_document is not null and insurance_document is not null') > 0, 'Upload license, RC and insurance images for approved drivers.'),
            $this->check('Taxi Config', 'Valid driver documents', $this->countRows('taxi_drivers', 'status = \'approved\' and license_expiry >= current_date and insurance_expiry >= current_date') > 0, 'Set current license and insurance expiry dates.'),
            $this->check('Taxi Config', 'Customer payment method', $this->boolSetting('taxi', 'cash_enabled', true) || $this->boolSetting('taxi', 'wallet_enabled', true), 'Enable Cash or Wallet in Taxi Operations.'),
        ];
    }

    private function legalPolicyChecks(): array
    {
        $checks = [];
        foreach (['mart', 'ecommerce', 'medical', 'services', 'hotel', 'real_estate', 'restaurant'] as $moduleKey) {
            $label = $this->moduleLabel($moduleKey) . ' Legal';
            $checks[] = $this->check($label, 'Terms content', $this->moduleSetting($moduleKey, 'terms_conditions') !== '', 'Add module-specific terms in Settings.');
            $checks[] = $this->check($label, 'Privacy content', $this->moduleSetting($moduleKey, 'privacy_policy') !== '', 'Add module-specific privacy policy in Settings.');
            $checks[] = $this->check($label, 'Refund/service policy content', $this->moduleSetting($moduleKey, 'refund_policy') !== '' || $this->moduleSetting($moduleKey, 'shipping_policy') !== '', 'Add refund, shipping, booking, or service policy content in Settings.');
        }
        return $checks;
    }

    private function complaintChecks(): array
    {
        return [
            $this->check('Complaints', 'Routed complaint schema', $this->columnExists('complaints', 'module_key') && $this->columnExists('complaints', 'zone_id'), 'Upload latest backend/app so complaints route by module and zone.'),
            $this->check('Complaints', 'Admin complaint queue reachable', $this->tableExists('complaints'), 'Open /admin/complaints once after upload to create/verify the queue.'),
        ];
    }

    private function floatingAdChecks(): array
    {
        $activeAds = $this->countRows('floating_ads', 'status = 1');
        $activeMediaAds = $this->countRows('floating_ads', 'status = 1 and media_url is not null and media_url != \'\'');
        $usableMediaAds = $this->countUsableFloatingAds();
        return [
            $this->check('Floating Ads', 'Uploaded ads table', $this->tableExists('floating_ads'), 'Open /admin/floating-ads after upload to create the ads table.'),
            $this->check('Floating Ads', 'Active uploaded ad', $activeAds > 0, 'Upload and enable at least one floating ad.'),
            $this->check('Floating Ads', 'Active ad media', $activeMediaAds > 0, 'Attach image/video media to active ads.'),
            $this->check('Floating Ads', 'Active ad media file reachable', $usableMediaAds > 0, 'For uploaded ads, upload the referenced file under backend/public/uploads. Remote http(s) media URLs are accepted.', ['usable_ads' => (string) $usableMediaAds]),
        ];
    }

    private function countUsableFloatingAds(): int
    {
        try {
            if (!$this->tableExists('floating_ads')) {
                return 0;
            }
            $rows = Database::connection()
                ->query('select media_url from floating_ads where status = 1 and media_url is not null and media_url != \'\'')
                ->fetchAll();
        } catch (\Throwable) {
            return 0;
        }

        $count = 0;
        $publicRoot = dirname(__DIR__, 3) . '/public';
        foreach ($rows as $row) {
            $url = trim((string) ($row['media_url'] ?? ''));
            if ($url === '') {
                continue;
            }
            if (preg_match('#^https?://#i', $url) === 1) {
                $count++;
                continue;
            }
            $path = parse_url($url, PHP_URL_PATH);
            if (!is_string($path) || $path === '') {
                continue;
            }
            $normalized = '/' . ltrim($path, '/');
            if (!str_starts_with($normalized, '/uploads/')) {
                continue;
            }
            if (is_file($publicRoot . $normalized)) {
                $count++;
            }
        }
        return $count;
    }

    private function queueChecks(): array
    {
        $pushEnabled = $this->anyPushEnabled();
        $pending = $this->countRows('push_outbox', 'sent_at is null and attempts < 5');
        $failed = $this->countRows('push_outbox', 'sent_at is null and attempts >= 5');
        return [
            $this->check('Notifications', 'Pending push outbox', !$pushEnabled || $failed === 0, 'Failed push jobs need Firebase/device-token review.', ['enabled' => $pushEnabled ? 'yes' : 'no', 'pending' => $pending, 'failed' => $failed]),
            $this->check('Notifications', 'Device tokens saved', !$pushEnabled || $this->countRows('device_tokens', '1 = 1') > 0, 'Register a real app device token before testing push.', ['enabled' => $pushEnabled ? 'yes' : 'no']),
        ];
    }

    private function uploadsProtected(string $uploadDir): bool
    {
        $path = $uploadDir . '/.htaccess';
        if (!is_file($path)) {
            return false;
        }
        $content = file_get_contents($path);
        if (!is_string($content)) {
            return false;
        }
        return str_contains($content, 'Options -Indexes')
            && str_contains($content, 'RemoveHandler')
            && str_contains($content, 'RemoveType')
            && str_contains($content, 'FilesMatch')
            && str_contains($content, 'Require all denied')
            && str_contains($content, 'phar');
    }

    private function canConnect(): bool
    {
        try {
            Database::connection()->query('select 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function validOutboundUrl(string $url): bool
    {
        return trim($url) !== '' && Security::validateOutboundHttpsUrl($url) === null;
    }

    private function validHeaderLine(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        if (str_contains($value, "\r") || str_contains($value, "\n") || strlen($value) > 1000) {
            return false;
        }
        return preg_match('/^[A-Za-z0-9-]+:\s*[^\x00-\x1F\x7F]+$/', $value) === 1;
    }

    private function hasGatewayAuth(string $moduleKey): bool
    {
        return $this->moduleSetting($moduleKey, 'online_payment_secret_key') !== ''
            || $this->validHeaderLine($this->moduleSetting($moduleKey, 'online_payment_auth_header'));
    }

    private function databaseDriver(): string
    {
        try {
            return (string) Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable) {
            return '';
        }
    }

    private function debugDisabled(): bool
    {
        return !in_array(strtolower((string) Env::get('APP_DEBUG', 'false')), ['1', 'true', 'yes', 'on'], true);
    }

    private function appUrlIsProductionReady(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }
        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return false;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', 'your-domain.com'], true)) {
            return false;
        }
        return !str_ends_with($host, '.local');
    }

    private function hasFirebase(string $moduleKey): bool
    {
        return $this->moduleSetting($moduleKey, 'firebase_server_key') !== ''
            || $this->moduleSetting($moduleKey, 'firebase_service_account_json') !== ''
            || $this->setting('firebase_server_key') !== ''
            || $this->setting('firebase_service_account_json') !== '';
    }

    private function anyPushEnabled(): bool
    {
        if ($this->setting('firebase_push_enabled') === '1') {
            return true;
        }
        foreach (['mart', 'ecommerce', 'medical', 'services', 'hotel', 'real_estate', 'restaurant'] as $moduleKey) {
            if ($this->boolSetting($moduleKey, 'firebase_push_enabled', false)) {
                return true;
            }
        }
        return false;
    }

    private function moduleLabel(string $moduleKey): string
    {
        return match ($moduleKey) {
            'ecommerce' => 'E-Commerce',
            'services' => 'Services',
            'hotel' => 'Hotels',
            'medical' => 'Medical',
            'restaurant' => 'Restaurants',
            'real_estate' => 'Real Estate',
            default => 'Mart',
        };
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $db = Database::connection();
            if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $stmt = $db->prepare(
                    'select count(*) from information_schema.columns
                     where table_schema = database() and table_name = :table and column_name = :column'
                );
                $stmt->execute(['table' => $table, 'column' => $column]);
                return (int) $stmt->fetchColumn() > 0;
            }
            $columns = $db->query('pragma table_info(' . $table . ')')->fetchAll();
            foreach ($columns as $existing) {
                if (($existing['name'] ?? '') === $column) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }
        return false;
    }

    private function tableExists(string $table): bool
    {
        try {
            $db = Database::connection();
            if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $stmt = $db->prepare(
                    'select count(*) from information_schema.tables
                     where table_schema = database() and table_name = :table'
                );
                $stmt->execute(['table' => $table]);
                return (int) $stmt->fetchColumn() > 0;
            }
            $stmt = $db->prepare("select count(*) from sqlite_master where type = 'table' and name = :table");
            $stmt->execute(['table' => $table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function countRows(string $table, string $where): int
    {
        try {
            return (int) Database::connection()->query('select count(*) from ' . $table . ' where ' . $where)->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function countDefaultPasswords(string $table): int
    {
        if (!$this->tableExists($table) || !$this->columnExists($table, 'password')) {
            return 0;
        }
        try {
            $rows = Database::connection()->query('select password from ' . $table . ' where password is not null and password != \'\'')->fetchAll();
        } catch (\Throwable) {
            return 0;
        }
        $count = 0;
        foreach ($rows as $row) {
            $hash = (string) ($row['password'] ?? '');
            if ($hash !== '' && password_verify('password', $hash)) {
                $count++;
            }
        }
        return $count;
    }

    private function quote(string $value): string
    {
        return '\'' . str_replace('\'', '\'\'', $value) . '\'';
    }

    private function setting(string $key): string
    {
        try {
            return Settings::get($key);
        } catch (\Throwable) {
            return '';
        }
    }

    private function moduleSetting(string $moduleKey, string $key): string
    {
        try {
            return Settings::moduleGet($moduleKey, $key);
        } catch (\Throwable) {
            return '';
        }
    }

    private function boolSetting(string $moduleKey, string $key, bool $default = false): bool
    {
        $value = $this->moduleSetting($moduleKey, $key);
        if ($value === '') {
            return $default;
        }
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    private function check(string $group, string $name, bool $ok, string $fix, array $meta = []): array
    {
        return [
            'group' => $group,
            'name' => $name,
            'status' => $ok ? 'ok' : 'action_needed',
            'fix' => $ok ? '' : $fix,
            'meta' => $meta,
        ];
    }
}
