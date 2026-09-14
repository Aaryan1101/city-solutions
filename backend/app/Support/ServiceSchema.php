<?php

declare(strict_types=1);

namespace App\Support;

final class ServiceSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec('create table if not exists service_categories (
                id bigint unsigned primary key auto_increment,
                name varchar(190) not null,
                description text null,
                icon varchar(80) null,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists services (
                id bigint unsigned primary key auto_increment,
                category_id bigint unsigned null,
                provider_id bigint unsigned null,
                vendor_id bigint unsigned null,
                name varchar(190) not null,
                description text null,
                checklist_json text null,
                duration_minutes int not null default 60,
                warranty_days int not null default 0,
                price decimal(12,2) not null default 0,
                discount_price decimal(12,2) null,
                image varchar(255) null,
                status tinyint(1) not null default 1,
                is_featured tinyint(1) not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists service_providers (
                id bigint unsigned primary key auto_increment,
                name varchar(190) not null,
                phone varchar(40) null,
                email varchar(190) null,
                area varchar(190) null,
                password varchar(255) null,
                auth_token varchar(255) null,
                auth_token_expires_at timestamp null,
                availability_status varchar(40) not null default \'offline\',
                last_seen_at timestamp null,
                commission_percent decimal(5,2) not null default 0,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists service_addons (
                id bigint unsigned primary key auto_increment,
                service_id bigint unsigned not null,
                name varchar(190) not null,
                description text null,
                price decimal(12,2) not null default 0,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists service_slots (
                id bigint unsigned primary key auto_increment,
                service_id bigint unsigned not null,
                provider_id bigint unsigned null,
                day_of_week tinyint unsigned not null default 0,
                start_time varchar(20) not null,
                end_time varchar(20) not null,
                capacity int not null default 1,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists service_blackouts (
                id bigint unsigned primary key auto_increment,
                service_id bigint unsigned null,
                provider_id bigint unsigned null,
                blackout_date date not null,
                end_date date null,
                recurrence varchar(40) not null default \'none\',
                start_time varchar(20) null,
                end_time varchar(20) null,
                reason varchar(255) null,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists service_provider_settlements (
                id bigint unsigned primary key auto_increment,
                provider_id bigint unsigned not null,
                period_start date null,
                period_end date null,
                gross_amount decimal(12,2) not null default 0,
                commission_amount decimal(12,2) not null default 0,
                payable_amount decimal(12,2) not null default 0,
                payment_reference varchar(190) null,
                note text null,
                status varchar(40) not null default \'pending\',
                settled_at timestamp null,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists service_bookings (
                id bigint unsigned primary key auto_increment,
                booking_number varchar(80) not null unique,
                service_id bigint unsigned not null,
                provider_id bigint unsigned null,
                slot_id bigint unsigned null,
                vendor_id bigint unsigned null,
                guest_id varchar(190) null,
                customer_name varchar(190) not null,
                customer_phone varchar(40) not null,
                customer_email varchar(190) null,
                address text not null,
                preferred_date date null,
                preferred_time varchar(40) null,
                addons_json text null,
                addon_total decimal(12,2) not null default 0,
                amount decimal(12,2) not null default 0,
                payment_method varchar(60) not null default \'cash_on_service\',
                payment_status varchar(40) not null default \'unpaid\',
                booking_status varchar(40) not null default \'pending\',
                admin_note text null,
                reschedule_count int not null default 0,
                rescheduled_at timestamp null,
                cancelled_at timestamp null,
                completed_at timestamp null,
                warranty_days int not null default 0,
                warranty_until date null,
                note text null,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists service_payment_transactions (
                id bigint unsigned primary key auto_increment,
                booking_id bigint unsigned not null,
                payment_method varchar(60) not null,
                amount decimal(12,2) not null default 0,
                reference varchar(190) null,
                note text null,
                status varchar(40) not null default \'pending\',
                gateway_response text null,
                reconciled_at timestamp null,
                reconciled_by varchar(190) null,
                verified_at timestamp null,
                created_at timestamp null,
                updated_at timestamp null,
                index service_payment_transactions_booking_index (booking_id)
            )');
        } else {
            $db->exec('create table if not exists service_categories (
                id integer primary key autoincrement,
                name text not null,
                description text,
                icon text,
                status integer not null default 1,
                sort_order integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists services (
                id integer primary key autoincrement,
                category_id integer,
                provider_id integer,
                vendor_id integer,
                name text not null,
                description text,
                checklist_json text,
                duration_minutes integer not null default 60,
                warranty_days integer not null default 0,
                price real not null default 0,
                discount_price real,
                image text,
                status integer not null default 1,
                is_featured integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists service_providers (
                id integer primary key autoincrement,
                name text not null,
                phone text,
                email text,
                area text,
                password text,
                auth_token text,
                auth_token_expires_at text,
                availability_status text not null default "offline",
                last_seen_at text,
                commission_percent real not null default 0,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists service_addons (
                id integer primary key autoincrement,
                service_id integer not null,
                name text not null,
                description text,
                price real not null default 0,
                status integer not null default 1,
                sort_order integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists service_slots (
                id integer primary key autoincrement,
                service_id integer not null,
                provider_id integer,
                day_of_week integer not null default 0,
                start_time text not null,
                end_time text not null,
                capacity integer not null default 1,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists service_blackouts (
                id integer primary key autoincrement,
                service_id integer,
                provider_id integer,
                blackout_date text not null,
                end_date text,
                recurrence text not null default "none",
                start_time text,
                end_time text,
                reason text,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists service_provider_settlements (
                id integer primary key autoincrement,
                provider_id integer not null,
                period_start text,
                period_end text,
                gross_amount real not null default 0,
                commission_amount real not null default 0,
                payable_amount real not null default 0,
                payment_reference text,
                note text,
                status text not null default "pending",
                settled_at text,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists service_bookings (
                id integer primary key autoincrement,
                booking_number text not null unique,
                service_id integer not null,
                provider_id integer,
                slot_id integer,
                vendor_id integer,
                guest_id text,
                customer_name text not null,
                customer_phone text not null,
                customer_email text,
                address text not null,
                preferred_date text,
                preferred_time text,
                addons_json text,
                addon_total real not null default 0,
                amount real not null default 0,
                payment_method text not null default "cash_on_service",
                payment_status text not null default "unpaid",
                booking_status text not null default "pending",
                admin_note text,
                reschedule_count integer not null default 0,
                rescheduled_at text,
                cancelled_at text,
                completed_at text,
                warranty_days integer not null default 0,
                warranty_until text,
                note text,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists service_payment_transactions (
                id integer primary key autoincrement,
                booking_id integer not null,
                payment_method text not null,
                amount real not null default 0,
                reference text,
                note text,
                status text not null default "pending",
                gateway_response text,
                reconciled_at text,
                reconciled_by text,
                verified_at text,
                created_at text,
                updated_at text
            )');
        }
        self::addColumnIfMissing('service_providers', 'commission_percent', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'decimal(5,2) not null default 0' : 'real not null default 0');
        self::addColumnIfMissing('service_providers', 'zone_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('service_providers', 'password', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(255) null' : 'text');
        self::addColumnIfMissing('service_providers', 'auth_token', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(255) null' : 'text');
        self::addColumnIfMissing('service_providers', 'auth_token_expires_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('service_providers', 'availability_status', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(40) not null default \'offline\'' : 'text not null default "offline"');
        self::addColumnIfMissing('service_providers', 'last_seen_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('services', 'provider_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('services', 'zone_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('services', 'warranty_days', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'int not null default 0' : 'integer not null default 0');
        self::addColumnIfMissing('services', 'checklist_json', 'text');
        self::addColumnIfMissing('service_bookings', 'zone_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('service_bookings', 'provider_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('service_bookings', 'slot_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('service_bookings', 'addons_json', 'text');
        self::addColumnIfMissing('service_bookings', 'addon_total', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'decimal(12,2) not null default 0' : 'real not null default 0');
        self::addColumnIfMissing('service_bookings', 'admin_note', 'text');
        self::addColumnIfMissing('service_bookings', 'reschedule_count', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'int not null default 0' : 'integer not null default 0');
        self::addColumnIfMissing('service_bookings', 'rescheduled_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('service_bookings', 'cancelled_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('service_bookings', 'completed_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('service_bookings', 'warranty_days', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'int not null default 0' : 'integer not null default 0');
        self::addColumnIfMissing('service_bookings', 'warranty_until', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'date null' : 'text');
        self::addColumnIfMissing('service_blackouts', 'end_date', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'date null' : 'text');
        self::addColumnIfMissing('service_blackouts', 'recurrence', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(40) not null default \'none\'' : 'text not null default "none"');
        self::addColumnIfMissing('service_blackouts', 'start_time', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(20) null' : 'text');
        self::addColumnIfMissing('service_blackouts', 'end_time', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(20) null' : 'text');
        self::addColumnIfMissing('service_payment_transactions', 'gateway_response', 'text');
        self::addColumnIfMissing('service_payment_transactions', 'reconciled_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
        self::addColumnIfMissing('service_payment_transactions', 'reconciled_by', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(190) null' : 'text');
        self::addColumnIfMissing('service_payment_transactions', 'verified_at', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'timestamp null' : 'text');
    }

    private static function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $db = Database::connection();
        try {
            $db->query('select ' . $column . ' from ' . $table . ' limit 1');
        } catch (\Throwable) {
            $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $definition);
        }
    }
}
