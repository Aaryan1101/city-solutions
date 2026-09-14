<?php

declare(strict_types=1);

namespace App\Controllers\Api\Ecommerce;

use App\Support\BrandSchema;
use App\Support\Database;
use App\Support\Env;
use App\Support\ProductExtrasSchema;
use App\Support\Request;
use App\Support\Response;
use App\Support\VendorSchema;
use App\Support\WishlistSchema;

final class WishlistController
{
    public function __construct(private readonly string $moduleKey = 'ecommerce')
    {
    }

    public function index(): void
    {
        $this->ensure();
        $guestId = $this->guestId();
        $stmt = Database::connection()->prepare($this->productSelect() . ' and wishlists.guest_id = :guest_id order by wishlists.id desc');
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => array_map([$this, 'formatProduct'], $stmt->fetchAll())]);
    }

    public function toggle(): void
    {
        $this->ensure();
        $body = Request::json();
        $guestId = $this->guestId();
        $productId = (int) ($body['product_id'] ?? 0);
        if ($productId <= 0 || !$this->productExists($productId)) {
            Response::json(['message' => 'Product not found'], 404);
            return;
        }

        $db = Database::connection();
        $existing = $db->prepare('select id from wishlists where guest_id = :guest_id and product_id = :product_id limit 1');
        $existing->execute(['guest_id' => $guestId, 'product_id' => $productId]);
        if ($existing->fetch()) {
            $delete = $db->prepare('delete from wishlists where guest_id = :guest_id and product_id = :product_id');
            $delete->execute(['guest_id' => $guestId, 'product_id' => $productId]);
            Response::json(['message' => 'Removed from wishlist', 'wishlisted' => false]);
            return;
        }

        $insert = $db->prepare('insert into wishlists (guest_id, product_id, created_at) values (:guest_id, :product_id, CURRENT_TIMESTAMP)');
        $insert->execute(['guest_id' => $guestId, 'product_id' => $productId]);
        Response::json(['message' => 'Added to wishlist', 'wishlisted' => true]);
    }

    public function check(int $productId): void
    {
        $this->ensure();
        $stmt = Database::connection()->prepare('select id from wishlists where guest_id = :guest_id and product_id = :product_id limit 1');
        $stmt->execute(['guest_id' => $this->guestId(), 'product_id' => $productId]);
        Response::json(['wishlisted' => (bool) $stmt->fetch()]);
    }

    private function ensure(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        WishlistSchema::ensure();
    }

    private function guestId(): string
    {
        return trim($_GET['guest_id'] ?? $_SERVER['HTTP_X_GUEST_ID'] ?? 'guest');
    }

    private function productExists(int $productId): bool
    {
        $stmt = Database::connection()->prepare('select id from products where id = :id and status = 1 and module_key = :module_key limit 1');
        $stmt->execute(['id' => $productId, 'module_key' => $this->moduleKey]);
        return (bool) $stmt->fetch();
    }

    private function productSelect(): string
    {
        return 'select products.*, brands.name as brand_name, vendors.shop_name as vendor_name
            from wishlists
            join products on products.id = wishlists.product_id
            left join brands on brands.id = products.brand_id
            left join vendors on vendors.id = products.vendor_id
            where products.status = 1 and products.module_key = \'' . $this->moduleKey . '\' and (products.vendor_id is null or (vendors.module_key = \'' . $this->moduleKey . '\' and vendors.status = \'approved\'))';
    }

    private function formatProduct(array $row): array
    {
        $row['price'] = (float) $row['price'];
        $row['discount_price'] = $row['discount_price'] === null ? null : (float) $row['discount_price'];
        $row['stock'] = (int) $row['stock'];
        $row['is_featured'] = (bool) $row['is_featured'];
        $row['brand_id'] = (int) ($row['brand_id'] ?? 0);
        $row['vendor_id'] = (int) ($row['vendor_id'] ?? 0);
        if (($row['thumbnail'] ?? null) !== null) {
            $row['thumbnail_full_url'] = $this->assetUrl((string) $row['thumbnail']);
        }
        $row['images'] = [];
        $row['variants'] = [];
        return $row;
    }

    private function assetUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }
        $configured = rtrim(Env::get('APP_URL', ''), '/');
        if ($configured !== '' && !str_contains($configured, '127.0.0.1') && !str_contains($configured, 'localhost')) {
            return $configured . '/' . ltrim($path, '/');
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        if ($proto === null) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        }
        return rtrim($proto . '://' . $host, '/') . '/' . ltrim($path, '/');
    }
}
