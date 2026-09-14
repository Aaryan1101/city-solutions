<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Response;
use App\Support\SupportChatSchema;
use App\Support\View;

final class SupportController
{
    public function index(): void
    {
        Auth::requireAdmin();
        SupportChatSchema::ensure();
        $threads = Database::connection()->query(
            'select support_threads.*, vendors.shop_name as vendor_name,
                    (
                        select count(*) from support_messages
                        where support_messages.thread_id = support_threads.id
                            and support_messages.sender_type != \'admin\'
                            and support_messages.admin_read_at is null
                    ) as unread_count
             from support_threads
             left join vendors on vendors.id = support_threads.vendor_id
             order by support_threads.id desc'
        )->fetchAll();
        View::render('admin/support', ['title' => 'Support', 'threads' => $threads]);
    }

    public function show(int $id): void
    {
        Auth::requireAdmin();
        SupportChatSchema::ensure();
        $thread = Database::connection()->prepare('select * from support_threads where id = :id limit 1');
        $thread->execute(['id' => $id]);
        Database::connection()->prepare('update support_messages set admin_read_at = CURRENT_TIMESTAMP where thread_id = :id and sender_type != \'admin\' and admin_read_at is null')
            ->execute(['id' => $id]);
        $messages = Database::connection()->prepare('select * from support_messages where thread_id = :id order by id asc');
        $messages->execute(['id' => $id]);
        $vendors = Database::connection()->query('select id, shop_name from vendors where status = \'approved\' order by shop_name asc')->fetchAll();
        View::render('admin/support_show', [
            'title' => 'Support Thread',
            'thread' => $thread->fetch(),
            'messages' => $messages->fetchAll(),
            'vendors' => $vendors,
        ]);
    }

    public function reply(int $id): void
    {
        Auth::requireAdmin();
        SupportChatSchema::ensure();
        $message = trim($_POST['message'] ?? '');
        if ($message !== '') {
            $stmt = Database::connection()->prepare(
            'insert into support_messages (thread_id, sender_type, sender_name, message, attachment_url, created_at)
                 values (:thread_id, \'admin\', \'Admin\', :message, :attachment_url, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                'thread_id' => $id,
                'message' => $message,
                'attachment_url' => trim($_POST['attachment_url'] ?? '') ?: null,
            ]);
            Database::connection()->prepare('update support_threads set status = :status, updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['status' => $_POST['status'] ?? 'open', 'id' => $id]);
        }
        Response::redirect('/admin/support/' . $id);
    }

    public function assignVendor(int $id): void
    {
        Auth::requireAdmin();
        SupportChatSchema::ensure();
        $vendorId = (int) ($_POST['vendor_id'] ?? 0);
        Database::connection()->prepare(
            'update support_threads set vendor_id = :vendor_id, updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute([
            'id' => $id,
            'vendor_id' => $vendorId > 0 ? $vendorId : null,
        ]);

        Response::redirect('/admin/support/' . $id);
    }
}
