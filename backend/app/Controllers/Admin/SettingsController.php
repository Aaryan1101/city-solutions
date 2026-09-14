<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\FirebasePush;
use App\Support\Response;
use App\Support\Security;
use App\Support\Settings;
use App\Support\View;

final class SettingsController
{
    public function edit(): void
    {
        Auth::requireAdmin();
        View::render('admin/settings', [
            'title' => 'Settings',
            'settings' => Settings::all(),
        ]);
    }

    public function update(): void
    {
        Auth::requireAdmin();
        $current = Settings::all();
        $scope = trim($_POST['settings_scope'] ?? 'global');
        $posted = static fn (string $key, string $default = ''): string => trim((string) ($_POST[$key] ?? $current[$key] ?? $default));
        $postedSecret = fn (string $key): string => $this->secretFromPost($key, $key, $current);
        $postedHeaderSecret = fn (string $key): string => $this->secretFromPost($key, $key, $current, fn (string $value): string => $this->cleanHeaderLine($value));
        $postedBool = static fn (string $key, string $currentKey, bool $default = false): string => $scope === 'global'
            ? (isset($_POST[$key]) ? '1' : '0')
            : (string) ($current[$currentKey] ?? ($default ? '1' : '0'));
        $values = [
            'app_name' => $posted('app_name', 'City Solutions Mart'),
            'currency' => $posted('currency', 'INR'),
            'currency_symbol' => $posted('currency_symbol', '₹'),
            'minimum_order_amount' => max(0, (float) $posted('minimum_order_amount', '0')),
            'delivery_charge' => max(0, (float) $posted('delivery_charge', '0')),
            'vendor_commission_percent' => min(100, max(0, (float) $posted('vendor_commission_percent', '0'))),
            'cod_enabled' => $postedBool('cod_enabled', 'cod_enabled', true),
            'online_payment_enabled' => $postedBool('online_payment_enabled', 'online_payment_enabled', true),
            'manual_payment_enabled' => $postedBool('manual_payment_enabled', 'manual_payment_enabled', true),
            'wallet_payment_enabled' => $postedBool('wallet_payment_enabled', 'wallet_payment_enabled', true),
            'online_payment_title' => $posted('online_payment_title', 'Online Payment'),
            'online_payment_description' => $posted('online_payment_description'),
            'online_payment_gateway' => $this->cleanGateway($posted('online_payment_gateway', 'manual')),
            'online_payment_public_key' => $posted('online_payment_public_key'),
            'online_payment_secret_key' => $postedSecret('online_payment_secret_key'),
            'online_payment_webhook_secret' => $postedSecret('online_payment_webhook_secret'),
            'online_payment_capture_url' => $this->cleanOutboundUrl($posted('online_payment_capture_url')),
            'online_payment_status_url' => $this->cleanOutboundUrl($posted('online_payment_status_url')),
            'online_payment_refund_url' => $this->cleanOutboundUrl($posted('online_payment_refund_url')),
            'online_payment_auth_header' => $postedHeaderSecret('online_payment_auth_header'),
            'online_payment_merchant_id' => $posted('online_payment_merchant_id'),
            'online_payment_environment' => in_array($posted('online_payment_environment', 'test'), ['test', 'live'], true)
                ? $posted('online_payment_environment', 'test')
                : 'test',
            'online_payment_instructions' => $posted('online_payment_instructions'),
            'bank_transfer_title' => $posted('bank_transfer_title', 'Bank Transfer'),
            'bank_transfer_description' => $posted('bank_transfer_description'),
            'bank_transfer_instructions' => $posted('bank_transfer_instructions'),
            'api_rate_limit_per_minute' => max(10, min(600, (int) $posted('api_rate_limit_per_minute', '60'))),
            'panel_session_timeout_minutes' => max(15, min(1440, (int) $posted('panel_session_timeout_minutes', '120'))),
            'support_poll_interval_seconds' => max(5, min(120, (int) $posted('support_poll_interval_seconds', '15'))),
            'support_realtime_provider' => $posted('support_realtime_provider', 'polling'),
            'app_locale' => in_array($posted('app_locale', 'en'), ['en', 'hi'], true) ? $posted('app_locale', 'en') : 'en',
            'supported_locales' => $posted('supported_locales', 'en,hi'),
            'about_us' => $posted('about_us'),
            'terms_conditions' => $posted('terms_conditions'),
            'privacy_policy' => $posted('privacy_policy'),
            'refund_policy' => $posted('refund_policy'),
            'shipping_policy' => $posted('shipping_policy'),
            'support_content' => $posted('support_content'),
            'public_business_name' => $posted('public_business_name', 'City Solutions'),
            'public_legal_entity' => $posted('public_legal_entity', 'City Solutions'),
            'public_support_email' => filter_var($posted('public_support_email'), FILTER_VALIDATE_EMAIL)
                ? $posted('public_support_email')
                : 'support@citysolutions.in',
            'public_support_phone' => $posted('public_support_phone'),
            'public_business_address' => $posted('public_business_address', 'Lucknow, India'),
            'medical_terms_conditions' => $posted('medical_terms_conditions'),
            'medical_privacy_policy' => $posted('medical_privacy_policy'),
            'medical_refund_policy' => $posted('medical_refund_policy'),
            'medical_prescription_policy' => $posted('medical_prescription_policy'),
            'hotel_cancellation_free_hours' => max(0, (int) $posted('hotel_cancellation_free_hours', '24')),
            'hotel_late_cancellation_refund_percent' => min(100, max(0, (float) $posted('hotel_late_cancellation_refund_percent', '50'))),
            'hotel_owner_commission_percent' => min(100, max(0, (float) $posted('hotel_owner_commission_percent', '10'))),
            'maintenance_mode' => $postedBool('maintenance_mode', 'maintenance_mode'),
            'maintenance_message' => $posted('maintenance_message'),
            'latest_app_version' => $posted('latest_app_version'),
            'force_update_version' => $posted('force_update_version'),
            'firebase_push_enabled' => $postedBool('firebase_push_enabled', 'firebase_push_enabled'),
            'firebase_auth_enabled' => $postedBool('firebase_auth_enabled', 'firebase_auth_enabled'),
            'firebase_phone_auth_enabled' => $postedBool('firebase_phone_auth_enabled', 'firebase_phone_auth_enabled'),
            'firebase_google_auth_enabled' => $postedBool('firebase_google_auth_enabled', 'firebase_google_auth_enabled'),
            'firebase_api_key' => $posted('firebase_api_key'),
            'firebase_auth_domain' => $posted('firebase_auth_domain'),
            'firebase_project_id' => $posted('firebase_project_id'),
            'firebase_sender_id' => $posted('firebase_sender_id'),
            'firebase_app_id' => $posted('firebase_app_id'),
            'firebase_storage_bucket' => $posted('firebase_storage_bucket'),
            'firebase_measurement_id' => $posted('firebase_measurement_id'),
            'firebase_server_key' => $postedSecret('firebase_server_key'),
            'firebase_service_account_json' => $this->jsonSecretFromPost('firebase_service_account_json', 'firebase_service_account_json', $current),
            'firebase_test_device_token' => $posted('firebase_test_device_token'),
            'floating_ad_enabled' => $postedBool('floating_ad_enabled', 'floating_ad_enabled'),
            'floating_ad_id' => $posted('floating_ad_id', 'default'),
            'floating_ad_title' => $posted('floating_ad_title'),
            'floating_ad_message' => $posted('floating_ad_message'),
            'floating_ad_image_url' => $this->cleanMediaUrl($posted('floating_ad_image_url')),
            'floating_ad_video_url' => $this->cleanMediaUrl($posted('floating_ad_video_url')),
            'floating_ad_cta_text' => $posted('floating_ad_cta_text'),
            'floating_ad_link_url' => $this->cleanOptionalUrl($posted('floating_ad_link_url')),
        ];

        foreach (['mart', 'ecommerce', 'medical', 'services', 'hotel', 'real_estate', 'restaurant'] as $moduleKey) {
            $values += $this->moduleSettingsFromPost($moduleKey, $current);
        }

        Settings::setMany($values);

        Response::redirect('/admin/settings');
    }

