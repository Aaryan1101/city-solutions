<?php

declare(strict_types=1);

namespace App\Support;

final class Request
{
    public static function json(): array
    {
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($length > 2 * 1024 * 1024) {
            Response::json(['message' => 'Request payload is too large.'], 413);
            exit;
        }
        $raw = file_get_contents('php://input') ?: '';
        if (strlen($raw) > 2 * 1024 * 1024) {
            Response::json(['message' => 'Request payload is too large.'], 413);
            exit;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
