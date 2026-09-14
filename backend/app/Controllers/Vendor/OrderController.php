<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\DeliverySchema;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\OrderStatusHistory;
use App\Support\Response;
use App\Support\VendorAuth;
use App\Support\VendorSchema;
use App\Support\View;

final class OrderController
{
    public function index(): void
    {
        VendorAuth::requireVendor();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        OrderStatusHistory::ensure();
        VendorSchema::ensure();
        $vendorId = VendorAuth::id();
        $moduleKey = VendorAuth::moduleKey();
        $stmt = Database::connection()->prepare(
            'select orders.id as order_id, orders.module_key, orders.order_number, orders.customer_name, orders.customer_phone, orders.address, orders.order_status, orders.created_at,
                    delivery_men.name as delivery_man_name, delivery_men.phone as delivery_man_phone,
                    order_items.id as item_id, order_items.product_name, order_items.variant_name, order_items.quantity, order_items.price, order_items.total, order_items.status as item_status
             from order_items
             join orders on orders.id = order_items.order_id
             left join delivery_men on delivery_men.id = orders.delivery_man_id
             where order_items.vendor_id = :vendor_id and orders.module_key = :module_key
             order by orders.id desc, order_items.id asc'
        );
        $stmt->execute(['vendor_id' => $vendorId, 'module_key' => $moduleKey]);
        $items = $stmt->fetchAll();
        View::render('vendor/orders', ['title' => 'Vendor Orders', 'items' => $items]);
    }

    public function status(int $itemId): void
    {
        VendorAuth::requireVendor();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        OrderStatusHistory::ensure();
        VendorSchema::ensure();
        $allowed = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled'];
        $status = $_POST['status'] ?? 'pending';
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $db = Database::connection();
        $vendorId = VendorAuth::id();
        $moduleKey = VendorAuth::moduleKey();
        $lookup = $db->prepare('select order_items.order_id, orders.guest_id, orders.order_number, orders.module_key from order_items join orders on orders.id = order_items.order_id where order_items.id = :id and order_items.vendor_id = :vendor_id and orders.module_key = :module_key limit 1');
        $lookup->execute(['id' => $itemId, 'vendor_id' => $vendorId, 'module_key' => $moduleKey]);
        $order = $lookup->fetch();
        $orderId = (int) ($order['order_id'] ?? 0);
        if ($orderId <= 0) {
            Response::redirect('/vendor/orders');
        }

        $stmt = $db->prepare('update order_items set status = :status, updated_at = CURRENT_TIMESTAMP where id = :id and vendor_id = :vendor_id');
        $stmt->execute(['status' => $status, 'id' => $itemId, 'vendor_id' => $vendorId]);
        OrderStatusHistory::record($orderId, $itemId, $status, 'vendor', $_SESSION['vendor_name'] ?? 'Vendor', 'Vendor updated item status');
        NotificationLog::record('customer', null, (string) $order['guest_id'], 'Order item updated', 'An item in ' . $order['order_number'] . ' is now ' . $status . '.', $orderId, (string) ($order['module_key'] ?? 'mart'));
        $this->syncSingleVendorOrderStatus($orderId, $vendorId, $status);
        Response::redirect('/vendor/orders');
    }

    private function syncSingleVendorOrderStatus(int $orderId, int $vendorId, string $status): void
    {
        $db = Database::connection();
        $order = $db->prepare('select vendor_id from orders where id = :id limit 1');
        $order->execute(['id' => $orderId]);
        if ((int) ($order->fetchColumn() ?: 0) !== $vendorId) {
            return;
        }

        $stmt = $db->prepare('update orders set order_status = :status, updated_at = CURRENT_TIMESTAMP where id = :id and vendor_id = :vendor_id');
        $stmt->execute(['status' => $status, 'id' => $orderId, 'vendor_id' => $vendorId]);
        OrderStatusHistory::record($orderId, null, $status, 'vendor', $_SESSION['vendor_name'] ?? 'Vendor', 'Vendor updated order status');
    }
}
