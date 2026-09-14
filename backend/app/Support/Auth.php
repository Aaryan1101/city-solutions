<?php

declare(strict_types=1);

namespace App\Support;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            Response::redirect('/admin/login');
        }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    public static function enforcePanelSecurity(string $path, string $method): void
    {
        if (!self::isPanelPath($path)) {
            return;
        }
        if ($method === 'POST' && !self::validCsrf($_POST['_csrf_token'] ?? '')) {
            Response::json(['message' => 'Invalid session token. Please refresh and try again.'], 419);
            exit;
        }
        if (str_starts_with($path, '/admin') && !in_array($path, ['/admin/login', '/admin/logout'], true)) {
            self::requireAdmin();
            self::enforceSessionFreshness($path);
            if (!self::canAccessAdminPath($path)) {
                Response::json(['message' => 'Permission denied'], 403);
                exit;
            }
            return;
        }
        if (self::isAuthenticatedPanelPath($path)) {
            self::enforceSessionFreshness($path);
        }
    }

    public static function role(): string
    {
        return (string) ($_SESSION['admin_role'] ?? 'super_admin');
    }

    public static function zoneId(): ?int
    {
        $zoneId = (int) ($_SESSION['admin_zone_id'] ?? 0);
        return $zoneId > 0 ? $zoneId : null;
    }

    public static function isZoneScoped(): bool
    {
        return self::role() !== 'super_admin' && self::zoneId() !== null;
    }

    public static function zoneWhere(string $alias): string
    {
        return self::isZoneScoped() ? ' and ' . $alias . '.zone_id = :admin_zone_id' : '';
    }

    public static function zoneParams(array $params = []): array
    {
        if (!self::isZoneScoped()) {
            return $params;
        }
        $params['admin_zone_id'] = self::zoneId();
        return $params;
    }

    private static function validCsrf(string $token): bool
    {
        return $token !== '' && isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    private static function isPanelPath(string $path): bool
    {
        return str_starts_with($path, '/admin')
            || str_starts_with($path, '/vendor')
            || str_starts_with($path, '/service-provider')
            || str_starts_with($path, '/hotel-owner')
            || str_starts_with($path, '/real-estate-agent');
    }

    private static function isAuthenticatedPanelPath(string $path): bool
    {
        return !in_array($path, [
            '/admin/login',
            '/vendor/login',
            '/service-provider/login',
            '/hotel-owner/login',
            '/real-estate-agent/login',
        ], true);
    }

    private static function enforceSessionFreshness(string $path): void
    {
        $timeoutSeconds = max(900, (int) Settings::get('panel_session_timeout_minutes', '120') * 60);
        $lastActivity = (int) ($_SESSION['last_activity_at'] ?? time());
        if ((time() - $lastActivity) > $timeoutSeconds) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
            }
            session_destroy();
            Response::redirect(self::loginPathFor($path));
        }
        $_SESSION['last_activity_at'] = time();
    }

    private static function loginPathFor(string $path): string
    {
        if (str_starts_with($path, '/vendor')) {
            return '/vendor/login';
        }
        if (str_starts_with($path, '/service-provider')) {
            return '/service-provider/login';
        }
        if (str_starts_with($path, '/hotel-owner')) {
            return '/hotel-owner/login';
        }
        if (str_starts_with($path, '/real-estate-agent')) {
            return '/real-estate-agent/login';
        }

        return '/admin/login';
    }

    private static function canAccessAdminPath(string $path): bool
    {
        $role = self::role();
        if ($role === 'super_admin') {
            return true;
        }
        if ($role === 'medical_manager') {
            return str_starts_with($path, '/admin/medical') || $path === '/admin';
        }
        if ($role === 'hotel_manager') {
            return str_starts_with($path, '/admin/hotels') || $path === '/admin';
        }
        if ($role === 'support') {
            return str_starts_with($path, '/admin/support')
                || str_starts_with($path, '/admin/complaints')
                || $path === '/admin';
        }
        if ($role === 'viewer') {
            return $_SERVER['REQUEST_METHOD'] === 'GET';
        }
        if ($role === 'zone_manager') {
            return !str_starts_with($path, '/admin/admin-users')
                && !str_starts_with($path, '/admin/settings')
                && !str_starts_with($path, '/admin/health')
                && !str_starts_with($path, '/admin/zones');
        }

        return false;
    }
}
