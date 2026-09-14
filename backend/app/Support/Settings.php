<?php

declare(strict_types=1);

namespace App\Support;

final class Settings
{
    private const DEFAULTS = [
        'app_name' => 'City Solutions Mart',
        'currency' => 'INR',
        'currency_symbol' => '₹',
        'minimum_order_amount' => '0',
        'delivery_charge' => '0',
        'cod_enabled' => '1',
        'online_payment_enabled' => '1',
        'manual_payment_enabled' => '1',
        'wallet_payment_enabled' => '1',
        'online_payment_title' => 'Online Payment',
        'online_payment_description' => 'Pay using the configured online gateway and submit the transaction reference.',
        'online_payment_gateway' => 'manual',
        'online_payment_public_key' => '',
        'online_payment_secret_key' => '',
        'online_payment_webhook_secret' => '',
        'online_payment_capture_url' => '',
        'online_payment_status_url' => '',
        'online_payment_refund_url' => '',
        'online_payment_auth_header' => '',
        'online_payment_merchant_id' => '',
        'online_payment_environment' => 'test',
        'online_payment_instructions' => 'Complete payment through the configured gateway and enter the reference number.',
        'bank_transfer_title' => 'Bank Transfer',
        'bank_transfer_description' => 'Transfer to the configured bank/UPI account and submit the reference.',
        'bank_transfer_instructions' => '',
        'api_rate_limit_per_minute' => '60',
        'panel_session_timeout_minutes' => '120',
        'support_poll_interval_seconds' => '15',
        'support_realtime_provider' => 'polling',
        'app_locale' => 'en',
        'supported_locales' => 'en,hi',
        'vendor_commission_percent' => '10',
        'about_us' => 'City Solutions connects local customers with commerce, mart, medical, services, hotel, restaurant and real estate partners through one city platform.',
        'terms_conditions' => 'Orders and bookings are subject to stock, zone coverage, partner availability, price verification, payment verification, prescription review where applicable and operational feasibility. Misuse of refunds, wallets, coupons, complaints, prescriptions, reviews or support tools may lead to rejection, reversal, cancellation or account restrictions.',
        'privacy_policy' => 'City Solutions uses account, address, location, order, booking, payment reference, wallet, support, complaint, prescription and device-token data to operate the platform, process transactions, route services, prevent fraud, provide support and comply with law. Data may be shared with relevant vendors, providers, hotels, restaurants, agents, payment providers, notification providers and legal authorities where required. We do not sell personal or sensitive user data.',
        'refund_policy' => 'Refunds and cancellations are reviewed by admin according to order or booking status, payment status, product or service type, partner policy, customer evidence and applicable law. Approved refunds may be credited to wallet or original payment method depending on payment configuration and reconciliation.',
        'shipping_policy' => 'Delivery, service and booking timelines depend on zone coverage, partner capacity, stock, route conditions, customer availability, weather, payment verification and operational constraints. Taxes, delivery charges, service charges and partner-specific rules may apply.',
        'support_content' => 'Use support, chat or complaint options with the correct order/booking number, evidence and contact details. Abuse, false claims or unsafe content may be restricted.',
        'public_business_name' => 'City Solutions',
        'public_legal_entity' => 'City Solutions',
        'public_support_email' => 'support@citysolutions.in',
        'public_support_phone' => '',
        'public_business_address' => 'Lucknow, India',
        'medical_terms_conditions' => 'Medicine orders are subject to prescription validation, pharmacy/admin review, stock, quantity limits, age confirmation where required, zone availability and applicable pharmacy law. Restricted, habit-forming, narcotic, psychotropic, Schedule H/H1/X-like or unsafe online-sale products may be rejected or blocked.',
        'medical_privacy_policy' => 'Prescription images and medical order details are sensitive information and are used only for prescription review, medicine fulfillment, support, dispute handling, safety checks and legally required records. Access should be limited to authorized pharmacy/admin personnel.',
        'medical_refund_policy' => 'Prescription medicines, opened products, cold-chain products, hygiene-sensitive products, restricted medicines and correctly supplied medicines may be non-returnable except where required by law or where the wrong, damaged, expired or unsafe item was supplied.',
        'medical_prescription_policy' => 'Prescription-required medicines are fulfilled only after valid prescription upload and pharmacist/admin approval. Invalid, altered, mismatched, expired or suspicious prescriptions may be rejected and the order may be cancelled or escalated.',
        'hotel_cancellation_free_hours' => '24',
        'hotel_late_cancellation_refund_percent' => '50',
        'hotel_owner_commission_percent' => '10',
        'maintenance_mode' => '0',
        'maintenance_message' => 'We are improving the mart experience. Please check back shortly.',
        'latest_app_version' => '',
        'force_update_version' => '',
        'firebase_push_enabled' => '0',
        'firebase_auth_enabled' => '0',
        'firebase_phone_auth_enabled' => '0',
        'firebase_google_auth_enabled' => '0',
        'firebase_api_key' => '',
        'firebase_auth_domain' => '',
        'firebase_project_id' => '',
        'firebase_sender_id' => '',
        'firebase_app_id' => '',
        'firebase_storage_bucket' => '',
        'firebase_measurement_id' => '',
        'firebase_server_key' => '',
        'firebase_service_account_json' => '',
        'firebase_test_device_token' => '',
        'floating_ad_enabled' => '0',
        'floating_ad_id' => 'default',
        'floating_ad_title' => '',
        'floating_ad_message' => '',
        'floating_ad_image_url' => '',
        'floating_ad_video_url' => '',
        'floating_ad_cta_text' => '',
        'floating_ad_link_url' => '',
    ];

