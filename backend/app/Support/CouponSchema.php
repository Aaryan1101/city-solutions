<?php

declare(strict_types=1);

namespace App\Support;

final class CouponSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists coupons (
                    id bigint unsigned primary key auto_increment,
                    code varchar(80) not null unique,
                    title varchar(190) not null,
                    discount_type varchar(20) not null default \'flat\',
                    discount_value decimal(12,2) not null default 0,
                    minimum_order_amount decimal(12,2) not null default 0,
                    maximum_discount decimal(12,2) null,
                    usage_limit int null,
                    used_count int not null default 0,
                    starts_at date null,
                    expires_at date null,
                    status tinyint(1) not null default 1,
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
            $db->exec(
                'create table if not exists cart_coupons (
                    guest_id varchar(120) primary key,
                    coupon_id bigint unsigned not null,
                    code varchar(80) not null,
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
        } else {
            $db->exec(
                'create table if not exists coupons (
                    id integer primary key autoincrement,
                    code text not null unique,
                    title text not null,
                    discount_type text not null default "flat",
                    discount_value real not null default 0,
                    minimum_order_amount real not null default 0,
                    maximum_discount real,
                    usage_limit integer,
                    used_count integer not null default 0,
                    starts_at text,
                    expires_at text,
                    status integer not null default 1,
                    created_at text,
                    updated_at text
                )'
            );
            $db->exec(
                'create table if not exists cart_coupons (
                    guest_id text primary key,
                    coupon_id integer not null,
                    code text not null,
                    created_at text,
                    updated_at text
                )'
            );
        }

        self::ensureOrdersCouponCodeColumn($db);
        self::ensureOrdersCouponDiscountColumn($db);
    }

    private static function ensureOrdersCouponCodeColumn(\PDO $db): void
    {
        try {
            $db->query('select coupon_code from orders limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(80) null' : 'text';
            $db->exec('alter table orders add column coupon_code ' . $type);
        }
    }

    private static function ensureOrdersCouponDiscountColumn(\PDO $db): void
    {
        try {
            $db->query('select coupon_discount from orders limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'decimal(12,2) not null default 0' : 'real not null default 0';
            $db->exec('alter table orders add column coupon_discount ' . $type);
        }
    }
}
