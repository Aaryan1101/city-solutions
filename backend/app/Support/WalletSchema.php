<?php

declare(strict_types=1);

namespace App\Support;

final class WalletSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists wallet_accounts (
                    id bigint unsigned primary key auto_increment,
                    owner_type varchar(40) not null,
                    owner_key varchar(190) not null,
                    balance decimal(12,2) not null default 0,
                    created_at timestamp null,
                    updated_at timestamp null,
                    unique key wallet_accounts_owner_unique (owner_type, owner_key)
                )'
            );
            $db->exec(
                'create table if not exists wallet_ledgers (
                    id bigint unsigned primary key auto_increment,
                    wallet_account_id bigint unsigned not null,
                    owner_type varchar(40) not null,
                    owner_key varchar(190) not null,
                    direction varchar(20) not null,
                    amount decimal(12,2) not null default 0,
                    entry_type varchar(60) not null,
                    reference_key varchar(190) null,
                    description text null,
                    created_at timestamp null,
                    updated_at timestamp null,
                    unique key wallet_ledgers_reference_unique (owner_type, owner_key, reference_key),
                    index wallet_ledgers_account_index (wallet_account_id)
                )'
            );
            $db->exec(
                'create table if not exists withdrawal_requests (
                    id bigint unsigned primary key auto_increment,
                    vendor_id bigint unsigned not null default 0,
                    owner_type varchar(40) not null default \'vendor\',
                    owner_key varchar(190) null,
                    amount decimal(12,2) not null default 0,
                    bank_details text null,
                    note text null,
                    status varchar(40) not null default \'pending\',
                    admin_note text null,
                    created_at timestamp null,
                    updated_at timestamp null,
                    index withdrawal_requests_vendor_index (vendor_id),
                    index withdrawal_requests_status_index (status)
                )'
            );
            $db->exec(
                'create table if not exists vendor_settlements (
                    id bigint unsigned primary key auto_increment,
                    vendor_id bigint unsigned not null,
                    withdrawal_id bigint unsigned null,
                    amount decimal(12,2) not null default 0,
                    commission_amount decimal(12,2) not null default 0,
                    payment_reference varchar(190) null,
                    note text null,
                    status varchar(40) not null default \'paid\',
                    settled_at timestamp null,
                    created_at timestamp null,
                    updated_at timestamp null,
                    index vendor_settlements_vendor_index (vendor_id)
                )'
            );
            self::addColumnIfMissing('withdrawal_requests', 'owner_type', 'varchar(40) not null default \'vendor\'');
            self::addColumnIfMissing('withdrawal_requests', 'owner_key', 'varchar(190) null');
        } else {
            $db->exec(
                'create table if not exists wallet_accounts (
                    id integer primary key autoincrement,
                    owner_type text not null,
                    owner_key text not null,
                    balance real not null default 0,
                    created_at text,
                    updated_at text
                )'
            );
            $db->exec(
                'create unique index if not exists wallet_accounts_owner_unique on wallet_accounts (owner_type, owner_key)'
            );
            $db->exec(
                'create table if not exists wallet_ledgers (
                    id integer primary key autoincrement,
                    wallet_account_id integer not null,
                    owner_type text not null,
                    owner_key text not null,
                    direction text not null,
                    amount real not null default 0,
                    entry_type text not null,
                    reference_key text,
                    description text,
                    created_at text,
                    updated_at text
                )'
            );
            $db->exec(
                'create unique index if not exists wallet_ledgers_reference_unique on wallet_ledgers (owner_type, owner_key, reference_key)'
            );
            $db->exec(
                'create table if not exists withdrawal_requests (
                    id integer primary key autoincrement,
                    vendor_id integer not null default 0,
                    owner_type text not null default "vendor",
                    owner_key text,
                    amount real not null default 0,
                    bank_details text,
                    note text,
                    status text not null default "pending",
                    admin_note text,
                    created_at text,
                    updated_at text
                )'
            );
            $db->exec(
                'create table if not exists vendor_settlements (
                    id integer primary key autoincrement,
                    vendor_id integer not null,
                    withdrawal_id integer,
                    amount real not null default 0,
                    commission_amount real not null default 0,
                    payment_reference text,
                    note text,
                    status text not null default "paid",
                    settled_at text,
                    created_at text,
                    updated_at text
                )'
            );
            self::addColumnIfMissing('withdrawal_requests', 'owner_type', 'text not null default "vendor"');
            self::addColumnIfMissing('withdrawal_requests', 'owner_key', 'text');
        }
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
