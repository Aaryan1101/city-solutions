<?php

declare(strict_types=1);

namespace App\Controllers\Api\Ecommerce;

use App\Support\Database;
use App\Support\NotificationSchema;
use App\Support\Request;
use App\Support\Response;

final class NotificationController
{
    public function index(): void
    {
        NotificationSchema::ensure();
        $guestId = trim($_GET['guest_id'] ?? '');
        if ($guestId === '') {
            Response::json(['data' => [], 'unread_count' => 0]);
            return;
        }

        $stmt = Database::connection()->prepare(
            'select * from notifications where recipient_type = :type and guest_id = :guest_id order by id desc limit 100'
        );
        $stmt->execute(['type' => 'customer', 'guest_id' => $guestId]);
        $notifications = $stmt->fetchAll();
        $count = Database::connection()->prepare(
            'select count(*) from notifications where recipient_type = :type and guest_id = :guest_id and read_at is null'
        );
        $count->execute(['type' => 'customer', 'guest_id' => $guestId]);
        Response::json(['data' => $notifications, 'unread_count' => (int) $count->fetchColumn()]);
    }

    public function markRead(): void
    {
        NotificationSchema::ensure();
        $body = Request::json();
        $guestId = trim($body['guest_id'] ?? $_GET['guest_id'] ?? '');
        if ($guestId !== '') {
            $stmt = Database::connection()->prepare(
                'update notifications set read_at = CURRENT_TIMESTAMP where recipient_type = :type and guest_id = :guest_id and read_at is null'
            );
            $stmt->execute(['type' => 'customer', 'guest_id' => $guestId]);
        }
        Response::json(['message' => 'Notifications marked as read']);
    }
}
