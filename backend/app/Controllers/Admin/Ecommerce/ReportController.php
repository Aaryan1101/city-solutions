<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Settings;
use App\Support\VendorSchema;
use App\Support\View;

final class ReportController
{
    public function __construct(private readonly string $moduleKey = 'ecommerce', private readonly string $label = 'E-Commerce')
    {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        VendorSchema::ensure();
        $db = Database::connection();
        $commissionPercent = Settings::float('vendor_commission_percent', 10);

        \App\Support\ProductExtrasSchema::ensure();
        $salesStmt = $db->prepare(
            'select
                count(*) as orders_count,
                coalesce(sum(order_amount), 0) as total_sales,
                coalesce(sum(coupon_discount), 0) as coupon_discount
             from orders
             where order_status != \'cancelled\' and module_key = :module_key'
        );
        $salesStmt->execute(['module_key' => $this->moduleKey]);
        $sales = $salesStmt->fetch();

        $vendorStmt = $db->prepare(
            'select
                vendors.id,
                vendors.shop_name,
                coalesce(sum(order_items.total), 0) as sales,
                count(distinct orders.id) as orders_count
             from vendors
             left join order_items on order_items.vendor_id = vendors.id
             left join orders on orders.id = order_items.order_id and orders.order_status != \'cancelled\' and orders.module_key = :module_key
             where vendors.status = \'approved\'
             group by vendors.id, vendors.shop_name
             order by sales desc'
        );
        $vendorStmt->execute(['module_key' => $this->moduleKey]);
        $vendorRows = array_map(function (array $row) use ($commissionPercent): array {
            $sales = (float) $row['sales'];
            $commission = round(($sales * $commissionPercent) / 100, 2);
            $row['sales'] = $sales;
            $row['commission'] = $commission;
            $row['payable'] = max(0, $sales - $commission);
            return $row;
        }, $vendorStmt->fetchAll());

        $lowStockStmt = $db->prepare(
            'select products.*, categories.name as category_name, vendors.shop_name as vendor_name
             from products
             left join categories on categories.id = products.category_id
             left join vendors on vendors.id = products.vendor_id
             where products.stock <= 10 and products.module_key = :module_key
             order by products.stock asc, products.id desc
             limit 50'
        );
        $lowStockStmt->execute(['module_key' => $this->moduleKey]);
        $lowStock = $lowStockStmt->fetchAll();

        View::render('admin/reports', [
            'title' => $this->label . ' Reports',
            'sales' => $sales,
            'vendors' => $vendorRows,
            'lowStock' => $lowStock,
            'commissionPercent' => $commissionPercent,
            'exportBase' => match ($this->moduleKey) {
                'ecommerce' => '/admin/ecommerce/reports/export',
                'medical' => '/admin/medical/reports/export',
                default => '/admin/reports/export',
            },
        ]);
    }

    public function exportSales(): void
    {
        Auth::requireAdmin();
        \App\Support\ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select order_number, customer_name, customer_phone, order_amount, coupon_discount, payment_method, payment_status, order_status, created_at
             from orders
             where module_key = :module_key
             order by id desc'
        );
        $stmt->execute(['module_key' => $this->moduleKey]);
        $rows = $stmt->fetchAll();
        $this->downloadCsv(
            $this->moduleKey . '-sales-report.csv',
            ['Order Number', 'Customer', 'Phone', 'Order Amount', 'Coupon Discount', 'Payment Method', 'Payment Status', 'Order Status', 'Created At'],
            array_map(static fn (array $row): array => [
                $row['order_number'],
                $row['customer_name'],
                $row['customer_phone'],
                $row['order_amount'],
                $row['coupon_discount'],
                $row['payment_method'],
                $row['payment_status'],
                $row['order_status'],
                $row['created_at'],
            ], $rows)
        );
    }

    public function exportVendors(): void
    {
        Auth::requireAdmin();
        VendorSchema::ensure();
        $commissionPercent = Settings::float('vendor_commission_percent', 10);
        \App\Support\ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select
                vendors.shop_name,
                coalesce(sum(order_items.total), 0) as sales,
                count(distinct orders.id) as orders_count
             from vendors
             left join order_items on order_items.vendor_id = vendors.id
             left join orders on orders.id = order_items.order_id and orders.order_status != \'cancelled\' and orders.module_key = :module_key
             where vendors.status = \'approved\'
             group by vendors.id, vendors.shop_name
             order by sales desc'
        );
        $stmt->execute(['module_key' => $this->moduleKey]);
        $rows = $stmt->fetchAll();
        $this->downloadCsv(
            $this->moduleKey . '-vendor-earnings.csv',
            ['Vendor', 'Orders', 'Sales', 'Commission', 'Payable'],
            array_map(static function (array $row) use ($commissionPercent): array {
                $sales = (float) $row['sales'];
                $commission = round(($sales * $commissionPercent) / 100, 2);
                return [
                    $row['shop_name'],
                    $row['orders_count'],
                    $sales,
                    $commission,
                    max(0, $sales - $commission),
                ];
            }, $rows)
        );
    }

    public function exportLowStock(): void
    {
        Auth::requireAdmin();
        VendorSchema::ensure();
        \App\Support\ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select products.name, products.sku, products.stock, categories.name as category_name, vendors.shop_name as vendor_name, products.status
             from products
             left join categories on categories.id = products.category_id
             left join vendors on vendors.id = products.vendor_id
             where products.stock <= 10 and products.module_key = :module_key
             order by products.stock asc, products.id desc'
        );
        $stmt->execute(['module_key' => $this->moduleKey]);
        $rows = $stmt->fetchAll();
        $this->downloadCsv(
            $this->moduleKey . '-low-stock.csv',
            ['Product', 'SKU', 'Stock', 'Category', 'Vendor', 'Status'],
            array_map(static fn (array $row): array => [
                $row['name'],
                $row['sku'],
                $row['stock'],
                $row['category_name'],
                $row['vendor_name'] ?? 'Admin Store',
                (int) $row['status'] === 1 ? 'Active' : 'Inactive',
            ], $rows)
        );
    }

    private function downloadCsv(string $filename, array $header, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'wb');
        fputcsv($output, $header);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}
