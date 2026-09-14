<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\VendorSchema;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $db = Database::connection();
        VendorSchema::ensure();

        $stats = [
            'categories' => (int) $db->query('select count(*) from categories')->fetchColumn(),
            'products' => (int) $db->query('select count(*) from products')->fetchColumn(),
            'orders' => (int) $db->query('select count(*) from orders')->fetchColumn(),
            'low_stock' => (int) $db->query('select count(*) from products where stock <= 5')->fetchColumn(),
            'pending_vendors' => (int) $db->query('select count(*) from vendors where status = \'pending\'')->fetchColumn(),
        ];

        $orders = $db->query('select * from orders order by id desc limit 8')->fetchAll();

        View::render('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'orders' => $orders,
        ]);
    }
}
