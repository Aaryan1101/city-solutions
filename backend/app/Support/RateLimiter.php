<?php

declare(strict_types=1);

namespace App\Support;

final class RateLimiter
{
    public static function enforceApiWriteLimit(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (!str_starts_with($path, '/api/') || in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $limit = max(10, (int) Settings::get('api_rate_limit_per_minute', '60'));
        $ip = Security::clientIp();
        self::enforceKeyLimit(
            'api',
            $ip,
            $limit,
            60,
            'Too many requests. Please try again shortly.'
        );
    }

    public static function enforceKeyLimit(string $scope, string $key, int $limit, int $windowSeconds, string $message): void
    {
        $limit = max(1, $limit);
        $windowSeconds = max(60, $windowSeconds);
        $bucket = (string) floor(time() / $windowSeconds);
        $dir = dirname(__DIR__, 2) . '/storage/rate-limit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            Response::json(['message' => 'Rate limiter storage is not writable.'], 503);
            exit;
        }
        self::cleanup($dir);
        $safeKey = hash('sha256', $scope . '|' . $key . '|' . $bucket);
        $file = $dir . '/' . $safeKey . '.count';
        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            Response::json(['message' => 'Rate limiter storage is not writable.'], 503);
            exit;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                Response::json(['message' => 'Rate limiter is busy. Please retry shortly.'], 503);
                exit;
            }
            $raw = stream_get_contents($handle);
            $count = max(0, (int) $raw) + 1;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) $count);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if ($count > $limit) {
            header('Retry-After: ' . $windowSeconds);
            Response::json(['message' => $message], 429);
            exit;
        }
    }

    private static function cleanup(string $dir): void
    {
        if (random_int(1, 100) !== 1) {
            return;
        }
        $threshold = time() - 7200;
        foreach (glob($dir . '/*.count') ?: [] as $file) {
            if (is_file($file) && (filemtime($file) ?: time()) < $threshold) {
                @unlink($file);
            }
        }
    }
}
