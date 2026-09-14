<?php

declare(strict_types=1);

namespace App\Support;

final class HotelSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        $mysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        if ($mysql) {
            $db->exec('create table if not exists hotel_categories (
                id bigint unsigned primary key auto_increment,
                name varchar(190) not null,
                description text null,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists hotel_owners (
                id bigint unsigned primary key auto_increment,
                name varchar(190) not null,
                phone varchar(40) null,
                email varchar(190) null,
                password varchar(255) null,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists hotels (
                id bigint unsigned primary key auto_increment,
                owner_id bigint unsigned null,
                category_id bigint unsigned null,
                name varchar(190) not null,
                city varchar(120) not null,
                area varchar(190) null,
                address text not null,
                description text null,
                star_rating decimal(3,1) not null default 0,
                rating decimal(3,2) not null default 0,
                review_count int not null default 0,
                amenities_json text null,
                thumbnail varchar(255) null,
                gallery_json text null,
                status tinyint(1) not null default 1,
                is_featured tinyint(1) not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists hotel_rooms (
                id bigint unsigned primary key auto_increment,
                hotel_id bigint unsigned not null,
                name varchar(190) not null,
                description text null,
                capacity_adults int not null default 2,
                capacity_children int not null default 0,
                total_rooms int not null default 1,
                price_per_night decimal(12,2) not null default 0,
                discount_price decimal(12,2) null,
                tax_percent decimal(6,2) not null default 0,
                amenities_json text null,
                thumbnail varchar(255) null,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists hotel_bookings (
                id bigint unsigned primary key auto_increment,
                booking_number varchar(80) not null unique,
                hotel_id bigint unsigned not null,
                room_id bigint unsigned not null,
                guest_id varchar(190) null,
                customer_name varchar(190) not null,
                customer_phone varchar(40) not null,
                customer_email varchar(190) null,
                check_in date not null,
                check_out date not null,
                nights int not null default 1,
                rooms int not null default 1,
                adults int not null default 1,
                children int not null default 0,
                room_total decimal(12,2) not null default 0,
                tax_total decimal(12,2) not null default 0,
                grand_total decimal(12,2) not null default 0,
                payment_method varchar(60) not null default \'pay_at_hotel\',
                payment_status varchar(40) not null default \'unpaid\',
                booking_status varchar(40) not null default \'pending\',
                cancellation_reason text null,
                refund_status varchar(40) not null default \'none\',
                refund_amount decimal(12,2) not null default 0,
                refund_note text null,
                admin_note text null,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists hotel_reviews (
                id bigint unsigned primary key auto_increment,
                hotel_id bigint unsigned not null,
                booking_id bigint unsigned null,
                guest_id varchar(190) null,
                customer_name varchar(190) not null,
                rating tinyint unsigned not null default 5,
                comment text null,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists hotel_blackouts (
                id bigint unsigned primary key auto_increment,
                hotel_id bigint unsigned null,
                room_id bigint unsigned null,
                title varchar(190) not null,
                starts_at date not null,
                ends_at date not null,
                repeat_type varchar(40) not null default \'none\',
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null,
                index hotel_blackouts_dates_index (starts_at, ends_at),
                index hotel_blackouts_room_index (room_id)
            )');
            $db->exec('create table if not exists hotel_payment_transactions (
                id bigint unsigned primary key auto_increment,
                booking_id bigint unsigned not null,
                guest_id varchar(190) null,
                customer_name varchar(190) null,
                customer_phone varchar(40) null,
                payment_method varchar(60) not null,
                amount decimal(12,2) not null default 0,
                reference varchar(190) null,
                note text null,
                status varchar(40) not null default \'pending\',
                gateway_response text null,
                reconciled_at timestamp null,
                reconciled_by varchar(190) null,
                created_at timestamp null,
                updated_at timestamp null,
                index hotel_payment_booking_index (booking_id)
            )');
        } else {
            $db->exec('create table if not exists hotel_categories (
                id integer primary key autoincrement,
                name text not null,
                description text,
                status integer not null default 1,
                sort_order integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists hotel_owners (
                id integer primary key autoincrement,
                name text not null,
                phone text,
                email text,
                password text,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists hotels (
                id integer primary key autoincrement,
                owner_id integer,
                category_id integer,
                name text not null,
                city text not null,
                area text,
                address text not null,
                description text,
                star_rating real not null default 0,
                rating real not null default 0,
                review_count integer not null default 0,
                amenities_json text,
                thumbnail text,
                gallery_json text,
                status integer not null default 1,
                is_featured integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists hotel_rooms (
                id integer primary key autoincrement,
                hotel_id integer not null,
                name text not null,
                description text,
                capacity_adults integer not null default 2,
                capacity_children integer not null default 0,
                total_rooms integer not null default 1,
                price_per_night real not null default 0,
                discount_price real,
                tax_percent real not null default 0,
                amenities_json text,
                thumbnail text,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists hotel_bookings (
                id integer primary key autoincrement,
                booking_number text not null unique,
                hotel_id integer not null,
                room_id integer not null,
                guest_id text,
                customer_name text not null,
                customer_phone text not null,
                customer_email text,
                check_in text not null,
                check_out text not null,
                nights integer not null default 1,
                rooms integer not null default 1,
                adults integer not null default 1,
                children integer not null default 0,
                room_total real not null default 0,
                tax_total real not null default 0,
                grand_total real not null default 0,
                payment_method text not null default "pay_at_hotel",
                payment_status text not null default "unpaid",
                booking_status text not null default "pending",
                cancellation_reason text,
                refund_status text not null default "none",
                refund_amount real not null default 0,
                refund_note text,
                admin_note text,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists hotel_reviews (
                id integer primary key autoincrement,
                hotel_id integer not null,
                booking_id integer,
                guest_id text,
                customer_name text not null,
                rating integer not null default 5,
                comment text,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists hotel_blackouts (
                id integer primary key autoincrement,
                hotel_id integer,
                room_id integer,
                title text not null,
                starts_at text not null,
                ends_at text not null,
                repeat_type text not null default "none",
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists hotel_payment_transactions (
                id integer primary key autoincrement,
                booking_id integer not null,
                guest_id text,
                customer_name text,
                customer_phone text,
                payment_method text not null,
                amount real not null default 0,
                reference text,
                note text,
                status text not null default "pending",
                gateway_response text,
                reconciled_at text,
                reconciled_by text,
                created_at text,
                updated_at text
            )');
        }

        self::addColumnIfMissing('hotel_bookings', 'payment_reference', $mysql ? 'varchar(190) null' : 'text');
        self::addColumnIfMissing('hotels', 'zone_id', $mysql ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('hotel_bookings', 'zone_id', $mysql ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('hotel_bookings', 'payment_note', 'text');
        self::addColumnIfMissing('hotel_bookings', 'refund_status', $mysql ? 'varchar(40) not null default \'none\'' : 'text not null default "none"');
        self::addColumnIfMissing('hotel_bookings', 'refund_amount', $mysql ? 'decimal(12,2) not null default 0' : 'real not null default 0');
        self::addColumnIfMissing('hotel_bookings', 'refund_note', 'text');
        self::addColumnIfMissing('hotel_bookings', 'cancelled_at', $mysql ? 'timestamp null' : 'text');
        self::addColumnIfMissing('hotel_bookings', 'completed_at', $mysql ? 'timestamp null' : 'text');
        self::addColumnIfMissing('hotel_rooms', 'max_advance_days', $mysql ? 'int not null default 365' : 'integer not null default 365');
        self::addColumnIfMissing('hotels', 'check_in_time', $mysql ? 'varchar(20) null' : 'text');
        self::addColumnIfMissing('hotels', 'check_out_time', $mysql ? 'varchar(20) null' : 'text');
        self::addColumnIfMissing('hotel_payment_transactions', 'gateway_response', 'text');
        self::addColumnIfMissing('hotel_payment_transactions', 'reconciled_at', $mysql ? 'timestamp null' : 'text');
        self::addColumnIfMissing('hotel_payment_transactions', 'reconciled_by', $mysql ? 'varchar(190) null' : 'text');
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
