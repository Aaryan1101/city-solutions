<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\FloatingAdSchema;
use App\Support\Response;
use App\Support\Settings;

final class AppConfigController
{
    public function show(): void
    {
        FloatingAdSchema::ensure();
        $ad = $this->activeFloatingAd();
        if ($ad !== null) {
            Response::json(['floating_ad' => $ad]);
            return;
        }

        $settings = Settings::all();
        Response::json([
            'floating_ad' => [
                'enabled' => ($settings['floating_ad_enabled'] ?? '0') === '1',
                'id' => (string) ($settings['floating_ad_id'] ?? 'default'),
                'title' => (string) ($settings['floating_ad_title'] ?? ''),
                'message' => (string) ($settings['floating_ad_message'] ?? ''),
                'image_url' => (string) ($settings['floating_ad_image_url'] ?? ''),
                'video_url' => (string) ($settings['floating_ad_video_url'] ?? ''),
                'cta_text' => (string) ($settings['floating_ad_cta_text'] ?? ''),
                'link_url' => (string) ($settings['floating_ad_link_url'] ?? ''),
                'target_module' => 'global',
                'target_type' => 'url',
                'target_value' => '',
            ],
        ]);
    }

    private function activeFloatingAd(): ?array
    {
        $db = Database::connection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $random = $driver === 'mysql' ? 'rand()' : 'random()';
        $stmt = $db->query(
            'select * from floating_ads
             where status = 1
               and (starts_at is null or starts_at = \'\' or starts_at <= CURRENT_TIMESTAMP)
               and (ends_at is null or ends_at = \'\' or ends_at >= CURRENT_TIMESTAMP)
             order by priority desc, ' . $random . '
             limit 1'
        );
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $mediaUrl = (string) ($row['media_url'] ?? '');
        $mediaType = (string) ($row['media_type'] ?? 'image');
        return [
            'enabled' => true,
            'id' => 'floating-ad-' . (string) $row['id'],
            'title' => (string) ($row['title'] ?? ''),
            'message' => (string) ($row['message'] ?? ''),
            'image_url' => $mediaType === 'image' ? $this->absoluteUrl($mediaUrl) : '',
            'video_url' => $mediaType === 'video' ? $this->absoluteUrl($mediaUrl) : '',
            'cta_text' => (string) ($row['cta_text'] ?? 'Explore'),
            'link_url' => (string) ($row['link_url'] ?? ''),
            'target_module' => (string) ($row['target_module'] ?? 'mart'),
            'target_type' => (string) ($row['target_type'] ?? 'none'),
            'target_value' => (string) ($row['target_value'] ?? ''),
        ];
    }

    private function absoluteUrl(string $path): string
    {
        if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return $host === '' ? $path : $scheme . '://' . $host . $path;
    }
}