    public function testFirebase(): void
    {
        Auth::requireAdmin();
        $moduleKey = trim((string) ($_POST['module_key'] ?? 'global'));
        if (!in_array($moduleKey, ['global', 'mart', 'ecommerce', 'medical', 'services', 'hotel', 'real_estate', 'restaurant'], true)) {
            $moduleKey = 'global';
        }
        $token = trim($_POST['firebase_test_device_token'] ?? '');
        $settingKey = $moduleKey === 'global'
            ? 'firebase_test_device_token'
            : Settings::moduleSettingKey($moduleKey, 'firebase_test_device_token');
        Settings::setMany([$settingKey => $token]);

        $result = FirebasePush::sendToToken(
            $token,
            trim($_POST['title'] ?? 'City Solutions test'),
            trim($_POST['message'] ?? 'Firebase test notification from admin panel.'),
            [],
            $moduleKey === 'global' ? null : $moduleKey
        );

        View::render('admin/settings', [
            'title' => 'Settings',
            'settings' => Settings::all(),
            'pushResult' => $result,
            'pushModule' => $moduleKey,
        ]);
    }

    private function moduleSettingsFromPost(string $moduleKey, array $current): array
    {
        $prefix = $moduleKey . '_';
        if (($_POST['settings_scope'] ?? '') !== 'modules' || !array_key_exists($prefix . 'app_name', $_POST)) {
            return [];
        }
        $setting = static fn (string $key): string => Settings::moduleSettingKey($moduleKey, $key);
        $environment = in_array(trim($_POST[$prefix . 'online_payment_environment'] ?? 'test'), ['test', 'live'], true)
            ? trim($_POST[$prefix . 'online_payment_environment'] ?? 'test')
            : 'test';

        return [
            $setting('app_name') => trim($_POST[$prefix . 'app_name'] ?? ''),
            $setting('currency') => trim($_POST[$prefix . 'currency'] ?? 'INR'),
            $setting('currency_symbol') => trim($_POST[$prefix . 'currency_symbol'] ?? '₹'),
            $setting('minimum_order_amount') => max(0, (float) ($_POST[$prefix . 'minimum_order_amount'] ?? 0)),
            $setting('delivery_charge') => max(0, (float) ($_POST[$prefix . 'delivery_charge'] ?? 0)),
            $setting('cod_enabled') => isset($_POST[$prefix . 'cod_enabled']) ? '1' : '0',
            $setting('online_payment_enabled') => isset($_POST[$prefix . 'online_payment_enabled']) ? '1' : '0',
            $setting('manual_payment_enabled') => isset($_POST[$prefix . 'manual_payment_enabled']) ? '1' : '0',
            $setting('wallet_payment_enabled') => isset($_POST[$prefix . 'wallet_payment_enabled']) ? '1' : '0',
            $setting('online_payment_title') => trim($_POST[$prefix . 'online_payment_title'] ?? 'Online Payment'),
            $setting('online_payment_description') => trim($_POST[$prefix . 'online_payment_description'] ?? ''),
            $setting('online_payment_gateway') => $this->cleanGateway(trim($_POST[$prefix . 'online_payment_gateway'] ?? 'manual')),
            $setting('online_payment_public_key') => trim($_POST[$prefix . 'online_payment_public_key'] ?? ''),
            $setting('online_payment_secret_key') => $this->secretFromPost($prefix . 'online_payment_secret_key', $setting('online_payment_secret_key'), $current),
            $setting('online_payment_webhook_secret') => $this->secretFromPost($prefix . 'online_payment_webhook_secret', $setting('online_payment_webhook_secret'), $current),
            $setting('online_payment_capture_url') => $this->cleanOutboundUrl(trim($_POST[$prefix . 'online_payment_capture_url'] ?? '')),
            $setting('online_payment_status_url') => $this->cleanOutboundUrl(trim($_POST[$prefix . 'online_payment_status_url'] ?? '')),
            $setting('online_payment_refund_url') => $this->cleanOutboundUrl(trim($_POST[$prefix . 'online_payment_refund_url'] ?? '')),
            $setting('online_payment_auth_header') => $this->secretFromPost($prefix . 'online_payment_auth_header', $setting('online_payment_auth_header'), $current, fn (string $value): string => $this->cleanHeaderLine($value)),
            $setting('online_payment_merchant_id') => trim($_POST[$prefix . 'online_payment_merchant_id'] ?? ''),
            $setting('online_payment_environment') => $environment,
            $setting('online_payment_instructions') => trim($_POST[$prefix . 'online_payment_instructions'] ?? ''),
            $setting('bank_transfer_title') => trim($_POST[$prefix . 'bank_transfer_title'] ?? 'Bank Transfer'),
            $setting('bank_transfer_description') => trim($_POST[$prefix . 'bank_transfer_description'] ?? ''),
            $setting('bank_transfer_instructions') => trim($_POST[$prefix . 'bank_transfer_instructions'] ?? ''),
            $setting('app_locale') => trim($_POST[$prefix . 'app_locale'] ?? 'en'),
            $setting('supported_locales') => trim($_POST[$prefix . 'supported_locales'] ?? 'en,hi'),
            $setting('maintenance_mode') => isset($_POST[$prefix . 'maintenance_mode']) ? '1' : '0',
            $setting('maintenance_message') => trim($_POST[$prefix . 'maintenance_message'] ?? ''),
            $setting('latest_app_version') => trim($_POST[$prefix . 'latest_app_version'] ?? ''),
            $setting('force_update_version') => trim($_POST[$prefix . 'force_update_version'] ?? ''),
            $setting('firebase_push_enabled') => isset($_POST[$prefix . 'firebase_push_enabled']) ? '1' : '0',
            $setting('firebase_project_id') => trim($_POST[$prefix . 'firebase_project_id'] ?? ''),
            $setting('firebase_sender_id') => trim($_POST[$prefix . 'firebase_sender_id'] ?? ''),
            $setting('firebase_server_key') => $this->secretFromPost($prefix . 'firebase_server_key', $setting('firebase_server_key'), $current),
            $setting('firebase_service_account_json') => $this->jsonSecretFromPost($prefix . 'firebase_service_account_json', $setting('firebase_service_account_json'), $current),
            $setting('firebase_test_device_token') => trim($_POST[$prefix . 'firebase_test_device_token'] ?? ''),
            $setting('about_us') => trim($_POST[$prefix . 'about_us'] ?? ''),
            $setting('terms_conditions') => trim($_POST[$prefix . 'terms_conditions'] ?? ''),
            $setting('privacy_policy') => trim($_POST[$prefix . 'privacy_policy'] ?? ''),
            $setting('refund_policy') => trim($_POST[$prefix . 'refund_policy'] ?? ''),
            $setting('shipping_policy') => trim($_POST[$prefix . 'shipping_policy'] ?? ''),
            $setting('support_content') => trim($_POST[$prefix . 'support_content'] ?? ''),
        ];
    }

