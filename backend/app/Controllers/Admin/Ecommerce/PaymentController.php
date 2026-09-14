<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\PaymentGatewayClient;
use App\Support\Settings;
use App\Support\PaymentSchema;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\View;
use App\Support\VendorSchema;
use App\Support\WalletService;

final class PaymentController
{
    public function __construct(
        private readonly string $moduleKey = 'ecommerce',
        private readonly string $basePath = '/admin/ecommerce/payments',
        private readonly string $moduleLabel = 'Payments'
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        PaymentSchema::ensure();
        VendorSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select payment_transactions.*, orders.order_number, orders.payment_status
             from payment_transactions
             join orders on orders.id = payment_transactions.order_id
             where orders.module_key = :module_key
             order by payment_transactions.id desc'
        );
        $stmt->execute(['module_key' => $this->moduleKey]);

        View::render('admin/payments', [
            'title' => $this->moduleLabel,
            'payments' => $stmt->fetchAll(),
            'basePath' => $this->basePath,
        ]);
    }

    public function status(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        PaymentSchema::ensure();
        NotificationSchema::ensure();
        VendorSchema::ensure();
        $status = trim($_POST['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'paid', 'rejected'], true)) {
            $status = 'pending';
        }

        $db = Database::connection();
        $lookup = $db->prepare(
            'select payment_transactions.*, orders.order_number
             from payment_transactions
             join orders on orders.id = payment_transactions.order_id
             where payment_transactions.id = :id
             and orders.module_key = :module_key
             limit 1'
        );
        $lookup->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $payment = $lookup->fetch();
        if (!$payment) {
            Response::redirect($this->basePath);
            return;
        }

        $gatewayAction = trim((string) ($_POST['gateway_action'] ?? ''));
        $gatewayResult = null;
        if (in_array($gatewayAction, ['capture', 'status'], true)) {
            $gatewayResult = $gatewayAction === 'capture'
                ? PaymentGatewayClient::capture($this->moduleKey, $payment, $payment)
                : PaymentGatewayClient::status($this->moduleKey, $payment, $payment);
            if (($gatewayResult['ok'] ?? false) === true) {
                $status = 'paid';
            }
        }

        $adminNote = trim($_POST['admin_note'] ?? '');
        $stmt = $db->prepare(
            'update payment_transactions
             set status = :status,
                 admin_note = :admin_note,
                 gateway_response = :gateway_response,
                 reconciled_at = CURRENT_TIMESTAMP,
                 reconciled_by = :reconciled_by,
                 updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'admin_note' => $gatewayResult === null
                ? ($adminNote === '' ? null : $adminNote)
                : trim(($adminNote === '' ? '' : $adminNote . "\n") . 'Gateway: ' . ($gatewayResult['message'] ?? 'processed')),
            'gateway_response' => $gatewayResult === null ? ($payment['gateway_response'] ?? null) : json_encode($gatewayResult, JSON_UNESCAPED_SLASHES),
            'reconciled_by' => $_SESSION['admin_name'] ?? 'Admin',
        ]);

        $orderPaymentStatus = match ($status) {
            'paid' => 'paid',
            'rejected' => 'payment_rejected',
            default => $payment['payment_method'] === 'cash_on_delivery' ? 'unpaid' : 'pending_verification',
        };
        $updateOrder = $db->prepare(
            'update orders set payment_status = :payment_status, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $updateOrder->execute([
            'id' => (int) $payment['order_id'],
            'payment_status' => $orderPaymentStatus,
        ]);

        if ($status === 'paid') {
            $commissionPercent = Settings::float('vendor_commission_percent', 10);
            $items = $db->prepare(
                'select vendor_id, coalesce(sum(total), 0) as vendor_total
                 from order_items
                 where order_id = :order_id and vendor_id is not null
                 group by vendor_id'
            );
            $items->execute(['order_id' => (int) $payment['order_id']]);
            foreach ($items->fetchAll() as $row) {
                $vendorId = (int) ($row['vendor_id'] ?? 0);
                if ($vendorId <= 0) {
                    continue;
                }
                $gross = (float) $row['vendor_total'];
                $net = round($gross - (($gross * $commissionPercent) / 100), 2);
                if ($net <= 0) {
                    continue;
                }
                WalletService::credit(
                    'vendor',
                    (string) $vendorId,
                    $net,
                    'order_settlement',
                    'payment:' . $id . ':vendor:' . $vendorId,
                    'Settlement for order ' . $payment['order_number']
                );
            }
        }

        NotificationLog::record(
            'customer',
            null,
            (string) $payment['guest_id'],
            'Payment status updated',
            'Payment for ' . $payment['order_number'] . ' is now ' . $status . '.',
            (int) $payment['order_id'],
            $this->moduleKey
        );

        Response::redirect($this->basePath);
    }
}
