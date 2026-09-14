<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\HotelSchema;
use App\Support\NotificationLog;
use App\Support\PaymentSchema;
use App\Support\Response;
use App\Support\RestaurantSchema;
use App\Support\ServiceSchema;
use App\Support\Settings;

final class PaymentWebhookController
{
    private string $rawPayload = '';

    public function order(string $moduleKey): void
    {
        PaymentSchema::ensure();
        $payload = $this->payload($moduleKey);
        if ($payload === null) {
            return;
        }
        $orderId = (int) ($payload['order_id'] ?? 0);
        $orderNumber = trim((string) ($payload['order_number'] ?? ''));
        $reference = trim((string) ($payload['reference'] ?? $payload['payment_reference'] ?? ''));
        $status = $this->status((string) ($payload['status'] ?? 'paid'));
        if ($orderId <= 0 && $orderNumber === '') {
            Response::json(['message' => 'order_id or order_number is required'], 422);
            return;
        }

        $db = Database::connection();
        $lookup = $db->prepare(
            'select * from orders where module_key = :module_key and (id = :id or order_number = :order_number) limit 1'
        );
        $lookup->execute(['module_key' => $moduleKey, 'id' => $orderId, 'order_number' => $orderNumber]);
        $order = $lookup->fetch();
        if (!$order) {
            Response::json(['message' => 'Order not found'], 404);
            return;
        }
        if ($this->isDuplicate($moduleKey, $payload)) {
            Response::json(['message' => 'Webhook already processed']);
            return;
        }

        $paymentStatus = $status === 'paid' ? 'paid' : ($status === 'refunded' ? 'refunded' : 'payment_rejected');
        try {
            $db->beginTransaction();
            $db->prepare('update orders set payment_status = :payment_status, updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['id' => (int) $order['id'], 'payment_status' => $paymentStatus]);
            $db->prepare(
                'update payment_transactions
                 set status = :status, reference = coalesce(:reference, reference), admin_note = :admin_note,
                     gateway_response = :gateway_response, reconciled_at = CURRENT_TIMESTAMP,
                     reconciled_by = :reconciled_by, updated_at = CURRENT_TIMESTAMP
                 where order_id = :order_id and module_key = :module_key'
            )->execute([
                'order_id' => (int) $order['id'],
                'module_key' => $moduleKey,
                'status' => $status,
                'reference' => $reference === '' ? null : $reference,
                'admin_note' => 'Webhook verified',
                'gateway_response' => json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}',
                'reconciled_by' => 'webhook',
            ]);
            NotificationLog::record('customer', null, (string) $order['guest_id'], 'Payment updated', 'Payment for ' . $order['order_number'] . ' is now ' . $paymentStatus . '.', (int) $order['id'], $moduleKey);
            $this->markProcessed($moduleKey, $payload);
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->markFailed($moduleKey, $payload);
            Response::json(['message' => 'Webhook could not be processed. Please retry.'], 500);
            return;
        }
        Response::json(['message' => 'Webhook processed']);
    }

