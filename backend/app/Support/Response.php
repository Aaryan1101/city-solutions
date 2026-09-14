<?php

declare(strict_types=1);

namespace App\Support;

final class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . Security::safeRedirect($path, '/admin/login'));
        exit;
    }
}
