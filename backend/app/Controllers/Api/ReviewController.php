<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\ProductExtrasSchema;
use App\Support\Request;
use App\Support\Response;
use App\Support\ReviewSchema;
use App\Support\VendorSchema;

final class ReviewController
{
    public function __construct(private readonly string $moduleKey = 'mart')
    {
    }

    public function index(int $productId): void
    {
        ReviewSchema::ensure();
        ProductExtrasSchema::ensure();
        if (!$this->productExists($productId)) {
            Response::json(['message' => 'Product not found'], 404);
            return;
        }

        $stmt = Database::connection()->prepare('select * from product_reviews where product_id = :product_id and status = 1 order by id desc limit 50');
        $stmt->execute(['product_id' => $productId]);
        $summary = Database::connection()->prepare('select count(*) as total_reviews, coalesce(avg(rating), 0) as average_rating from product_reviews where product_id = :product_id and status = 1');
        $summary->execute(['product_id' => $productId]);
        Response::json(['data' => $stmt->fetchAll(), 'summary' => $summary->fetch()]);
    }

    public function store(int $productId): void
    {
        ReviewSchema::ensure();
        VendorSchema::ensure();
        ProductExtrasSchema::ensure();
        $body = Request::json();
        $guestId = trim($body['guest_id'] ?? $_GET['guest_id'] ?? '');
        $customerName = trim($body['customer_name'] ?? 'Customer');
        $rating = min(5, max(1, (int) ($body['rating'] ?? 5)));
        $comment = trim($body['comment'] ?? '');
        if ($guestId === '' || $comment === '') {
            Response::json(['message' => 'Name, comment, and guest id are required'], 422);
            return;
        }

        $productStmt = Database::connection()->prepare('select id, vendor_id from products where id = :id and module_key = :module_key and status = 1 limit 1');
        $productStmt->execute(['id' => $productId, 'module_key' => $this->moduleKey]);
        $product = $productStmt->fetch();
        if (!$product) {
            Response::json(['message' => 'Product not found'], 404);
            return;
        }

        $db = Database::connection();
        $existing = $db->prepare('select id from product_reviews where product_id = :product_id and guest_id = :guest_id limit 1');
        $existing->execute(['product_id' => $productId, 'guest_id' => $guestId]);
        $reviewId = (int) ($existing->fetchColumn() ?: 0);
        if ($reviewId > 0) {
            $stmt = $db->prepare('update product_reviews set customer_name = :customer_name, rating = :rating, comment = :comment, status = 1, updated_at = CURRENT_TIMESTAMP where id = :id');
            $stmt->execute([
                'id' => $reviewId,
                'customer_name' => $customerName,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        } else {
            $stmt = $db->prepare(
                'insert into product_reviews (product_id, vendor_id, guest_id, customer_name, rating, comment, status, created_at, updated_at)
                 values (:product_id, :vendor_id, :guest_id, :customer_name, :rating, :comment, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                'product_id' => $productId,
                'vendor_id' => $product['vendor_id'],
                'guest_id' => $guestId,
                'customer_name' => $customerName,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        }

        Response::json(['message' => 'Review saved']);
    }

    private function productExists(int $productId): bool
    {
        VendorSchema::ensure();
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('select id from products where id = :id and module_key = :module_key and status = 1 limit 1');
        $stmt->execute(['id' => $productId, 'module_key' => $this->moduleKey]);
        return (bool) $stmt->fetchColumn();
    }
}
