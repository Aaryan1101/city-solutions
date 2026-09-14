<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\Settings;
use App\Support\VendorAuth;
use App\Support\VendorSchema;
use App\Support\View;
use App\Support\WalletService;
use App\Support\Response;

final class DashboardController
{
    public function index(): void
    {
        VendorAuth::requireVendor();
        VendorSchema::ensure();
        $db = Database::connection();
        $vendorId = VendorAuth::id();
        $moduleKey = VendorAuth::moduleKey();
        $productCount = $db->prepare('select count(*) from products where vendor_id = :vendor_id and module_key = :module_key');
        $productCount->execute(['vendor_id' => $vendorId, 'module_key' => $moduleKey]);
        $activeCount = $db->prepare('select count(*) from products where vendor_id = :vendor_id and module_key = :module_key and status = 1');
        $activeCount->execute(['vendor_id' => $vendorId, 'module_key' => $moduleKey]);
        $orderCount = $db->prepare('select count(distinct orders.id) from orders join order_items on order_items.order_id = orders.id where order_items.vendor_id = :vendor_id and orders.module_key = :module_key');
        $orderCount->execute(['vendor_id' => $vendorId, 'module_key' => $moduleKey]);
        $sales = $db->prepare('select coalesce(sum(order_items.total), 0) from order_items join orders on orders.id = order_items.order_id where order_items.vendor_id = :vendor_id and orders.module_key = :module_key and orders.order_status != :cancelled');
        $sales->execute(['vendor_id' => $vendorId, 'module_key' => $moduleKey, 'cancelled' => 'cancelled']);
        $grossSales = (float) $sales->fetchColumn();
        $commissionPercent = Settings::float('vendor_commission_percent', 10);
        $commission = round(($grossSales * $commissionPercent) / 100, 2);
        $wallet = WalletService::ensureAccount('vendor', (string) $vendorId);
        $stats = [
            'products' => (int) $productCount->fetchColumn(),
            'active_products' => (int) $activeCount->fetchColumn(),
            'orders' => (int) $orderCount->fetchColumn(),
            'sales' => $grossSales,
            'commission_percent' => $commissionPercent,
            'commission' => $commission,
            'payable' => max(0, $grossSales - $commission),
            'wallet_balance' => (float) ($wallet['balance'] ?? 0),
        ];
        $orderStmt = $db->prepare('select distinct orders.* from orders join order_items on order_items.order_id = orders.id where order_items.vendor_id = :vendor_id and orders.module_key = :module_key order by orders.id desc limit 8');
        $orderStmt->execute(['vendor_id' => $vendorId, 'module_key' => $moduleKey]);
        $orders = $orderStmt->fetchAll();
        $vendorStmt = $db->prepare('select * from vendors where id = :id and module_key = :module_key limit 1');
        $vendorStmt->execute(['id' => $vendorId, 'module_key' => $moduleKey]);

        View::render('vendor/dashboard', [
            'title' => 'Vendor Dashboard',
            'stats' => $stats,
            'orders' => $orders,
            'vendor' => $vendorStmt->fetch(),
        ]);
    }

    public function updateProfile(): void
    {
        VendorAuth::requireVendor();
        VendorSchema::ensure();
        $stmt = Database::connection()->prepare(
            'update vendors set shop_name = :shop_name, owner_name = :owner_name, email = :email, address = :address, city = :city, description = :description, is_temporarily_closed = :is_temporarily_closed, vacation_starts_at = :vacation_starts_at, vacation_ends_at = :vacation_ends_at, vacation_note = :vacation_note, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $stmt->execute([
            'id' => VendorAuth::id(),
            'shop_name' => trim($_POST['shop_name'] ?? ''),
            'owner_name' => trim($_POST['owner_name'] ?? ''),
            'email' => trim($_POST['email'] ?? '') ?: null,
            'address' => trim($_POST['address'] ?? '') ?: null,
            'city' => trim($_POST['city'] ?? '') ?: null,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'is_temporarily_closed' => isset($_POST['is_temporarily_closed']) ? 1 : 0,
            'vacation_starts_at' => trim($_POST['vacation_starts_at'] ?? '') ?: null,
            'vacation_ends_at' => trim($_POST['vacation_ends_at'] ?? '') ?: null,
            'vacation_note' => trim($_POST['vacation_note'] ?? '') ?: null,
        ]);

        Response::redirect('/vendor');
    }
}
