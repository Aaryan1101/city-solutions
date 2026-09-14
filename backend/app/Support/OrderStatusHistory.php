<?php

declare(strict_types=1);

namespace App\Support;

final class OrderStatusHistory
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists order_status_history (
                    id bigint unsigned primary key auto_increment,
                    order_id bigint unsigned not null,
                    order_item_id bigint unsigned null,
                    status varchar(60) not null,
                    actor_type varchar(40) not null,
                    actor_name varchar(190) null,
                    note text null,
                    created_at timestamp null,
                    index order_status_history_order_id_index (order_id)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists order_status_history (
                    id integer primary key autoincrement,
                    order_id integer not null,
                    order_item_id integer,
                    status text not null,
                    actor_type text not null,
                    actor_name text,
                    note text,
                    created_at text
                )'
            );
        }
    }

    public static function record(
        int $orderId,
        ?int $orderItemId,
        string $status,
        string $actorType,
        string $actorName = '',
        string $note = ''
    ): void {
        $db = Database::connection();
        if (!$db->inTransaction()) {
            self::ensure();
        }

        $stmt = $db->prepare(
            'insert into order_status_history (order_id, order_item_id, status, actor_type, actor_name, note, created_at)
             values (:order_id, :order_item_id, :status, :actor_type, :actor_name, :note, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'status' => $status,
            'actor_type' => $actorType,
            'actor_name' => $actorName === '' ? null : $actorName,
            'note' => $note === '' ? null : $note,
        ]);
    }
}
