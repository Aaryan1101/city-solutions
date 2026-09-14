<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\SupportChatSchema;

final class SupportController
{
    public function __construct(private readonly string $moduleKey = 'mart')
    {
    }

    public function index(): void
    {
        SupportChatSchema::ensure();
        $guestId = trim($_GET['guest_id'] ?? '');
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }

        $stmt = Database::connection()->prepare('select * from support_threads where guest_id = :guest_id and module_key = :module_key order by id desc');
        $stmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        Response::json(['data' => array_map([$this, 'withUnread'], $stmt->fetchAll())]);
    }

    public function show(int $id): void
    {
        SupportChatSchema::ensure();
        $guestId = trim($_GET['guest_id'] ?? '');
        $thread = Database::connection()->prepare('select * from support_threads where id = :id and guest_id = :guest_id and module_key = :module_key limit 1');
        $thread->execute(['id' => $id, 'guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $row = $thread->fetch();
        if (!$row) {
            Response::json(['message' => 'Support thread not found'], 404);
            return;
        }

        $messages = Database::connection()->prepare('select * from support_messages where thread_id = :thread_id order by id asc');
        $messages->execute(['thread_id' => $id]);
        Database::connection()->prepare('update support_messages set customer_read_at = CURRENT_TIMESTAMP where thread_id = :thread_id and sender_type != \'customer\' and customer_read_at is null')
            ->execute(['thread_id' => $id]);
        Response::json(['data' => $row, 'messages' => $messages->fetchAll()]);
    }

    public function store(): void
    {
        SupportChatSchema::ensure();
        $body = Request::json();
        $guestId = trim((string) ($body['guest_id'] ?? ''));
        $subject = trim((string) ($body['subject'] ?? 'Support request'));
        $message = trim((string) ($body['message'] ?? ''));
        if ($guestId === '' || $message === '') {
            Response::json(['message' => 'Subject and message are required'], 422);
            return;
        }

        $db = Database::connection();
        $thread = $db->prepare(
            'insert into support_threads (module_key, subject, guest_id, customer_id, vendor_id, order_id, booking_id, status, created_at, updated_at)
             values (:module_key, :subject, :guest_id, :customer_id, :vendor_id, :order_id, :booking_id, \'open\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $thread->execute([
            'module_key' => $this->moduleKey,
            'subject' => $subject,
            'guest_id' => $guestId,
            'customer_id' => (int) ($body['customer_id'] ?? 0) ?: null,
            'vendor_id' => (int) ($body['vendor_id'] ?? 0) ?: null,
            'order_id' => (int) ($body['order_id'] ?? 0) ?: null,
            'booking_id' => (int) ($body['booking_id'] ?? 0) ?: null,
        ]);
        $threadId = (int) $db->lastInsertId();
        $this->insertMessage($threadId, 'customer', trim((string) ($body['sender_name'] ?? 'Customer')), $message, trim((string) ($body['attachment_url'] ?? '')));
        $this->showWithId($threadId);
    }

    public function reply(int $id): void
    {
        SupportChatSchema::ensure();
        $body = Request::json();
        $guestId = trim((string) ($body['guest_id'] ?? ''));
        $message = trim((string) ($body['message'] ?? ''));
        $thread = Database::connection()->prepare('select id from support_threads where id = :id and guest_id = :guest_id and module_key = :module_key limit 1');
        $thread->execute(['id' => $id, 'guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        if (!$thread->fetch() || $message === '') {
            Response::json(['message' => 'Message could not be sent'], 422);
            return;
        }
        $this->insertMessage($id, 'customer', trim((string) ($body['sender_name'] ?? 'Customer')), $message, trim((string) ($body['attachment_url'] ?? '')));
        $this->showWithId($id);
    }

    private function insertMessage(int $threadId, string $senderType, string $senderName, string $message, string $attachmentUrl = ''): void
    {
        $stmt = Database::connection()->prepare(
            'insert into support_messages (thread_id, sender_type, sender_name, message, attachment_url, created_at)
             values (:thread_id, :sender_type, :sender_name, :message, :attachment_url, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'thread_id' => $threadId,
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'message' => $message,
            'attachment_url' => $attachmentUrl === '' ? null : $attachmentUrl,
        ]);
        Database::connection()->prepare('update support_threads set updated_at = CURRENT_TIMESTAMP where id = :id')
            ->execute(['id' => $threadId]);
    }

    private function showWithId(int $id): void
    {
        $thread = Database::connection()->prepare('select * from support_threads where id = :id limit 1');
        $thread->execute(['id' => $id]);
        $messages = Database::connection()->prepare('select * from support_messages where thread_id = :thread_id order by id asc');
        $messages->execute(['thread_id' => $id]);
        Response::json(['data' => $thread->fetch(), 'messages' => $messages->fetchAll()]);
    }

    private function withUnread(array $thread): array
    {
        $count = Database::connection()->prepare(
            'select count(*) from support_messages where thread_id = :thread_id and sender_type != \'customer\' and customer_read_at is null'
        );
        $count->execute(['thread_id' => $thread['id']]);
        $thread['unread_count'] = (int) $count->fetchColumn();
        return $thread;
    }
}
