<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\Database;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\ReviewSchema;
use App\Support\View;

final class ReviewController
{
    public function __construct(
        private readonly string $moduleKey = 'ecommerce',
        private readonly string $basePath = '/admin/ecommerce/reviews',
        private readonly string $moduleLabel = 'Reviews'
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        ReviewSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select product_reviews.*, products.name as product_name, vendors.shop_name as vendor_name
             from product_reviews
             join products on products.id = product_reviews.product_id
             left join vendors on vendors.id = product_reviews.vendor_id
             where products.module_key = :module_key
             order by product_reviews.id desc limit 200'
        );
        $stmt->execute(['module_key' => $this->moduleKey]);
        View::render('admin/reviews', ['title' => $this->moduleLabel, 'reviews' => $stmt->fetchAll(), 'basePath' => $this->basePath]);
    }

    public function status(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        ReviewSchema::ensure();
        $status = isset($_POST['status']) ? 1 : 0;
        $stmt = Database::connection()->prepare(
            'update product_reviews
             set status = :status, updated_at = CURRENT_TIMESTAMP
             where id = :id and product_id in (select id from products where module_key = :module_key)'
        );
        $stmt->execute(['status' => $status, 'id' => $id, 'module_key' => $this->moduleKey]);
        Response::redirect($this->basePath);
    }
}