    public static function all(): array
    {
        self::ensureTable();
        $rows = Database::connection()->query('select key_name, value from settings')->fetchAll();
        $settings = self::DEFAULTS;
        foreach ($rows as $row) {
            $settings[$row['key_name']] = $row['value'];
        }

        return $settings;
    }

    public static function get(string $key, ?string $default = null): string
    {
        $settings = self::all();
        return (string) ($settings[$key] ?? $default ?? '');
    }

    public static function moduleGet(string $moduleKey, string $key, ?string $default = null): string
    {
        $settings = self::all();
        $moduleSetting = self::moduleSettingKey($moduleKey, $key);
        return (string) ($settings[$moduleSetting] ?? $settings[$key] ?? $default ?? '');
    }

    public static function float(string $key, float $default = 0): float
    {
        return (float) self::get($key, (string) $default);
    }

    public static function moduleFloat(string $moduleKey, string $key, float $default = 0): float
    {
        return (float) self::moduleGet($moduleKey, $key, (string) $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return in_array(self::get($key, $default ? '1' : '0'), ['1', 'true', 'yes', 'on'], true);
    }

    public static function moduleBool(string $moduleKey, string $key, bool $default = false): bool
    {
        return in_array(self::moduleGet($moduleKey, $key, $default ? '1' : '0'), ['1', 'true', 'yes', 'on'], true);
    }

    public static function moduleSettingKey(string $moduleKey, string $key): string
    {
        return preg_replace('/[^a-z0-9_]/', '_', strtolower($moduleKey)) . '_' . $key;
    }

    public static function setMany(array $values): void
    {
        self::ensureTable();
        $db = Database::connection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'mysql'
            ? 'insert into settings (key_name, value, updated_at) values (:key_name, :value, CURRENT_TIMESTAMP) on duplicate key update value = values(value), updated_at = CURRENT_TIMESTAMP'
            : 'insert into settings (key_name, value, updated_at) values (:key_name, :value, CURRENT_TIMESTAMP) on conflict(key_name) do update set value = excluded.value, updated_at = CURRENT_TIMESTAMP';
        $stmt = $db->prepare($sql);
        foreach ($values as $key => $value) {
            $stmt->execute([
                'key_name' => $key,
                'value' => (string) $value,
            ]);
        }
    }

    private static function ensureTable(): void
    {
        Database::connection()->exec(
            'create table if not exists settings (
                key_name varchar(120) primary key,
                value text null,
                updated_at timestamp null
            )'
        );
    }
}
