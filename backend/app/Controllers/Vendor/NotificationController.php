<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\NotificationSchema;
use App\Support\Response;
use App\Support\VendorAuth;
use App\Support\View;

final class NotificationController
{
    public function index(): void
    {
        VendorAuth::requireVendor();
        NotificationSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select * from notifications where recipient_type = :type and recipient_id = :recipient_id order by id desc limit 100'
        );
        $stmt->execute(['type' => 'vendor', 'recipient_id' => VendorAuth::id()]);
        View::render('vendor/notifications', [
            'title' => 'Vendor Notifications',
            'notifications' => $stmt->fetchAll(),
        ]);
    }

    public function markRead(): void
    {
        VendorAuth::requireVendor();
        NotificationSchema::ensure();
        $stmt = Database::connection()->prepare(
            'update notifications set read_at = CURRENT_TIMESTAMP where recipient_type = :type and recipient_id = :recipient_id and read_at is null'
        );
        $stmt->execute(['type' => 'vendor', 'recipient_id' => VendorAuth::id()]);
        Response::redirect('/vendor/notifications');
    }
}
