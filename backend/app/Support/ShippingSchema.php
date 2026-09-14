<?php

declare(strict_types=1);

namespace App\Support;

final class ShippingSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists shipping_methods (
                    id bigint unsigned primary key auto_increment,
                    name varchar(120) not null,
                    description text null,
                    cost decimal(12,2) not null default 0,
                    expected_days varchar(80) null,
                    sort_order int not null default 0,
                    status tinyint not null default 1,
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
            self::addColumnIfMissing('orders', 'shipping_method_id', 'bigint unsigned null');
            self::addColumnIfMissing('orders', 'shipping_method_name', 'varchar(120) null');
            self::addColumnIfMissing('orders', 'shipping_cost', 'decimal(12,2) not null default 0');
            self::addColumnIfMissing('orders', 'expected_delivery', 'varchar(80) null');
            self::addColumnIfMissing('orders', 'tracking_provider', 'varchar(120) null');
            self::addColumnIfMissing('orders', 'tracking_number', 'varchar(190) null');
            self::addColumnIfMissing('orders', 'tracking_url', 'varchar(255) null');
        } else {
            $db->exec(
                'create table if not exists shipping_methods (
                    id integer primary key autoincrement,
                    name text not null,
                    description text,
                    cost real not null default 0,
                    expected_days text,
                    sort_order integer not null default 0,
                    status integer not null default 1,
                    created_at text,
                    updated_at text
                )'
            );
            self::addColumnIfMissing('orders', 'shipping_method_id', 'integer');
            self::addColumnIfMissing('orders', 'shipping_method_name', 'text');
            self::addColumnIfMissing('orders', 'shipping_cost', 'real not null default 0');
            self::addColumnIfMissing('orders', 'expected_delivery', 'text');
            self::addColumnIfMissing('orders', 'tracking_provider', 'text');
            self::addColumnIfMissing('orders', 'tracking_number', 'text');
            self::addColumnIfMissing('orders', 'tracking_url', 'text');
        }
    }

    public static function activeMethods(?string $moduleKey = null): array
    {
        self::ensure();
        $rows = Database::connection()->query(
            'select * from shipping_methods where status = 1 order by sort_order asc, id asc'
        )->fetchAll();
        if ($rows !== []) {
            return $rows;
        }

        return [[
            'id' => 0,
            'name' => 'Standard Delivery',
            'description' => 'Default local delivery.',
            'cost' => $moduleKey === null ? Settings::float('delivery_charge') : Settings::moduleFloat($moduleKey, 'delivery_charge'),
            'expected_days' => 'Today or tomorrow',
            'sort_order' => 0,
            'status' => 1,
        ]];
    }

    public static function method(int $id, ?string $moduleKey = null): array
    {
        $methods = self::activeMethods($moduleKey);
        foreach ($methods as $method) {
            if ((int) $method['id'] === $id) {
                return $method;
            }
        }

        return $methods[0];
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
