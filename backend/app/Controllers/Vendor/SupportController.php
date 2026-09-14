<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\Response;
use App\Support\SupportChatSchema;
use App\Support\VendorAuth;
use App\Support\View;

final class SupportController
{
    public function index(): void
    {
        VendorAuth::requireVendor();
        SupportChatSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select support_threads.*,
                    (select count(*) from support_messages
                     where support_messages.thread_id = support_threads.id
                       and sender_type != \'vendor\'
                       and vendor_read_at is null) as unread_count
             from support_threads
             where vendor_id = :vendor_id
             order by updated_at desc, id desc'
        );
        $stmt->execute(['vendor_id' => VendorAuth::id()]);

        View::render('vendor/support', [
            'title' => 'Support',
            'threads' => $stmt->fetchAll(),
        ]);
    }

    public function show(int $id): void
    {
        VendorAuth::requireVendor();
        SupportChatSchema::ensure();
        $thread = $this->thread($id);
        if (!$thread) {
            Response::redirect('/vendor/support');
        }

        Database::connection()->prepare(
            'update support_messages set vendor_read_at = CURRENT_TIMESTAMP
             where thread_id = :thread_id and sender_type != \'vendor\' and vendor_read_at is null'
        )->execute(['thread_id' => $id]);

        $messages = Database::connection()->prepare('select * from support_messages where thread_id = :thread_id order by id asc');
        $messages->execute(['thread_id' => $id]);

        View::render('vendor/support_show', [
            'title' => 'Support Thread',
            'thread' => $thread,
            'messages' => $messages->fetchAll(),
        ]);
    }

    public function reply(int $id): void
    {
        VendorAuth::requireVendor();
        SupportChatSchema::ensure();
        if (!$this->thread($id)) {
            Response::redirect('/vendor/support');
        }

        $message = trim($_POST['message'] ?? '');
        if ($message !== '') {
            $stmt = Database::connection()->prepare(
                'insert into support_messages (thread_id, sender_type, sender_name, message, attachment_url, created_at)
                 values (:thread_id, \'vendor\', :sender_name, :message, :attachment_url, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                'thread_id' => $id,
                'sender_name' => $_SESSION['vendor_name'] ?? 'Vendor',
                'message' => $message,
                'attachment_url' => trim($_POST['attachment_url'] ?? '') ?: null,
            ]);
            Database::connection()->prepare(
                'update support_threads set status = \'open\', updated_at = CURRENT_TIMESTAMP where id = :id'
            )->execute(['id' => $id]);
        }

        Response::redirect('/vendor/support/' . $id);
    }

    private function thread(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'select * from support_threads where id = :id and vendor_id = :vendor_id limit 1'
        );
        $stmt->execute(['id' => $id, 'vendor_id' => VendorAuth::id()]);
        $thread = $stmt->fetch();
        return $thread ?: null;
    }
}
