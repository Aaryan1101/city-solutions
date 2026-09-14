<?php

declare(strict_types=1);

namespace App\Support;

final class Security
{
    public static function sendHeaders(string $path): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Cross-Origin-Resource-Policy: same-origin');

        if (!str_starts_with($path, '/uploads/')) {
            header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; media-src 'self' https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self' https:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        }

        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function clientIp(): string
    {
        $candidate = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'unknown';

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $first = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                $candidate = $first;
            }
        }

        $candidate = trim((string) $candidate);
        return filter_var($candidate, FILTER_VALIDATE_IP) ? $candidate : 'unknown';
    }

    public static function enforceLoginThrottle(string $scope, string $identifier): void
    {
        $identifier = strtolower(trim($identifier));
        $key = $scope . '|' . self::clientIp() . '|' . $identifier;
        RateLimiter::enforceKeyLimit('login', $key, 8, 900, 'Too many login attempts. Please wait 15 minutes and try again.');
    }

    public static function safeRedirect(string $path, string $fallback = '/admin'): string
    {
        $path = trim($path);
        if ($path === '' || str_contains($path, "\r") || str_contains($path, "\n")) {
            return $fallback;
        }
        $parsed = parse_url($path);
        if (isset($parsed['scheme']) || isset($parsed['host'])) {
            return $fallback;
        }
        return str_starts_with($path, '/') ? $path : $fallback;
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    public static function validateOutboundHttpsUrl(string $url): ?string
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return 'Invalid URL.';
        }
        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return 'Only HTTPS URLs are allowed.';
        }
        $host = strtolower(trim((string) ($parts['host'] ?? '')));
        if ($host === '') {
            return 'URL host is required.';
        }
        $blockedHosts = ['localhost', 'localhost.localdomain'];
        if (in_array($host, $blockedHosts, true) || str_ends_with($host, '.localhost')) {
            return 'Local hosts are not allowed.';
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $isPublic = filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
            if ($isPublic === false) {
                return 'Private or reserved IP addresses are not allowed.';
            }
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'URL credentials are not allowed.';
        }
        return null;
    }
}
