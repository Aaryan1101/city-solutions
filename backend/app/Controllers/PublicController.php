<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Database;
use App\Support\ProductExtrasSchema;

final class PublicController
{
    public function page(string $page = 'home'): void
    {
        $allowed = [
            'home',
            'about',
            'trust-safety',
            'privacy-policy',
            'terms',
            'refund-cancellation',
            'medical-compliance',
            'account-deletion',
            'contact',
        ];
        if (!in_array($page, $allowed, true)) {
            http_response_code(404);
            $page = 'not-found';
        }

        $catalogPreview = $page === 'home' ? $this->catalogPreview() : [];
        $viewPath = dirname(__DIR__, 2) . '/resources/views/public/' . $page . '.php';
        if (!is_file($viewPath)) {
            http_response_code(404);
            $viewPath = dirname(__DIR__, 2) . '/resources/views/public/not-found.php';
        }

        require dirname(__DIR__, 2) . '/resources/views/layouts/public.php';
    }

    private function catalogPreview(): array
    {
        try {
            ProductExtrasSchema::ensure();
            $db = Database::connection();
            $categories = $db->query(
                "select id, module_key, name from categories where status = 1 and module_key in ('mart','ecommerce','medical') order by module_key, sort_order asc, id desc"
            )->fetchAll();
            $subcategories = $db->query(
                "select category_id, name from subcategories where status = 1 and module_key in ('mart','ecommerce','medical') order by sort_order asc, id desc"
            )->fetchAll();
            $children = [];
            foreach ($subcategories as $subcategory) {
                $children[(int) $subcategory['category_id']][] = (string) $subcategory['name'];
            }
            return array_map(static function (array $category) use ($children): array {
                $category['subcategories'] = array_slice($children[(int) $category['id']] ?? [], 0, 6);
                return $category;
            }, $categories);
        } catch (\Throwable) {
            return [];
        }
    }
}
