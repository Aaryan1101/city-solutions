<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\PaymentGatewayClient;
use App\Support\ProductExtrasSchema;
use App\Support\RefundSchema;
use App\Support\Response;
use App\Support\View;
use App\Support\WalletService;

final class RefundController
{
    public function __construct(
        private readonly string $moduleKey = 'ecommerce',
        private readonly string $basePath = '/admin/ecommerce/refunds',
        private readonly string $moduleLabel = 'Refunds'
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        RefundSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select refund_requests.*, orders.order_number, order_items.product_name, vendors.shop_name as vendor_name
             from refund_requests
             join orders on orders.id = refund_requests.order_id
             left join order_items on order_items.id = refund_requests.order_item_id
             left join vendors on vendors.id = refund_requests.vendor_id
             where orders.module_key = :module_key
             order by refund_requests.id desc'
        );
        $stmt->execute(['module_key' => $this->moduleKey]);
        View::render('admin/refunds', ['title' => $this->moduleLabel, 'refunds' => $stmt->fetchAll(), 'basePath' => $this->basePath]);
    }

    public function status(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        RefundSchema::ensure();
        NotificationSchema::ensure();
        $allowed = ['pending', 'approved', 'rejected', 'refunded'];
        $status = $_POST['status'] ?? 'pending';
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $db = Database::connection();
        $lookup = $db->prepare('select refund_requests.*, orders.order_number from refund_requests join orders on orders.id = refund_requests.order_id where refund_requests.id = :id and orders.module_key = :module_key limit 1');
        $lookup->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $refund = $lookup->fetch();
        if (!$refund) {
            Response::redirect($this->basePath);
            return;
        }

        $gatewayResult = null;
        $payment = [];
        if ($status === 'refunded') {
            $paymentLookup = $db->prepare('select * from payment_transactions where order_id = :order_id and module_key = :module_key order by id desc limit 1');
            $paymentLookup->execute(['order_id' => (int) $refund['order_id'], 'module_key' => $this->moduleKey]);
            $payment = $paymentLookup->fetch() ?: [];
            if (($payment['payment_method'] ?? '') === 'online_payment') {
                $gatewayResult = PaymentGatewayClient::refund($this->moduleKey, $refund, $payment + $refund);
                if (($gatewayResult['ok'] ?? false) !== true) {
                    $status = 'approved';
                }
            }
        }

        $stmt = $db->prepare('update refund_requests set status = :status, admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP where id = :id');
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'admin_note' => trim(
                (trim($_POST['admin_note'] ?? '') ?: '') .
                ($gatewayResult === null ? '' : "\nGateway: " . ($gatewayResult['message'] ?? 'processed'))
            ) ?: null,
        ]);
        if ($status === 'refunded') {
            $db->prepare('update orders set payment_status = \'refunded\', updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['id' => (int) $refund['order_id']]);
            $db->prepare('update payment_transactions set status = \'refunded\', gateway_response = :gateway_response, reconciled_at = CURRENT_TIMESTAMP, reconciled_by = :reconciled_by, updated_at = CURRENT_TIMESTAMP where order_id = :order_id and module_key = :module_key')
                ->execute([
                    'order_id' => (int) $refund['order_id'],
                    'module_key' => $this->moduleKey,
                    'gateway_response' => $gatewayResult === null ? null : json_encode($gatewayResult, JSON_UNESCAPED_SLASHES),
                    'reconciled_by' => $_SESSION['admin_name'] ?? 'Admin',
                ]);
        }

        if ($status === 'refunded' && !empty($refund['guest_id'])) {
            WalletService::credit(
                'customer',
                (string) $refund['guest_id'],
                (float) $refund['amount'],
                'refund_credit',
                'refund:' . $id,
                'Refund credited to wallet for ' . $refund['order_number']
            );
        }

        NotificationLog::record('customer', null, (string) $refund['guest_id'], 'Refund status updated', 'Refund for ' . $refund['order_number'] . ' is now ' . $status . '.', (int) $refund['order_id'], $this->moduleKey);
        Response::redirect($this->basePath);
    }
}
