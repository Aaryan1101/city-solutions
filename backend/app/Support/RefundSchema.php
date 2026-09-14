<?php

declare(strict_types=1);

namespace App\Support;

final class RefundSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists refund_requests (
                    id bigint unsigned primary key auto_increment,
                    order_id bigint unsigned not null,
                    order_item_id bigint unsigned null,
                    vendor_id bigint unsigned null,
                    guest_id varchar(120) null,
                    customer_name varchar(190) null,
                    customer_phone varchar(60) null,
                    amount decimal(12,2) not null default 0,
                    reason varchar(255) not null,
                    note text null,
                    admin_note text null,
                    status varchar(40) not null default \'pending\',
                    created_at timestamp null,
                    updated_at timestamp null,
                    index refund_requests_order_id_index (order_id),
                    index refund_requests_vendor_id_index (vendor_id)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists refund_requests (
                    id integer primary key autoincrement,
                    order_id integer not null,
                    order_item_id integer,
                    vendor_id integer,
                    guest_id text,
                    customer_name text,
                    customer_phone text,
                    amount real not null default 0,
                    reason text not null,
                    note text,
                    admin_note text,
                    status text not null default "pending",
                    created_at text,
                    updated_at text
                )'
            );
        }
    }
}
