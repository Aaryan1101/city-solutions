<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Env;
use App\Support\NotificationLog;
use App\Support\OrderStatusHistory;
use App\Support\PrescriptionSchema;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\View;
use App\Support\WalletService;

final class PrescriptionController
{
    public function index(): void
    {
        Auth::requireAdmin();
        PrescriptionSchema::ensure();
        ProductExtrasSchema::ensure();
        $rows = Database::connection()->query(
            'select medical_prescriptions.*, orders.order_number, orders.order_status, orders.payment_status, orders.customer_phone as order_customer_phone
             from medical_prescriptions
             join orders on orders.id = medical_prescriptions.order_id
             where orders.module_key = \'medical\'
             order by medical_prescriptions.id desc'
        )->fetchAll();
        $rows = $this->attachSafetyFlags($rows);
        View::render('admin/medical_prescriptions', [
            'title' => 'Medical Prescriptions',
            'prescriptions' => $rows,
        ]);
    }

    public function status(int $id): void
    {
        Auth::requireAdmin();
        PrescriptionSchema::ensure();
        ProductExtrasSchema::ensure();
        OrderStatusHistory::ensure();
        $status = trim($_POST['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }
        $db = Database::connection();
        $lookup = $db->prepare(
            'select medical_prescriptions.*, orders.order_number, orders.guest_id, orders.customer_name,
                    orders.order_status, orders.payment_method, orders.payment_status, orders.order_amount
             from medical_prescriptions
             join orders on orders.id = medical_prescriptions.order_id
             where medical_prescriptions.id = :id and orders.module_key = \'medical\'
             limit 1'
        );
        $lookup->execute(['id' => $id]);
        $row = $lookup->fetch();
        if (!$row) {
            Response::redirect('/admin/medical/prescriptions');
            return;
        }
        if (($row['status'] ?? '') === 'rejected' && $status === 'approved') {
            Response::redirect('/admin/medical/prescriptions');
            return;
        }

        try {
            $db->beginTransaction();
            $db->prepare(
                'update medical_prescriptions
                 set status = :status, admin_note = :admin_note, reviewed_by = :reviewed_by, reviewed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                 where id = :id'
            )->execute([
                'id' => $id,
                'status' => $status,
                'admin_note' => trim($_POST['admin_note'] ?? '') ?: null,
                'reviewed_by' => $_SESSION['admin_name'] ?? 'Admin',
            ]);

            $orderStatus = $status === 'approved' ? 'pending' : ($status === 'rejected' ? 'prescription_rejected' : 'prescription_pending');
            if ($status === 'rejected' && !in_array((string) ($row['order_status'] ?? ''), ['prescription_rejected', 'cancelled', 'refunded'], true)) {
                $this->releaseInventory((int) $row['order_id']);
                $db->prepare('update order_items set status = :status, updated_at = CURRENT_TIMESTAMP where order_id = :order_id')
                    ->execute(['order_id' => (int) $row['order_id'], 'status' => 'rejected']);
                $this->reconcileRejectedPayment($row);
            }
            $db->prepare('update orders set order_status = :status, updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['id' => (int) $row['order_id'], 'status' => $orderStatus]);
            OrderStatusHistory::record((int) $row['order_id'], null, $orderStatus, 'admin', $_SESSION['admin_name'] ?? 'Admin', 'Prescription ' . $status);
            NotificationLog::record(
                'customer',
                null,
                (string) $row['guest_id'],
                'Prescription ' . $status,
                'Prescription for ' . $row['order_number'] . ' is ' . $status . '.',
                (int) $row['order_id'],
                'medical'
            );
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $payload = ['message' => 'Prescription update failed'];
            if (strtolower((string) Env::get('APP_DEBUG', 'false')) === 'true') {
                $payload['error'] = $error->getMessage();
            }
            Response::json($payload, 500);
            return;
        }
        Response::redirect('/admin/medical/prescriptions');
    }

    private function releaseInventory(int $orderId): void
    {
        $db = Database::connection();
        $items = $db->prepare('select product_id, variant_id, quantity from order_items where order_id = :order_id');
        $items->execute(['order_id' => $orderId]);
        foreach ($items->fetchAll() as $item) {
            if (!empty($item['variant_id'])) {
                $db->prepare('update product_variants set stock = stock + :quantity, updated_at = CURRENT_TIMESTAMP where id = :id')
                    ->execute(['quantity' => (int) $item['quantity'], 'id' => (int) $item['variant_id']]);
                continue;
            }
            $db->prepare('update products set stock = stock + :quantity, updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['quantity' => (int) $item['quantity'], 'id' => (int) $item['product_id']]);
        }
    }

    private function reconcileRejectedPayment(array $order): void
    {
        $paymentMethod = (string) ($order['payment_method'] ?? '');
        $paymentStatus = (string) ($order['payment_status'] ?? '');
        $orderId = (int) ($order['order_id'] ?? 0);
        $amount = (float) ($order['order_amount'] ?? 0);
        $guestId = (string) ($order['guest_id'] ?? '');
        $db = Database::connection();

        if ($paymentMethod === 'wallet' && $paymentStatus === 'paid' && $amount > 0 && str_starts_with($guestId, 'customer-')) {
            WalletService::credit(
                'customer',
                $guestId,
                $amount,
                'prescription_rejection_refund',
                'medical-prescription-rejected:' . $orderId,
                'Wallet refund after prescription rejection for ' . (string) ($order['order_number'] ?? 'medical order')
            );
            $db->prepare(
                'update payment_transactions
                 set status = \'refunded\', admin_note = :admin_note, reconciled_at = CURRENT_TIMESTAMP,
                     reconciled_by = :reconciled_by, updated_at = CURRENT_TIMESTAMP
                 where order_id = :order_id and module_key = \'medical\''
            )->execute([
                'order_id' => $orderId,
                'admin_note' => 'Wallet refunded after prescription rejection.',
                'reconciled_by' => $_SESSION['admin_name'] ?? 'Admin',
            ]);
            $db->prepare('update orders set payment_status = \'refunded\', updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['id' => $orderId]);
            return;
        }

        if ($paymentMethod === 'online_payment' && in_array($paymentStatus, ['paid', 'pending_verification'], true)) {
            $db->prepare(
                'update payment_transactions
                 set status = \'refund_pending\', admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP
                 where order_id = :order_id and module_key = \'medical\''
            )->execute([
                'order_id' => $orderId,
                'admin_note' => 'Prescription rejected. Process gateway refund from Payments/Refunds.',
            ]);
            $db->prepare('update orders set payment_status = \'refund_pending\', updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['id' => $orderId]);
            return;
        }

        if (!in_array($paymentStatus, ['refunded', 'refund_pending'], true)) {
            $db->prepare('update orders set payment_status = \'payment_rejected\', updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['id' => $orderId]);
        }
    }

    private function attachSafetyFlags(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $orderIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['order_id'], $rows)));
        $itemFlags = $this->itemSafetyFlags($orderIds);
        $repeatMap = $this->repeatPurchaseMap($rows);

        foreach ($rows as &$row) {
            $flags = $itemFlags[(int) $row['order_id']] ?? [];
            $phone = (string) ($row['order_customer_phone'] ?? $row['customer_phone'] ?? '');
            if ($phone !== '' && ($repeatMap[$phone] ?? 0) > 1) {
                $flags[] = 'Repeat medical buyer this month: ' . (int) $repeatMap[$phone] . ' orders';
            }
            if (empty($row['reference']) && empty($row['note']) && empty($row['file_path'])) {
                $flags[] = 'No prescription file or reference provided';
            }
            $row['safety_flags'] = array_values(array_unique($flags));
        }
        unset($row);

        return $rows;
    }

    private function itemSafetyFlags(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = Database::connection()->prepare(
            'select order_items.order_id, order_items.product_name, order_items.quantity,
                    products.medicine_type, products.schedule_tag, products.max_qty_per_order,
                    products.max_qty_per_month, products.requires_pharmacist_review,
                    products.requires_age_confirmation
             from order_items
             left join products on products.id = order_items.product_id
             where order_items.order_id in (' . $placeholders . ')'
        );
        $stmt->execute($orderIds);

        $map = [];
        foreach ($stmt->fetchAll() as $item) {
            $orderId = (int) $item['order_id'];
            $name = (string) ($item['product_name'] ?? 'Medicine');
            $medicineType = (string) ($item['medicine_type'] ?? 'otc');
            $quantity = (int) ($item['quantity'] ?? 0);

            if (in_array($medicineType, ['prescription_required', 'restricted'], true)) {
                $map[$orderId][] = $name . ': ' . str_replace('_', ' ', $medicineType);
            }
            if ($medicineType === 'blocked_online') {
                $map[$orderId][] = $name . ': blocked for online sale';
            }
            if ((int) ($item['requires_pharmacist_review'] ?? 0) === 1) {
                $map[$orderId][] = $name . ': pharmacist review required';
            }
            if ((int) ($item['requires_age_confirmation'] ?? 0) === 1) {
                $map[$orderId][] = $name . ': age confirmation required';
            }
            if (!empty($item['schedule_tag'])) {
                $map[$orderId][] = $name . ': schedule ' . (string) $item['schedule_tag'];
            }
            $maxQty = (int) ($item['max_qty_per_order'] ?? 0);
            if ($maxQty > 0 && $quantity >= $maxQty) {
                $map[$orderId][] = $name . ': ordered at per-order limit (' . $quantity . '/' . $maxQty . ')';
            }
            $maxMonthlyQty = (int) ($item['max_qty_per_month'] ?? 0);
            if ($maxMonthlyQty > 0) {
                $map[$orderId][] = $name . ': monthly limit configured (' . $maxMonthlyQty . ')';
            }
        }

        return $map;
    }

    private function repeatPurchaseMap(array $rows): array
    {
        $phones = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => (string) ($row['order_customer_phone'] ?? $row['customer_phone'] ?? ''),
            $rows
        ))));
        if ($phones === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($phones), '?'));
        $stmt = Database::connection()->prepare(
            'select customer_phone, count(*) as order_count
             from orders
             where module_key = \'medical\'
               and customer_phone in (' . $placeholders . ')
               and created_at >= ?
               and order_status not in (\'cancelled\', \'refunded\', \'failed\', \'prescription_rejected\')
             group by customer_phone'
        );
        $params = $phones;
        $params[] = date('Y-m-01 00:00:00');
        $stmt->execute($params);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(string) $row['customer_phone']] = (int) $row['order_count'];
        }
        return $map;
    }
}
