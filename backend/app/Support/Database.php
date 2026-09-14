<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $connection = Env::get('DB_CONNECTION', 'sqlite');
        if ($connection === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Env::get('DB_HOST', 'localhost'),
                Env::get('DB_PORT', '3306'),
                Env::get('DB_DATABASE', '')
            );
            self::$pdo = new PDO($dsn, Env::get('DB_USERNAME', ''), Env::get('DB_PASSWORD', ''), self::options());
        } else {
            $database = Env::get('DB_DATABASE', 'backend/database/local.sqlite');
            $path = str_starts_with($database, '/') ? $database : dirname(__DIR__, 3) . '/' . $database;
            self::$pdo = new PDO('sqlite:' . $path, null, null, self::options());
        }

        return self::$pdo;
    }

    private static function options(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
    }
}
