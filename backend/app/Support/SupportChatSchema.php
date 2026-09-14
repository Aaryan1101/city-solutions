<?php

declare(strict_types=1);

namespace App\Support;

final class SupportChatSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists support_threads (
                    id bigint unsigned primary key auto_increment,
                    module_key varchar(40) not null default \'mart\',
                    subject varchar(190) not null,
                    guest_id varchar(190) null,
                    customer_id bigint unsigned null,
                    vendor_id bigint unsigned null,
                    order_id bigint unsigned null,
                    booking_id bigint unsigned null,
                    status varchar(40) not null default \'open\',
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
            $db->exec(
                'create table if not exists support_messages (
                    id bigint unsigned primary key auto_increment,
                    thread_id bigint unsigned not null,
                    sender_type varchar(40) not null,
                    sender_name varchar(190) null,
                    message text not null,
                    attachment_url varchar(255) null,
                    customer_read_at timestamp null,
                    admin_read_at timestamp null,
                    vendor_read_at timestamp null,
                    created_at timestamp null,
                    index support_messages_thread_index (thread_id)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists support_threads (
                    id integer primary key autoincrement,
                    module_key text not null default "mart",
                    subject text not null,
                    guest_id text,
                    customer_id integer,
                    vendor_id integer,
                    order_id integer,
                    booking_id integer,
                    status text not null default "open",
                    created_at text,
                    updated_at text
                )'
            );
            $db->exec(
                'create table if not exists support_messages (
                    id integer primary key autoincrement,
                    thread_id integer not null,
                    sender_type text not null,
                    sender_name text,
                    message text not null,
                    attachment_url text,
                    customer_read_at text,
                    admin_read_at text,
                    vendor_read_at text,
                    created_at text
                )'
            );
        }
        self::addColumnIfMissing('support_threads', 'customer_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('support_threads', 'module_key', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(40) not null default \'mart\'' : 'text not null default "mart"');
        self::addColumnIfMissing('support_threads', 'vendor_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('support_threads', 'order_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('support_threads', 'booking_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('support_messages', 'attachment_url', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(255) null' : 'text');
        self::addColumnIfMissing('support_messages', 'customer_read_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('support_messages', 'admin_read_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('support_messages', 'vendor_read_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
    }

    private static function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $stmt = $db->prepare(
                'select count(*) from information_schema.columns
                 where table_schema = database() and table_name = :table and column_name = :column'
            );
            $stmt->execute(['table' => $table, 'column' => $column]);
            if ((int) $stmt->fetchColumn() === 0) {
                $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $definition);
            }
            return;
        }

        $columns = $db->query('pragma table_info(' . $table . ')')->fetchAll();
        foreach ($columns as $existing) {
            if (($existing['name'] ?? '') === $column) {
                return;
            }
        }
        $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $definition);
    }
}
