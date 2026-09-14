<?php

declare(strict_types=1);

namespace App\Support;

final class NotificationSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists notifications (
                    id bigint unsigned primary key auto_increment,
                    recipient_type varchar(40) not null,
                    recipient_id bigint unsigned null,
                    guest_id varchar(120) null,
                    title varchar(190) not null,
                    message text null,
                    order_id bigint unsigned null,
                    read_at timestamp null,
                    created_at timestamp null,
                    index notifications_recipient_index (recipient_type, recipient_id),
                    index notifications_guest_id_index (guest_id)
                )'
            );
            $db->exec(
                'create table if not exists device_tokens (
                    id bigint unsigned primary key auto_increment,
                    module_key varchar(40) not null default \'mart\',
                    owner_type varchar(40) not null default \'customer\',
                    owner_id bigint unsigned null,
                    guest_id varchar(190) null,
                    token text not null,
                    platform varchar(40) null,
                    last_seen_at timestamp null,
                    created_at timestamp null,
                    updated_at timestamp null,
                    index device_tokens_guest_index (module_key, owner_type, guest_id),
                    index device_tokens_owner_index (module_key, owner_type, owner_id)
                )'
            );
            $db->exec(
                'create table if not exists push_outbox (
                    id bigint unsigned primary key auto_increment,
                    module_key varchar(40) not null,
                    recipient_type varchar(40) not null,
                    recipient_id bigint unsigned null,
                    guest_id varchar(190) null,
                    title varchar(190) not null,
                    message text null,
                    data_json text null,
                    attempts int not null default 0,
                    last_error text null,
                    sent_at timestamp null,
                    created_at timestamp null,
                    updated_at timestamp null,
                    index push_outbox_pending_index (sent_at, attempts)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists notifications (
                    id integer primary key autoincrement,
                    recipient_type text not null,
                    recipient_id integer,
                    guest_id text,
                    title text not null,
                    message text,
                    order_id integer,
                    read_at text,
                    created_at text
                )'
            );
            $db->exec(
                'create table if not exists device_tokens (
                    id integer primary key autoincrement,
                    module_key text not null default "mart",
                    owner_type text not null default "customer",
                    owner_id integer,
                    guest_id text,
                    token text not null,
                    platform text,
                    last_seen_at text,
                    created_at text,
                    updated_at text
                )'
            );
            $db->exec(
                'create table if not exists push_outbox (
                    id integer primary key autoincrement,
                    module_key text not null,
                    recipient_type text not null,
                    recipient_id integer,
                    guest_id text,
                    title text not null,
                    message text,
                    data_json text,
                    attempts integer not null default 0,
                    last_error text,
                    sent_at text,
                    created_at text,
                    updated_at text
                )'
            );
        }
    }
}
