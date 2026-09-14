<?php

declare(strict_types=1);

namespace App\Support;

final class VendorSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists vendors (
                    id bigint unsigned primary key auto_increment,
                    module_key varchar(40) not null default \'mart\',
                    shop_name varchar(190) not null,
                    owner_name varchar(190) not null,
                    phone varchar(60) not null,
                    email varchar(190) null,
                    password varchar(255) not null,
                    auth_token varchar(128) null,
                    address text null,
                    city varchar(120) null,
                    status varchar(40) not null default \'pending\',
                    admin_note text null,
                    created_at timestamp null,
                    updated_at timestamp null,
                    unique key vendors_module_phone_unique (module_key, phone)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists vendors (
                    id integer primary key autoincrement,
                    module_key text not null default "mart",
                    shop_name text not null,
                    owner_name text not null,
                    phone text not null,
                    email text,
                    password text not null,
                    auth_token text,
                    address text,
                    city text,
                    status text not null default "pending",
                    admin_note text,
                    created_at text,
                    updated_at text
                )'
            );
        }

        self::ensureVendorColumn($db, 'module_key', 'module');
        self::ensureVendorColumn($db, 'zone_id', 'id');
        self::ensureModulePhoneIndex($db);
        self::ensureProductsVendorColumn($db);
        self::ensureColumn($db, 'products', 'zone_id', 'id');
        self::ensureOrdersVendorColumn($db);
        self::ensureColumn($db, 'orders', 'zone_id', 'id');
        self::ensureOrderItemsVendorColumn($db);
        self::ensureOrderItemsStatusColumn($db);
        self::ensureVendorColumn($db, 'logo', 'text');
        self::ensureVendorColumn($db, 'description', 'text');
        self::ensureVendorColumn($db, 'is_temporarily_closed', 'bool');
        self::ensureVendorColumn($db, 'vacation_starts_at', 'datetime');
        self::ensureVendorColumn($db, 'vacation_ends_at', 'datetime');
        self::ensureVendorColumn($db, 'vacation_note', 'text');
    }

    private static function ensureProductsVendorColumn(\PDO $db): void
    {
        try {
            $db->query('select vendor_id from products limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer';
            $db->exec('alter table products add column vendor_id ' . $type);
        }
    }

    private static function ensureOrdersVendorColumn(\PDO $db): void
    {
        try {
            $db->query('select vendor_id from orders limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer';
            $db->exec('alter table orders add column vendor_id ' . $type);
        }
    }

    private static function ensureOrderItemsVendorColumn(\PDO $db): void
    {
        try {
            $db->query('select vendor_id from order_items limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer';
            $db->exec('alter table order_items add column vendor_id ' . $type);
        }
    }

    private static function ensureOrderItemsStatusColumn(\PDO $db): void
    {
        try {
            $db->query('select status from order_items limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(60) not null default \'pending\'' : 'text not null default "pending"';
            $db->exec('alter table order_items add column status ' . $type);
        }
    }

    private static function ensureVendorColumn(\PDO $db, string $column, string $type): void
    {
        try {
            $db->query('select ' . $column . ' from vendors limit 1');
        } catch (\Throwable) {
            $sqlType = match ($type) {
                'bool' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'tinyint(1) not null default 0'
                    : 'integer not null default 0',
                'datetime' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'datetime null'
                    : 'text',
                'module' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'varchar(40) not null default \'mart\''
                    : 'text not null default "mart"',
                'id' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'bigint unsigned null'
                    : 'integer',
                default => 'text',
            };
            $db->exec('alter table vendors add column ' . $column . ' ' . $sqlType);
        }
    }

    private static function ensureColumn(\PDO $db, string $table, string $column, string $type): void
    {
        try {
            $db->query('select ' . $column . ' from ' . $table . ' limit 1');
        } catch (\Throwable) {
            $sqlType = $type === 'id' && $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                ? 'bigint unsigned null'
                : 'integer';
            $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $sqlType);
        }
    }

    private static function ensureModulePhoneIndex(\PDO $db): void
    {
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        $indexes = $db->query('show index from vendors')->fetchAll();
        foreach ($indexes as $index) {
            $keyName = (string) ($index['Key_name'] ?? '');
            $column = (string) ($index['Column_name'] ?? '');
            $nonUnique = (int) ($index['Non_unique'] ?? 1);
            if ($column === 'phone' && $nonUnique === 0 && $keyName !== 'vendors_module_phone_unique') {
                $db->exec('alter table vendors drop index `' . str_replace('`', '``', $keyName) . '`');
            }
        }

        $hasComposite = false;
        foreach ($indexes as $index) {
            if (($index['Key_name'] ?? '') === 'vendors_module_phone_unique') {
                $hasComposite = true;
                break;
            }
        }
        if (!$hasComposite) {
            $db->exec('alter table vendors add unique key vendors_module_phone_unique (module_key, phone)');
        }
    }
}
