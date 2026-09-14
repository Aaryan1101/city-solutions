<?php

declare(strict_types=1);

namespace App\Support;

final class PaymentSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists payment_transactions (
                    id bigint unsigned primary key auto_increment,
                    module_key varchar(40) not null default \'mart\',
                    order_id bigint unsigned not null,
                    guest_id varchar(120) null,
                    customer_name varchar(190) null,
                    customer_phone varchar(60) null,
                    payment_method varchar(60) not null,
                    amount decimal(12,2) not null default 0,
                    reference varchar(255) null,
                    note text null,
                    status varchar(40) not null default \'pending\',
                    admin_note text null,
                    gateway_response text null,
                    reconciled_at timestamp null,
                    reconciled_by varchar(190) null,
                    created_at timestamp null,
                    updated_at timestamp null,
                    index payment_transactions_order_id_index (order_id),
                    index payment_transactions_status_index (status)
                )'
            );
            $db->exec(
                'create table if not exists payment_webhook_events (
                    id bigint unsigned primary key auto_increment,
                    module_key varchar(40) not null,
                    event_key varchar(190) not null,
                    payload_hash varchar(64) not null,
                    status varchar(40) not null default \'processed\',
                    received_at timestamp null,
                    processed_at timestamp null,
                    unique key payment_webhook_events_key_unique (module_key, event_key),
                    index payment_webhook_events_hash_index (payload_hash)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists payment_transactions (
                    id integer primary key autoincrement,
                    module_key text not null default "mart",
                    order_id integer not null,
                    guest_id text,
                    customer_name text,
                    customer_phone text,
                    payment_method text not null,
                    amount real not null default 0,
                    reference text,
                    note text,
                    status text not null default "pending",
                    admin_note text,
                    gateway_response text,
                    reconciled_at text,
                    reconciled_by text,
                    created_at text,
                    updated_at text
                )'
            );
            $db->exec(
                'create table if not exists payment_webhook_events (
                    id integer primary key autoincrement,
                    module_key text not null,
                    event_key text not null,
                    payload_hash text not null,
                    status text not null default "processed",
                    received_at text,
                    processed_at text
                )'
            );
            $db->exec(
                'create unique index if not exists payment_webhook_events_key_unique on payment_webhook_events (module_key, event_key)'
            );
            $db->exec(
                'create index if not exists payment_webhook_events_hash_index on payment_webhook_events (payload_hash)'
            );
        }

        self::addColumnIfMissing('payment_transactions', 'module_key', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(40) not null default \'mart\'' : 'text not null default "mart"');
        self::addColumnIfMissing('payment_transactions', 'gateway_response', 'text');
        self::addColumnIfMissing('payment_transactions', 'reconciled_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('payment_transactions', 'reconciled_by', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(190) null' : 'text');
        self::addColumnIfMissing('payment_webhook_events', 'event_key', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(190) not null' : 'text not null default ""');
        self::addColumnIfMissing('payment_webhook_events', 'payload_hash', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(64) not null' : 'text not null default ""');
        self::addColumnIfMissing('payment_webhook_events', 'status', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(40) not null default \'processed\'' : 'text not null default "processed"');
        self::addColumnIfMissing('payment_webhook_events', 'received_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('payment_webhook_events', 'processed_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
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
