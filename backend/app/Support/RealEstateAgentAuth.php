<?php

declare(strict_types=1);

namespace App\Support;

final class RealEstateAgentAuth
{
    public static function check(): bool
    {
        return isset($_SESSION['real_estate_agent_id']);
    }

    public static function id(): int
    {
        return (int) ($_SESSION['real_estate_agent_id'] ?? 0);
    }

    public static function requireAgent(): void
    {
        if (!self::check()) {
            Response::redirect('/real-estate-agent/login');
        }
    }
}
