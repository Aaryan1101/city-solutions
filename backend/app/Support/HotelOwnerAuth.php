<?php

declare(strict_types=1);

namespace App\Support;

final class HotelOwnerAuth
{
    public static function check(): bool
    {
        return isset($_SESSION['hotel_owner_id']);
    }

    public static function id(): int
    {
        return (int) ($_SESSION['hotel_owner_id'] ?? 0);
    }

    public static function requireOwner(): void
    {
        if (!self::check()) {
            Response::redirect('/hotel-owner/login');
        }
    }
}
