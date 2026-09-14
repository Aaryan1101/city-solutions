<?php

declare(strict_types=1);

namespace App\Support;

final class PaymentMethodCatalog
{
    public static function enabled(?string $moduleKey = null): array
    {
        $methods = [];
        if (self::bool($moduleKey, 'cod_enabled', true)) {
            $methods[] = self::format('cash_on_delivery', $moduleKey);
        }
        if (self::bool($moduleKey, 'online_payment_enabled', true)) {
            $methods[] = self::format('online_payment', $moduleKey);
        }
        if (self::bool($moduleKey, 'manual_payment_enabled', true)) {
            $methods[] = self::format('bank_transfer', $moduleKey);
        }
        if (self::bool($moduleKey, 'wallet_payment_enabled', true)) {
            $methods[] = self::format('wallet', $moduleKey);
        }

        return $methods;
    }

    public static function enabledForServices(?string $moduleKey = 'services'): array
    {
        $methods = [];
        if (self::bool($moduleKey, 'cod_enabled', true)) {
            $methods[] = [
                'id' => 'cash_on_service',
                'title' => 'Cash On Service',
                'description' => 'Pay after the service is completed.',
                'requires_reference' => false,
            ];
        }
        if (self::bool($moduleKey, 'online_payment_enabled', true)) {
            $methods[] = self::format('online_payment', $moduleKey);
        }
        if (self::bool($moduleKey, 'manual_payment_enabled', true)) {
            $methods[] = self::format('bank_transfer', $moduleKey);
        }

        return $methods;
    }

    public static function ids(?string $moduleKey = null): array
    {
        return array_map(static fn (array $method): string => $method['id'], self::enabled($moduleKey));
    }

    public static function isEnabled(string $method, ?string $moduleKey = null): bool
    {
        return in_array($method, self::ids($moduleKey), true);
    }

    public static function requiresReference(string $method): bool
    {
        return in_array($method, ['online_payment', 'bank_transfer'], true);
    }

    public static function paymentStatus(string $method): string
    {
        return match ($method) {
            'cash_on_delivery' => 'unpaid',
            'wallet' => 'paid',
            default => 'pending_verification',
        };
    }

    private static function format(string $id, ?string $moduleKey = null): array
    {
        return match ($id) {
            'cash_on_delivery' => [
                'id' => 'cash_on_delivery',
                'title' => 'Cash on Delivery',
                'description' => 'Pay in cash when your order arrives.',
                'requires_reference' => false,
            ],
            'online_payment' => [
                'id' => 'online_payment',
                'title' => self::get($moduleKey, 'online_payment_title', 'Online Payment'),
                'description' => self::get($moduleKey, 'online_payment_description', 'Pay using the configured online gateway and submit the transaction reference.'),
                'requires_reference' => true,
                'gateway' => self::get($moduleKey, 'online_payment_gateway', 'manual'),
                'public_key' => self::get($moduleKey, 'online_payment_public_key', ''),
                'environment' => self::get($moduleKey, 'online_payment_environment', 'test'),
                'merchant_id' => self::get($moduleKey, 'online_payment_merchant_id', ''),
                'instructions' => self::get($moduleKey, 'online_payment_instructions', ''),
            ],
            'bank_transfer' => [
                'id' => 'bank_transfer',
                'title' => self::get($moduleKey, 'bank_transfer_title', 'Bank Transfer'),
                'description' => self::get($moduleKey, 'bank_transfer_description', 'Transfer to the configured bank/UPI account and submit the reference.'),
                'requires_reference' => true,
                'gateway' => 'manual',
                'public_key' => '',
                'instructions' => self::get($moduleKey, 'bank_transfer_instructions', ''),
            ],
            default => [
                'id' => 'wallet',
                'title' => 'Wallet',
                'description' => 'Use available refund credit from your wallet.',
                'requires_reference' => false,
            ],
        };
    }

    private static function get(?string $moduleKey, string $key, string $default = ''): string
    {
        return $moduleKey === null ? Settings::get($key, $default) : Settings::moduleGet($moduleKey, $key, $default);
    }

    private static function bool(?string $moduleKey, string $key, bool $default = false): bool
    {
        return $moduleKey === null ? Settings::bool($key, $default) : Settings::moduleBool($moduleKey, $key, $default);
    }
}
