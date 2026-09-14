<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\DeliverySchema;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\OrderStatusHistory;
use App\Support\RefundSchema;
use App\Support\Response;
use App\Support\View;

final class OrderController
{
    public function __construct(private readonly string $moduleKey = 'mart', private readonly string $basePath = '/admin/orders')
    {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        OrderStatusHistory::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        RefundSchema::ensure();
        \App\Support\ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select orders.*, delivery_men.name as delivery_man_name
             from orders
             left join delivery_men on delivery_men.id = orders.delivery_man_id
             where orders.module_key = :module_key' . Auth::zoneWhere('orders') . '
             order by orders.id desc'
        );
        $stmt->execute(Auth::zoneParams(['module_key' => $this->moduleKey]));
        $orders = $stmt->fetchAll();
        View::render('admin/orders', [
            'title' => ($this->moduleKey === 'ecommerce' ? 'E-Commerce' : 'Mart') . ' Orders',
            'orders' => $this->decorateOrders($orders),
            'basePath' => $this->basePath,
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireAdmin();
        OrderStatusHistory::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        RefundSchema::ensure();
        $db = Database::connection();
        $stmt = $db->prepare(
            'select orders.*, delivery_men.name as delivery_man_name, delivery_men.phone as delivery_man_phone, delivery_men.vehicle_type, delivery_men.vehicle_number
             from orders
             left join delivery_men on delivery_men.id = orders.delivery_man_id
             where orders.id = :id and orders.module_key = :module_key' . Auth::zoneWhere('orders')
        );
        $stmt->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
        $order = $stmt->fetch();
        if (!$order) {
            Response::redirect($this->basePath);
        }

        $items = $db->prepare('select * from order_items where order_id = :id');
        $items->execute(['id' => $id]);
        $history = $db->prepare('select * from order_status_history where order_id = :id order by id asc');
        $history->execute(['id' => $id]);
        $deliveryMen = $db->query('select * from delivery_men where status = 1 order by name asc')->fetchAll();

        View::render('admin/order_show', [
            'title' => 'Order #' . $id,
            'order' => $this->decorateOrder($order),
            'items' => $items->fetchAll(),
            'history' => $history->fetchAll(),
            'deliveryMen' => $deliveryMen,
            'basePath' => $this->basePath,
        ]);
    }

    public function invoice(int $id): void
    {
        Auth::requireAdmin();
        $db = Database::connection();
        $stmt = $db->prepare('select * from orders where id = :id and module_key = :module_key' . Auth::zoneWhere('orders') . ' limit 1');
        $stmt->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
        $order = $stmt->fetch();
        if (!$order) {
            Response::redirect($this->basePath);
        }

        $items = $db->prepare('select * from order_items where order_id = :id order by id asc');
        $items->execute(['id' => $id]);

        View::render('admin/order_invoice', [
            'title' => 'Invoice ' . $order['order_number'],
            'order' => $order,
            'items' => $items->fetchAll(),
            'basePath' => $this->basePath,
        ]);
    }

    public function status(int $id): void
    {
        Auth::requireAdmin();
        OrderStatusHistory::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        $allowed = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled'];
        $status = $_POST['order_status'] ?? 'pending';
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $db = Database::connection();
        $orderStmt = $db->prepare('select * from orders where id = :id and module_key = :module_key' . Auth::zoneWhere('orders') . ' limit 1');
        $orderStmt->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
        $order = $orderStmt->fetch();
        if (!$order) {
            Response::redirect($this->basePath);
        }
        $stmt = $db->prepare('update orders set order_status = :status, updated_at = CURRENT_TIMESTAMP where id = :id and module_key = :module_key' . Auth::zoneWhere('orders'));
        $stmt->execute(Auth::zoneParams(['status' => $status, 'id' => $id, 'module_key' => $this->moduleKey]));
        OrderStatusHistory::record($id, null, $status, 'admin', $_SESSION['admin_name'] ?? 'Admin', 'Admin updated order status');
        if ($order) {
            NotificationLog::record('customer', null, (string) $order['guest_id'], 'Order status updated', 'Your order ' . $order['order_number'] . ' is now ' . $status . '.', $id);
        }
        Response::redirect($this->basePath . '/' . $id);
    }

    public function assignDelivery(int $id): void
    {
        Auth::requireAdmin();
        OrderStatusHistory::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        $deliveryManId = (int) ($_POST['delivery_man_id'] ?? 0);
        $db = Database::connection();
        $orderStmt = $db->prepare('select * from orders where id = :id and module_key = :module_key' . Auth::zoneWhere('orders') . ' limit 1');
        $orderStmt->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
        $order = $orderStmt->fetch();
        if (!$order) {
            Response::redirect($this->basePath);
        }
        if ($deliveryManId > 0) {
            $person = $db->prepare('select * from delivery_men where id = :id and status = 1 limit 1');
            $person->execute(['id' => $deliveryManId]);
            $deliveryMan = $person->fetch();
            if (!$deliveryMan) {
                Response::redirect($this->basePath . '/' . $id);
            }
            $stmt = $db->prepare('update orders set delivery_man_id = :delivery_man_id, delivery_assigned_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP where id = :id and module_key = :module_key' . Auth::zoneWhere('orders'));
            $stmt->execute(Auth::zoneParams(['delivery_man_id' => $deliveryManId, 'id' => $id, 'module_key' => $this->moduleKey]));
            OrderStatusHistory::record($id, null, 'delivery_assigned', 'admin', $_SESSION['admin_name'] ?? 'Admin', 'Assigned to ' . $deliveryMan['name']);
            NotificationLog::record('delivery', $deliveryManId, null, 'Delivery assigned', 'Order ' . ($order['order_number'] ?? ('#' . $id)) . ' was assigned to you.', $id);
            if ($order) {
                NotificationLog::record('customer', null, (string) $order['guest_id'], 'Delivery assigned', $deliveryMan['name'] . ' will deliver your order ' . $order['order_number'] . '.', $id);
            }
        } else {
            $stmt = $db->prepare('update orders set delivery_man_id = null, delivery_assigned_at = null, updated_at = CURRENT_TIMESTAMP where id = :id and module_key = :module_key' . Auth::zoneWhere('orders'));
            $stmt->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
            OrderStatusHistory::record($id, null, 'delivery_unassigned', 'admin', $_SESSION['admin_name'] ?? 'Admin', 'Delivery assignment removed');
            if ($order) {
                NotificationLog::record('customer', null, (string) $order['guest_id'], 'Delivery assignment updated', 'Delivery assignment was removed for order ' . $order['order_number'] . '.', $id);
            }
        }

        Response::redirect($this->basePath . '/' . $id);
    }

    public function tracking(int $id): void
    {
        Auth::requireAdmin();
        DeliverySchema::ensure();
        \App\Support\ShippingSchema::ensure();
        $stmt = Database::connection()->prepare(
            'update orders set tracking_provider = :tracking_provider, tracking_number = :tracking_number, tracking_url = :tracking_url, expected_delivery = :expected_delivery, updated_at = CURRENT_TIMESTAMP where id = :id and module_key = :module_key' . Auth::zoneWhere('orders')
        );
        $stmt->execute(Auth::zoneParams([
            'id' => $id,
            'module_key' => $this->moduleKey,
            'tracking_provider' => trim($_POST['tracking_provider'] ?? '') ?: null,
            'tracking_number' => trim($_POST['tracking_number'] ?? '') ?: null,
            'tracking_url' => trim($_POST['tracking_url'] ?? '') ?: null,
            'expected_delivery' => trim($_POST['expected_delivery'] ?? '') ?: null,
        ]));

        Response::redirect($this->basePath . '/' . $id);
    }

    private function decorateOrders(array $orders): array
    {
        if ($orders === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn (array $order): int => (int) $order['id'], $orders)));
        $refundMap = $this->refundMap($ids);

