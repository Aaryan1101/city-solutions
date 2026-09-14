<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\RefundSchema;
use App\Support\Request;
use App\Support\Response;

final class RefundController
{
    public function __construct(private readonly string $moduleKey = 'mart')
    {
    }

    public function index(): void
    {
        RefundSchema::ensure();
        $guestId = trim($_GET['guest_id'] ?? '');
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }

        $stmt = Database::connection()->prepare(
            'select refund_requests.*, orders.order_number, order_items.product_name
             from refund_requests
             join orders on orders.id = refund_requests.order_id
             left join order_items on order_items.id = refund_requests.order_item_id
             where refund_requests.guest_id = :guest_id and orders.module_key = :module_key
             order by refund_requests.id desc'
        );
        $stmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function store(int $orderId): void
    {
        RefundSchema::ensure();
        NotificationSchema::ensure();
        $body = Request::json();
        $guestId = trim($body['guest_id'] ?? $_GET['guest_id'] ?? '');
        $orderItemId = (int) ($body['order_item_id'] ?? 0);
        $reason = trim($body['reason'] ?? '');
        $note = trim($body['note'] ?? '');
        if ($guestId === '' || $reason === '') {
            Response::json(['message' => 'Refund reason is required'], 422);
            return;
        }

        $db = Database::connection();
        $orderStmt = $db->prepare('select * from orders where id = :id and guest_id = :guest_id and module_key = :module_key limit 1');
        $orderStmt->execute(['id' => $orderId, 'guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $order = $orderStmt->fetch();
        if (!$order) {
            Response::json(['message' => 'Order not found'], 404);
            return;
        }
        if (!in_array($order['order_status'], ['delivered', 'cancelled'], true)) {
            Response::json(['message' => 'Refund can be requested after delivery or cancellation'], 422);
            return;
        }

        $item = null;
        if ($orderItemId > 0) {
            $itemStmt = $db->prepare('select * from order_items where id = :id and order_id = :order_id limit 1');
            $itemStmt->execute(['id' => $orderItemId, 'order_id' => $orderId]);
            $item = $itemStmt->fetch();
            if (!$item) {
                Response::json(['message' => 'Order item not found'], 404);
                return;
            }
        }

        $duplicate = $db->prepare('select id from refund_requests where order_id = :order_id and coalesce(order_item_id, 0) = :order_item_id and guest_id = :guest_id and status in (\'pending\', \'approved\', \'refunded\') limit 1');
        $duplicate->execute(['order_id' => $orderId, 'order_item_id' => $orderItemId, 'guest_id' => $guestId]);
        if ($duplicate->fetch()) {
            Response::json(['message' => 'Refund request already exists for this item/order'], 422);
            return;
        }

        $amount = $item ? (float) $item['total'] : (float) $order['order_amount'];
        $stmt = $db->prepare(
            'insert into refund_requests (order_id, order_item_id, vendor_id, guest_id, customer_name, customer_phone, amount, reason, note, status, created_at, updated_at)
             values (:order_id, :order_item_id, :vendor_id, :guest_id, :customer_name, :customer_phone, :amount, :reason, :note, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'order_id' => $orderId,
            'order_item_id' => $item ? $item['id'] : null,
            'vendor_id' => $item ? $item['vendor_id'] : $order['vendor_id'],
            'guest_id' => $guestId,
            'customer_name' => $order['customer_name'],
            'customer_phone' => $order['customer_phone'],
            'amount' => $amount,
            'reason' => $reason,
            'note' => $note === '' ? null : $note,
            'status' => 'pending',
        ]);
        $refundId = (int) $db->lastInsertId();
        NotificationLog::record('admin', null, null, 'Refund requested', 'Refund requested for ' . $order['order_number'], $orderId);
        if ($item && !empty($item['vendor_id'])) {
            NotificationLog::record('vendor', (int) $item['vendor_id'], null, 'Refund requested', $item['product_name'] . ' refund requested in ' . $order['order_number'], $orderId);
        }

        Response::json(['message' => 'Refund request submitted', 'refund_id' => $refundId]);
    }
}