    private function cleanOutboundUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        return Security::validateOutboundHttpsUrl($value) === null ? $value : '';
    }

    private function cleanGateway(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace([' ', '-'], '_', $value);
        $allowed = [
            'manual',
            'razorpay',
            'razor_pay',
            'stripe',
            'paypal',
            'paytm',
            'payu',
            'cashfree',
            'phonepe',
            'ccavenue',
            'paystack',
            'flutterwave',
            'ssl_commerz',
            'bkash',
            'mercadopago',
            'custom',
        ];
        return in_array($value, $allowed, true) ? $value : 'manual';
    }

    private function jsonSecretFromPost(string $postKey, string $settingKey, array $current): string
    {
        $value = trim((string) ($_POST[$postKey] ?? ''));
        if ($value === '') {
            return (string) ($current[$settingKey] ?? '');
        }
        json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return (string) ($current[$settingKey] ?? '');
        }
        return $value;
    }

    private function cleanMediaUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, '/uploads/')) {
            return $value;
        }
        return Security::validateOutboundHttpsUrl($value) === null ? $value : '';
    }

    private function cleanOptionalUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, "\r") || str_contains($value, "\n")) {
            return '';
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $value) !== 1) {
            return $value;
        }
        if (str_starts_with(strtolower($value), 'https://')) {
            return Security::validateOutboundHttpsUrl($value) === null ? $value : '';
        }
        return in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?: ''), ['citysolutions', 'mailto', 'tel'], true)
            ? $value
            : '';
    }

    private function cleanHeaderLine(string $value): string
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, "\r") || str_contains($value, "\n")) {
            return '';
        }
        if (strlen($value) > 1000) {
            return '';
        }
        return preg_match('/^[A-Za-z0-9-]+:\s*[^\x00-\x1F\x7F]+$/', $value) === 1 ? $value : '';
    }

    private function secretFromPost(string $postKey, string $settingKey, array $current, ?callable $cleaner = null): string
    {
        $posted = trim((string) ($_POST[$postKey] ?? ''));
        if ($posted === '') {
            return (string) ($current[$settingKey] ?? '');
        }
        return $cleaner === null ? $posted : (string) $cleaner($posted);
    }
}
