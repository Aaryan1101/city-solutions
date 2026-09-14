<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\FloatingAdSchema;
use App\Support\Response;
use App\Support\Security;
use App\Support\Upload;
use App\Support\View;

final class FloatingAdController
{
    public function index(): void
    {
        Auth::requireAdmin();
        FloatingAdSchema::ensure();
        $db = Database::connection();
        $ads = $db->query('select * from floating_ads order by priority desc, id desc')->fetchAll();
        View::render('admin/floating_ads', [
            'title' => 'Floating Ads',
            'ads' => $ads,
            'products' => $db->query('select id, module_key, name from products order by module_key asc, name asc limit 500')->fetchAll(),
            'vendors' => $db->query('select id, module_key, shop_name from vendors order by module_key asc, shop_name asc limit 500')->fetchAll(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        FloatingAdSchema::ensure();
        $media = Upload::media('media', 'floating_ads');
        $stmt = Database::connection()->prepare(
            'insert into floating_ads
            (title, message, media_type, media_url, cta_text, target_module, target_type, target_value, link_url, status, priority, starts_at, ends_at, created_at, updated_at)
            values
            (:title, :message, :media_type, :media_url, :cta_text, :target_module, :target_type, :target_value, :link_url, :status, :priority, :starts_at, :ends_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->payload($media));
        Response::redirect('/admin/floating-ads');
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        FloatingAdSchema::ensure();
        $current = Database::connection()->prepare('select * from floating_ads where id = :id limit 1');
        $current->execute(['id' => $id]);
        $ad = $current->fetch();
        if (!$ad) {
            Response::redirect('/admin/floating-ads');
        }

        $media = Upload::media('media', 'floating_ads') ?? [
            'path' => (string) ($ad['media_url'] ?? ''),
            'media_type' => (string) ($ad['media_type'] ?? 'image'),
        ];
        $payload = $this->payload($media);
        $payload['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update floating_ads set
                title = :title, message = :message, media_type = :media_type, media_url = :media_url,
                cta_text = :cta_text, target_module = :target_module, target_type = :target_type,
                target_value = :target_value, link_url = :link_url, status = :status, priority = :priority,
                starts_at = :starts_at, ends_at = :ends_at, updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute($payload);
        Response::redirect('/admin/floating-ads');
    }

    public function delete(int $id): void
    {
        Auth::requireAdmin();
        FloatingAdSchema::ensure();
        $stmt = Database::connection()->prepare('delete from floating_ads where id = :id');
        $stmt->execute(['id' => $id]);
        Response::redirect('/admin/floating-ads');
    }

    private function payload(?array $media): array
    {
        [$targetModule, $targetType, $targetValue] = $this->targetFromPost();
        if (!in_array($targetModule, ['mart', 'ecommerce', 'medical', 'services', 'hotel', 'global'], true)) {
            $targetModule = 'mart';
        }
        if (!in_array($targetType, ['none', 'product', 'store', 'url'], true)) {
            $targetType = 'none';
        }

        return [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'message' => trim((string) ($_POST['message'] ?? '')),
            'media_type' => (string) ($media['media_type'] ?? 'image'),
            'media_url' => (string) ($media['path'] ?? ''),
            'cta_text' => trim((string) ($_POST['cta_text'] ?? 'Explore')),
            'target_module' => $targetModule,
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'link_url' => $this->cleanOptionalUrl((string) ($_POST['link_url'] ?? '')),
            'status' => (int) ($_POST['status'] ?? 1),
            'priority' => (int) ($_POST['priority'] ?? 0),
            'starts_at' => $this->dateTimeOrNull((string) ($_POST['starts_at'] ?? '')),
            'ends_at' => $this->dateTimeOrNull((string) ($_POST['ends_at'] ?? '')),
        ];
    }

    private function targetFromPost(): array
    {
        $targetRef = trim((string) ($_POST['target_ref'] ?? ''));
        if ($targetRef !== '') {
            $parts = explode('|', $targetRef, 3);
            if (count($parts) === 3) {
                $targetType = trim($parts[1]);
                return [
                    trim($parts[0]),
                    $targetType,
                    $targetType === 'url'
                        ? trim((string) ($_POST['target_value'] ?? ''))
                        : trim($parts[2]),
                ];
            }
        }

        return [
            trim((string) ($_POST['target_module'] ?? 'mart')),
            trim((string) ($_POST['target_type'] ?? 'none')),
            trim((string) ($_POST['target_value'] ?? '')),
        ];
    }

    private function dateTimeOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return str_replace('T', ' ', $value);
    }

    private function cleanOptionalUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, "\r") || str_contains($value, "\n")) {
            return '';
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $value) !== 1) {
            return $value;
        }
        if (str_starts_with(strtolower($value), 'https://')) {
            return Security::validateOutboundHttpsUrl($value) === null ? $value : '';
        }
        return in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?: ''), ['citysolutions', 'mailto', 'tel'], true)
            ? $value
            : '';
    }
}