    public function hotel(): void
    {
        HotelSchema::ensure();
        PaymentSchema::ensure();
        $payload = $this->payload('hotel');
        if ($payload === null) {
            return;
        }
        $bookingId = (int) ($payload['booking_id'] ?? 0);
        $bookingNumber = trim((string) ($payload['booking_number'] ?? ''));
        $reference = trim((string) ($payload['reference'] ?? $payload['payment_reference'] ?? ''));
        $status = $this->status((string) ($payload['status'] ?? 'paid'));
        if ($bookingId <= 0 && $bookingNumber === '') {
            Response::json(['message' => 'booking_id or booking_number is required'], 422);
            return;
        }

        $db = Database::connection();
        $lookup = $db->prepare('select * from hotel_bookings where id = :id or booking_number = :booking_number limit 1');
        $lookup->execute(['id' => $bookingId, 'booking_number' => $bookingNumber]);
        $booking = $lookup->fetch();
        if (!$booking) {
            Response::json(['message' => 'Booking not found'], 404);
            return;
        }
        if ($this->isDuplicate('hotel', $payload)) {
            Response::json(['message' => 'Webhook already processed']);
            return;
        }

        $paymentStatus = $status === 'paid' ? 'paid' : ($status === 'refunded' ? 'refunded' : 'failed');
        try {
            $db->beginTransaction();
            $db->prepare('update hotel_bookings set payment_status = :payment_status, payment_reference = coalesce(:reference, payment_reference), updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['id' => (int) $booking['id'], 'payment_status' => $paymentStatus, 'reference' => $reference === '' ? null : $reference]);
            $db->prepare(
                'update hotel_payment_transactions
                 set status = :status, reference = coalesce(:reference, reference),
                     gateway_response = :gateway_response, reconciled_at = CURRENT_TIMESTAMP,
                     reconciled_by = :reconciled_by, updated_at = CURRENT_TIMESTAMP
                 where booking_id = :booking_id'
            )->execute([
                'booking_id' => (int) $booking['id'],
                'status' => $status,
                'reference' => $reference === '' ? null : $reference,
                'gateway_response' => json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}',
                'reconciled_by' => 'webhook',
            ]);
            NotificationLog::record('customer', null, (string) $booking['guest_id'], 'Hotel payment updated', 'Payment for ' . $booking['booking_number'] . ' is now ' . $paymentStatus . '.', (int) $booking['id'], 'hotel');
            $this->markProcessed('hotel', $payload);
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->markFailed('hotel', $payload);
            Response::json(['message' => 'Webhook could not be processed. Please retry.'], 500);
            return;
        }
        Response::json(['message' => 'Webhook processed']);
    }

    public function service(): void
    {
        ServiceSchema::ensure();
        PaymentSchema::ensure();
        $payload = $this->payload('services');
        if ($payload === null) {
            return;
        }
        $bookingId = (int) ($payload['booking_id'] ?? 0);
        $bookingNumber = trim((string) ($payload['booking_number'] ?? ''));
        $reference = trim((string) ($payload['reference'] ?? $payload['payment_reference'] ?? ''));
        $status = $this->status((string) ($payload['status'] ?? 'paid'));
        if ($bookingId <= 0 && $bookingNumber === '') {
            Response::json(['message' => 'booking_id or booking_number is required'], 422);
            return;
        }

        $db = Database::connection();
        $lookup = $db->prepare('select * from service_bookings where id = :id or booking_number = :booking_number limit 1');
        $lookup->execute(['id' => $bookingId, 'booking_number' => $bookingNumber]);
        $booking = $lookup->fetch();
        if (!$booking) {
            Response::json(['message' => 'Booking not found'], 404);
            return;
        }
        if ($this->isDuplicate('services', $payload)) {
            Response::json(['message' => 'Webhook already processed']);
            return;
        }

        $paymentStatus = $status === 'paid' ? 'paid' : ($status === 'refunded' ? 'refunded' : 'payment_rejected');
        $transactionStatus = $status === 'paid' ? 'verified' : ($status === 'refunded' ? 'refunded' : 'rejected');
        try {
            $db->beginTransaction();
            $db->prepare('update service_bookings set payment_status = :payment_status, updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['id' => (int) $booking['id'], 'payment_status' => $paymentStatus]);
            $db->prepare(
                'update service_payment_transactions
                 set status = :status, reference = coalesce(:reference, reference),
                     note = :note, gateway_response = :gateway_response,
                     reconciled_at = CURRENT_TIMESTAMP, reconciled_by = :reconciled_by,
                     verified_at = :verified_at, updated_at = CURRENT_TIMESTAMP
                 where booking_id = :booking_id'
            )->execute([
                'booking_id' => (int) $booking['id'],
                'status' => $transactionStatus,
                'reference' => $reference === '' ? null : $reference,
                'note' => 'Webhook verified',
                'gateway_response' => json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}',
                'reconciled_by' => 'webhook',
                'verified_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null,
            ]);
            NotificationLog::record('customer', null, (string) $booking['guest_id'], 'Service payment updated', 'Payment for ' . $booking['booking_number'] . ' is now ' . $paymentStatus . '.', (int) $booking['id'], 'services');
            $this->markProcessed('services', $payload);
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->markFailed('services', $payload);
            Response::json(['message' => 'Webhook could not be processed. Please retry.'], 500);
            return;
        }
        Response::json(['message' => 'Webhook processed']);
    }

