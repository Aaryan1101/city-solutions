<?php

declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';
        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/layouts/admin.php';
        $html = (string) ob_get_clean();
        $token = Auth::csrfToken();
        echo preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches) use ($token): string {
                $attributes = $matches[1] ?? '';
                if (preg_match('/\bmethod\s*=\s*["\']?get\b/i', $attributes) === 1) {
                    return $matches[0];
                }
                if (str_contains($attributes, '_csrf_injected')) {
                    return $matches[0];
                }
                return '<form' . $attributes . '><input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
            },
            $html
        );
    }
}
