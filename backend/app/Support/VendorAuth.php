<?php

declare(strict_types=1);

namespace App\Support;

final class VendorAuth
{
    public static function check(): bool
    {
        return isset($_SESSION['vendor_id']);
    }

    public static function id(): int
    {
        return (int) ($_SESSION['vendor_id'] ?? 0);
    }

    public static function moduleKey(): string
    {
        $moduleKey = (string) ($_SESSION['vendor_module_key'] ?? 'mart');
        return in_array($moduleKey, ['mart', 'ecommerce', 'medical'], true) ? $moduleKey : 'mart';
    }

    public static function requireVendor(): void
    {
        if (!self::check()) {
            Response::redirect('/vendor/login');
        }
    }
}
