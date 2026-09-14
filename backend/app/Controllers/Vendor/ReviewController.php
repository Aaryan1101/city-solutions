<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\Response;
use App\Support\ReviewSchema;
use App\Support\VendorAuth;
use App\Support\View;

final class ReviewController
{
    public function index(): void
    {
        VendorAuth::requireVendor();
        ReviewSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select product_reviews.*, products.name as product_name
             from product_reviews
             join products on products.id = product_reviews.product_id
             where product_reviews.vendor_id = :vendor_id
             order by product_reviews.id desc limit 200'
        );
        $stmt->execute(['vendor_id' => VendorAuth::id()]);
        View::render('vendor/reviews', ['title' => 'Product Reviews', 'reviews' => $stmt->fetchAll()]);
    }

    public function reply(int $id): void
    {
        VendorAuth::requireVendor();
        ReviewSchema::ensure();
        $stmt = Database::connection()->prepare('update product_reviews set reply = :reply, updated_at = CURRENT_TIMESTAMP where id = :id and vendor_id = :vendor_id');
        $stmt->execute([
            'id' => $id,
            'vendor_id' => VendorAuth::id(),
            'reply' => trim($_POST['reply'] ?? ''),
        ]);
        Response::redirect('/vendor/reviews');
    }
}
