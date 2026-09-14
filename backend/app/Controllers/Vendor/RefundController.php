<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\RefundSchema;
use App\Support\VendorAuth;
use App\Support\View;

final class RefundController
{
    public function index(): void
    {
        VendorAuth::requireVendor();
        RefundSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select refund_requests.*, orders.order_number, order_items.product_name
             from refund_requests
             join orders on orders.id = refund_requests.order_id
             left join order_items on order_items.id = refund_requests.order_item_id
             where refund_requests.vendor_id = :vendor_id
             order by refund_requests.id desc'
        );
        $stmt->execute(['vendor_id' => VendorAuth::id()]);
        View::render('vendor/refunds', ['title' => 'Refunds', 'refunds' => $stmt->fetchAll()]);
    }
}
