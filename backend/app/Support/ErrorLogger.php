<?php

declare(strict_types=1);

namespace App\Support;

final class ErrorLogger
{
    public static function log(\Throwable $error): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = json_encode([
            'time' => date('c'),
            'path' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ], JSON_UNESCAPED_SLASHES);

        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line . PHP_EOL, FILE_APPEND);
    }
}