        return array_map(fn (array $order): array => $this->decorateOrder($order, $refundMap[(int) $order['id']] ?? null), $orders);
    }

    private function decorateOrder(array $order, ?array $refund = null): array
    {
        $refund ??= $this->refundMap([(int) $order['id']])[(int) $order['id']] ?? null;
        if ($refund) {
            $order['refund_status'] = (string) $refund['status'];
            $order['display_status'] = match ((string) $refund['status']) {
                'pending' => 'refund_pending',
                'approved' => 'refund_approved',
                'rejected' => 'refund_rejected',
                'refunded' => 'refunded',
                default => (string) $order['order_status'],
            };
        } else {
            $order['refund_status'] = null;
            $order['display_status'] = (string) $order['order_status'];
        }

        return $order;
    }

    private function refundMap(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = Database::connection()->prepare(
            'select rr.order_id, rr.status
             from refund_requests rr
             join (
                select order_id, max(id) as latest_id
                from refund_requests
                where order_id in (' . $placeholders . ')
                group by order_id
             ) latest on latest.latest_id = rr.id'
        );
        $stmt->execute($orderIds);
        $map = [];
        foreach ($stmt->fetchAll() as $refund) {
            $map[(int) $refund['order_id']] = $refund;
        }

        return $map;
    }
}
