<?php

declare(strict_types=1);

namespace App\Support;

final class ProductExtrasSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists subcategories (
                    id bigint unsigned primary key auto_increment,
                    module_key varchar(40) not null default \'mart\',
                    category_id bigint unsigned not null,
                    name varchar(190) not null,
                    slug varchar(220) not null,
                    image varchar(255) null,
                    status tinyint(1) not null default 1,
                    sort_order int not null default 0,
                    created_at timestamp null,
                    updated_at timestamp null,
                    index subcategories_module_category_index (module_key, category_id),
                    unique key subcategories_module_slug_unique (module_key, slug)
                )'
            );
            $db->exec(
                'create table if not exists product_images (
                    id bigint unsigned primary key auto_increment,
                    product_id bigint unsigned not null,
                    image varchar(255) not null,
                    sort_order int not null default 0,
                    created_at timestamp null
                )'
            );
            $db->exec(
                'create table if not exists product_variants (
                    id bigint unsigned primary key auto_increment,
                    product_id bigint unsigned not null,
                    name varchar(190) not null,
                    unit varchar(60) not null default \'piece\',
                    price decimal(12,2) not null default 0,
                    discount_price decimal(12,2) null,
                    stock int not null default 0,
                    sku varchar(120) null,
                    status tinyint(1) not null default 1,
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
        } else {
            $db->exec(
                'create table if not exists subcategories (
                    id integer primary key autoincrement,
                    module_key text not null default "mart",
                    category_id integer not null,
                    name text not null,
                    slug text not null,
                    image text,
                    status integer not null default 1,
                    sort_order integer not null default 0,
                    created_at text,
                    updated_at text
                )'
            );
            $db->exec('create index if not exists subcategories_module_category_index on subcategories (module_key, category_id)');
            $db->exec(
                'create table if not exists product_images (
                    id integer primary key autoincrement,
                    product_id integer not null,
                    image text not null,
                    sort_order integer not null default 0,
                    created_at text
                )'
            );
            $db->exec(
                'create table if not exists product_variants (
                    id integer primary key autoincrement,
                    product_id integer not null,
                    name text not null,
                    unit text not null default "piece",
                    price real not null default 0,
                    discount_price real,
                    stock integer not null default 0,
                    sku text,
                    status integer not null default 1,
                    created_at text,
                    updated_at text
                )'
            );
        }

        self::ensureColumn('carts', 'variant_id', $db);
        foreach (['categories', 'brands', 'banners', 'products', 'coupons', 'carts', 'cart_coupons', 'orders', 'payment_transactions'] as $table) {
            self::ensureModuleColumn($table, $db);
        }
        self::ensureColumn('order_items', 'variant_id', $db);
        self::ensureColumn('products', 'subcategory_id', $db);
        self::ensureColumn('order_items', 'variant_name', $db, true);
        self::ensureProductColumn('products', 'tax_percent', $db, 'decimal');
        self::ensureProductColumn('products', 'shipping_cost', $db, 'decimal');
        self::ensureProductColumn('products', 'barcode', $db, 'string');
        self::ensureProductColumn('products', 'seo_title', $db, 'string');
        self::ensureProductColumn('products', 'seo_description', $db, 'text');
        self::ensureProductColumn('products', 'attributes_json', $db, 'text');
        self::ensureProductColumn('products', 'colors_json', $db, 'text');
        self::ensureProductColumn('products', 'is_digital', $db, 'bool');
        self::ensureProductColumn('products', 'digital_file_url', $db, 'text');
        self::ensureProductColumn('products', 'is_flash_deal', $db, 'bool');
        self::ensureProductColumn('products', 'flash_deal_ends_at', $db, 'datetime');
        self::ensureProductColumn('products', 'is_clearance', $db, 'bool');
        self::ensureProductColumn('products', 'freshness_note', $db, 'string');
        self::ensureProductColumn('products', 'expiry_date', $db, 'date');
        self::ensureProductColumn('products', 'shelf_life', $db, 'string');
        self::ensureProductColumn('products', 'warranty_note', $db, 'string');
        self::ensureProductColumn('products', 'return_policy', $db, 'text');
        self::ensureProductColumn('products', 'medicine_type', $db, 'string');
        self::ensureProductColumn('products', 'schedule_tag', $db, 'string');
        self::ensureProductColumn('products', 'max_qty_per_order', $db, 'int');
        self::ensureProductColumn('products', 'max_qty_per_month', $db, 'int');
        self::ensureProductColumn('products', 'requires_pharmacist_review', $db, 'bool');
        self::ensureProductColumn('products', 'requires_age_confirmation', $db, 'bool');
        self::ensureCategoryColumn('categories', 'shipping_cost', $db);
        self::ensureOrderColumn('orders', 'tax_total', $db, 'decimal');
        self::ensureOrderColumn('orders', 'substitution_preference', $db, 'string');
    }

    private static function ensureColumn(string $table, string $column, \PDO $db, bool $text = false): void
    {
        try {
            $db->query('select ' . $column . ' from ' . $table . ' limit 1');
        } catch (\Throwable) {
            if ($text) {
                $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(190) null' : 'text';
            } else {
                $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer';
            }
            $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $type);
        }
    }

    private static function ensureModuleColumn(string $table, \PDO $db): void
    {
        try {
            $db->query('select module_key from ' . $table . ' limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                ? 'varchar(40) not null default \'mart\''
                : 'text not null default "mart"';
            try {
                $db->exec('alter table ' . $table . ' add column module_key ' . $type);
            } catch (\Throwable) {
                // Some optional commerce tables are created lazily by their own schema helpers.
            }
        }
    }

    private static function ensureProductColumn(string $table, string $column, \PDO $db, string $type): void
    {
        try {
            $db->query('select ' . $column . ' from ' . $table . ' limit 1');
        } catch (\Throwable) {
            $sqlType = match ($type) {
                'decimal' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'decimal(8,2) not null default 0'
                    : 'real not null default 0',
                'bool' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'tinyint(1) not null default 0'
                    : 'integer not null default 0',
                'datetime' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'datetime null'
                    : 'text',
                'date' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'date null'
                    : 'text',
                'int' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'int null'
                    : 'integer',
                'text' => 'text',
                default => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'varchar(255) null'
                    : 'text',
            };
            $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $sqlType);
        }
    }

    private static function ensureCategoryColumn(string $table, string $column, \PDO $db): void
    {
        try {
            $db->query('select ' . $column . ' from ' . $table . ' limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                ? 'decimal(12,2) not null default 0'
                : 'real not null default 0';
            $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $type);
        }
    }

    private static function ensureOrderColumn(string $table, string $column, \PDO $db, string $type = 'decimal'): void
    {
        try {
            $db->query('select ' . $column . ' from ' . $table . ' limit 1');
        } catch (\Throwable) {
            $sqlType = match ($type) {
                'string' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'varchar(60) not null default \'call_before_replace\''
                    : 'text not null default "call_before_replace"',
                default => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                    ? 'decimal(12,2) not null default 0'
                    : 'real not null default 0',
            };
            $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $sqlType);
        }
    }
}