    public function restaurant(): void
    {
        RestaurantSchema::ensure();
        PaymentSchema::ensure();
        $payload = $this->payload('restaurant');
        if ($payload === null) {
            return;
        }
        $bookingId = (int) ($payload['booking_id'] ?? 0);
        $bookingNumber = trim((string) ($payload['booking_number'] ?? ''));
        $reference = trim((string) ($payload['reference'] ?? $payload['payment_reference'] ?? ''));
        $status = $this->status((string) ($payload['status'] ?? 'paid'));
        if ($bookingId <= 0 && $bookingNumber === '') {
            Response::json(['message' => 'booking_id or booking_number is required'], 422);
            return;
        }

        $db = Database::connection();
        $lookup = $db->prepare('select * from restaurant_bookings where id = :id or booking_number = :booking_number limit 1');
        $lookup->execute(['id' => $bookingId, 'booking_number' => $bookingNumber]);
        $booking = $lookup->fetch();
        if (!$booking) {
            Response::json(['message' => 'Booking not found'], 404);
            return;
        }
        if ($this->isDuplicate('restaurant', $payload)) {
            Response::json(['message' => 'Webhook already processed']);
            return;
        }

        $paymentStatus = $status === 'paid' ? 'paid' : ($status === 'refunded' ? 'refunded' : 'failed');
        $transactionStatus = $status === 'paid' ? 'verified' : ($status === 'refunded' ? 'refunded' : 'rejected');
        try {
            $db->beginTransaction();
            $db->prepare('update restaurant_bookings set payment_status = :payment_status, payment_reference = coalesce(:reference, payment_reference), payment_note = :payment_note, updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute([
                    'id' => (int) $booking['id'],
                    'payment_status' => $paymentStatus,
                    'reference' => $reference === '' ? null : $reference,
                    'payment_note' => 'Webhook verified',
                ]);
            $db->prepare(
                'update restaurant_payment_transactions
                 set status = :status, reference = coalesce(:reference, reference),
                     note = :note, gateway_response = :gateway_response,
                     reconciled_at = CURRENT_TIMESTAMP, reconciled_by = :reconciled_by,
                     verified_at = :verified_at, updated_at = CURRENT_TIMESTAMP
                 where booking_id = :booking_id'
            )->execute([
                'booking_id' => (int) $booking['id'],
                'status' => $transactionStatus,
                'reference' => $reference === '' ? null : $reference,
                'note' => 'Webhook verified',
                'gateway_response' => json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}',
                'reconciled_by' => 'webhook',
                'verified_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null,
            ]);
            NotificationLog::record('customer', null, (string) $booking['guest_id'], 'Restaurant payment updated', 'Payment for ' . $booking['booking_number'] . ' is now ' . $paymentStatus . '.', (int) $booking['id'], 'restaurant');
            $this->markProcessed('restaurant', $payload);
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->markFailed('restaurant', $payload);
            Response::json(['message' => 'Webhook could not be processed. Please retry.'], 500);
            return;
        }
        Response::json(['message' => 'Webhook processed']);
    }

    private function payload(string $moduleKey): ?array
    {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > 1048576) {
            Response::json(['message' => 'Webhook payload is too large'], 413);
            return null;
        }
        $raw = file_get_contents('php://input') ?: '';
        if (strlen($raw) > 1048576) {
            Response::json(['message' => 'Webhook payload is too large'], 413);
            return null;
        }
        $this->rawPayload = $raw;
        $secret = Settings::moduleGet($moduleKey, 'online_payment_webhook_secret');
        if ($secret !== '') {
            $signature = trim((string) ($_SERVER['HTTP_X_CITY_SIGNATURE'] ?? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? ''));
            if (str_starts_with(strtolower($signature), 'sha256=')) {
                $signature = substr($signature, 7);
            }
            $expected = hash_hmac('sha256', $raw, $secret);
            if ($signature === '' || !hash_equals($expected, $signature)) {
                Response::json(['message' => 'Invalid webhook signature'], 401);
                return null;
            }
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Response::json(['message' => 'Invalid JSON payload'], 422);
            return null;
        }
        return $decoded;
    }

    private function isDuplicate(string $moduleKey, array $payload): bool
    {
        $eventKey = $this->eventKey($payload);
        $hash = hash('sha256', $this->rawPayload);
        $db = Database::connection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        try {
            if ($driver === 'mysql') {
                $stmt = $db->prepare(
                    'insert into payment_webhook_events
                     (module_key, event_key, payload_hash, status, received_at, processed_at)
                     values (:module_key, :event_key, :payload_hash, \'processing\', CURRENT_TIMESTAMP, null)'
                );
                $stmt->execute(['module_key' => $moduleKey, 'event_key' => $eventKey, 'payload_hash' => $hash]);
                return false;
            }
            $stmt = $db->prepare(
                'insert into payment_webhook_events
                 (module_key, event_key, payload_hash, status, received_at, processed_at)
                 values (:module_key, :event_key, :payload_hash, \'processing\', CURRENT_TIMESTAMP, null)'
            );
            $stmt->execute(['module_key' => $moduleKey, 'event_key' => $eventKey, 'payload_hash' => $hash]);
            return false;
        } catch (\PDOException) {
            return !$this->reserveRetryableEvent($moduleKey, $eventKey, $hash);
        }
    }

    private function reserveRetryableEvent(string $moduleKey, string $eventKey, string $hash): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'select status, received_at
             from payment_webhook_events
             where module_key = :module_key and event_key = :event_key
             limit 1'
        );
        $stmt->execute([
            'module_key' => $moduleKey,
            'event_key' => $eventKey,
        ]);
        $event = $stmt->fetch();
        if (!$event) {
            return false;
        }
        $status = (string) ($event['status'] ?? '');
        if ($status === 'failed') {
            return $this->reserveEvent($moduleKey, $eventKey, $hash, '');
        }
        if ($status !== 'processing') {
            return false;
        }

        $receivedAt = strtotime((string) ($event['received_at'] ?? ''));
        if ($receivedAt === false || (time() - $receivedAt) < 900) {
            return false;
        }

        return $this->reserveEvent($moduleKey, $eventKey, $hash, ' and status = \'processing\'');
    }

    private function reserveEvent(string $moduleKey, string $eventKey, string $hash, string $extraWhere): bool
    {
        $db = Database::connection();
        $update = $db->prepare(
            'update payment_webhook_events
             set status = \'processing\', payload_hash = :payload_hash, received_at = CURRENT_TIMESTAMP, processed_at = null
             where module_key = :module_key and event_key = :event_key' . $extraWhere
        );
        $update->execute([
            'module_key' => $moduleKey,
            'event_key' => $eventKey,
            'payload_hash' => $hash,
        ]);

        return $update->rowCount() > 0;
    }

    private function markProcessed(string $moduleKey, array $payload): void
    {
        Database::connection()->prepare(
            'update payment_webhook_events
             set status = \'processed\', processed_at = CURRENT_TIMESTAMP
             where module_key = :module_key and event_key = :event_key'
        )->execute([
            'module_key' => $moduleKey,
            'event_key' => $this->eventKey($payload),
        ]);
    }

    private function markFailed(string $moduleKey, array $payload): void
    {
        try {
            Database::connection()->prepare(
                'update payment_webhook_events
                 set status = \'failed\', processed_at = null
                 where module_key = :module_key and event_key = :event_key and status = \'processing\''
            )->execute([
                'module_key' => $moduleKey,
                'event_key' => $this->eventKey($payload),
            ]);
        } catch (\Throwable) {
        }
    }

    private function eventKey(array $payload): string
    {
        foreach (['event_id', 'webhook_id', 'id', 'payment_id', 'transaction_id'] as $key) {
            $value = trim((string) ($payload[$key] ?? ''));
            if ($value !== '') {
                return substr(preg_replace('/[^a-zA-Z0-9_.:-]/', '_', $value) ?: hash('sha256', $value), 0, 190);
            }
        }

        return hash('sha256', $this->rawPayload);
    }

    private function status(string $status): string
    {
        return match (strtolower($status)) {
            'paid', 'captured', 'success', 'succeeded', 'verified' => 'paid',
            'refunded', 'refund' => 'refunded',
            default => 'rejected',
        };
    }
}
