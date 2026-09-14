<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\ProductExtrasSchema;
use App\Support\Settings;
use App\Support\VendorAuth;
use App\Support\WalletSchema;

final class ReportController
{
    public function exportOrders(): void
    {
        VendorAuth::requireVendor();
        $vendorId = VendorAuth::id();
        $stmt = Database::connection()->prepare(
            'select orders.order_number, orders.customer_name, orders.customer_phone, order_items.product_name, order_items.variant_name, order_items.quantity, order_items.price, order_items.total, order_items.status, orders.created_at
             from order_items
             join orders on orders.id = order_items.order_id
             where order_items.vendor_id = :vendor_id
             order by orders.id desc'
        );
        $stmt->execute(['vendor_id' => $vendorId]);
        $this->csv('vendor-orders.csv', ['Order', 'Customer', 'Phone', 'Product', 'Variant', 'Qty', 'Price', 'Total', 'Status', 'Date'], $stmt->fetchAll());
    }

    public function exportSettlements(): void
    {
        VendorAuth::requireVendor();
        WalletSchema::ensure();
        $stmt = Database::connection()->prepare('select amount, payment_reference, status, settled_at, note from vendor_settlements where vendor_id = :vendor_id order by id desc');
        $stmt->execute(['vendor_id' => VendorAuth::id()]);
        $this->csv('vendor-settlements.csv', ['Amount', 'Reference', 'Status', 'Settled At', 'Note'], $stmt->fetchAll());
    }

    public function exportProducts(): void
    {
        VendorAuth::requireVendor();
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select name, sku, unit, price, discount_price, stock, tax_percent, shipping_cost, status, is_featured, created_at
             from products
             where vendor_id = :vendor_id
             order by id desc'
        );
        $stmt->execute(['vendor_id' => VendorAuth::id()]);
        $this->csv('vendor-products.csv', ['Name', 'SKU', 'Unit', 'Price', 'Discount', 'Stock', 'Tax %', 'Shipping Cost', 'Status', 'Featured', 'Created'], $stmt->fetchAll());
    }

    public function exportLedger(): void
    {
        VendorAuth::requireVendor();
        WalletSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select direction, amount, entry_type, description, reference_type, reference_id, created_at
             from wallet_ledger
             where owner_type = \'vendor\' and owner_key = :owner_key
             order by id desc'
        );
        $stmt->execute(['owner_key' => (string) VendorAuth::id()]);
        $this->csv('vendor-wallet-ledger.csv', ['Direction', 'Amount', 'Type', 'Description', 'Reference Type', 'Reference ID', 'Date'], $stmt->fetchAll());
    }

    public function exportSummary(): void
    {
        VendorAuth::requireVendor();
        $vendorId = VendorAuth::id();
        $db = Database::connection();
        $commissionPercent = Settings::float('vendor_commission_percent', 10);
        $statuses = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled', 'refunded'];
        $rows = [];
        foreach ($statuses as $status) {
            $stmt = $db->prepare(
                'select count(distinct orders.id) as orders_count, coalesce(sum(order_items.total), 0) as sales_total
                 from order_items
                 join orders on orders.id = order_items.order_id
                 where order_items.vendor_id = :vendor_id and orders.order_status = :status'
            );
            $stmt->execute(['vendor_id' => $vendorId, 'status' => $status]);
            $row = $stmt->fetch() ?: ['orders_count' => 0, 'sales_total' => 0];
            $sales = (float) $row['sales_total'];
            $commission = round(($sales * $commissionPercent) / 100, 2);
            $rows[] = [
                'status' => $status,
                'orders' => (int) $row['orders_count'],
                'gross_sales' => $sales,
                'commission' => $commission,
                'payable' => max(0, $sales - $commission),
            ];
        }
        $this->csv('vendor-sales-summary.csv', ['Status', 'Orders', 'Gross Sales', 'Commission', 'Payable'], $rows);
    }

    private function csv(string $filename, array $headers, array $rows): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
    }
}
