<?php

declare(strict_types=1);

namespace App\Support;

final class ServiceProviderAuth
{
    public static function check(): bool
    {
        return isset($_SESSION['service_provider_id']);
    }

    public static function id(): int
    {
        return (int) ($_SESSION['service_provider_id'] ?? 0);
    }

    public static function requireProvider(): void
    {
        if (!self::check()) {
            Response::redirect('/service-provider/login');
        }
    }
}
