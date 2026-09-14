<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\NotificationSchema;
use App\Support\Response;
use App\Support\View;

final class NotificationController
{
    public function index(): void
    {
        Auth::requireAdmin();
        NotificationSchema::ensure();
        $notifications = Database::connection()
            ->query('select * from notifications where recipient_type = \'admin\' order by id desc limit 100')
            ->fetchAll();
        View::render('admin/notifications', [
            'title' => 'Notifications',
            'notifications' => $notifications,
        ]);
    }

    public function markRead(): void
    {
        Auth::requireAdmin();
        NotificationSchema::ensure();
        Database::connection()->exec('update notifications set read_at = CURRENT_TIMESTAMP where recipient_type = \'admin\' and read_at is null');
        Response::redirect('/admin/notifications');
    }
}
