<?php

declare(strict_types=1);

namespace App\Support;

final class Env
{
    private static array $values = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$values === []) {
            self::load();
        }

        return self::$values[$key] ?? $default;
    }

    private static function load(): void
    {
        $path = dirname(__DIR__, 2) . '/.env';
        if (!is_file($path)) {
            $path = dirname(__DIR__, 2) . '/.env.example';
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            self::$values[trim($key)] = trim(trim($value), '"\'');
        }
    }
}
