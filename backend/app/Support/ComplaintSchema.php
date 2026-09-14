<?php

declare(strict_types=1);

namespace App\Support;

final class ComplaintSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists complaints (
                    id bigint unsigned primary key auto_increment,
                    complaint_number varchar(80) not null unique,
                    module_key varchar(40) not null default \'global\',
                    severity varchar(40) not null default \'normal\',
                    guest_id varchar(120) null,
                    customer_name varchar(190) null,
                    customer_phone varchar(80) null,
                    zone_id bigint unsigned null,
                    order_id bigint unsigned null,
                    booking_id bigint unsigned null,
                    vendor_id bigint unsigned null,
                    provider_id bigint unsigned null,
                    hotel_id bigint unsigned null,
                    real_estate_property_id bigint unsigned null,
                    real_estate_agent_id bigint unsigned null,
                    category varchar(120) not null,
                    location varchar(255) not null,
                    description text null,
                    image_path varchar(255) null,
                    status varchar(40) not null default \'pending\',
                    admin_note text null,
                    created_at timestamp null,
                    updated_at timestamp null,
                    index complaints_status_index (status)
                )'
            );
        } else {
            $db->exec(
                'create table if not exists complaints (
                    id integer primary key autoincrement,
                    complaint_number text not null unique,
                    module_key text not null default "global",
                    severity text not null default "normal",
                    guest_id text,
                    customer_name text,
                    customer_phone text,
                    zone_id integer,
                    order_id integer,
                    booking_id integer,
                    vendor_id integer,
                    provider_id integer,
                    hotel_id integer,
                    real_estate_property_id integer,
                    real_estate_agent_id integer,
                    category text not null,
                    location text not null,
                    description text,
                    image_path text,
                    status text not null default "pending",
                    admin_note text,
                    created_at text,
                    updated_at text
                )'
            );
        }
        self::addColumnIfMissing('module_key', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(40) not null default \'global\'' : 'text not null default "global"');
        self::addColumnIfMissing('severity', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(40) not null default \'normal\'' : 'text not null default "normal"');
        self::addColumnIfMissing('guest_id', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(120) null' : 'text');
        self::addColumnIfMissing('customer_name', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(190) null' : 'text');
        self::addColumnIfMissing('customer_phone', $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(80) null' : 'text');
        foreach (['zone_id', 'order_id', 'booking_id', 'vendor_id', 'provider_id', 'hotel_id', 'real_estate_property_id', 'real_estate_agent_id'] as $column) {
            self::addColumnIfMissing($column, $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'bigint unsigned null' : 'integer');
        }
    }

    private static function addColumnIfMissing(string $column, string $definition): void
    {
        $db = Database::connection();
        try {
            $db->query('select ' . $column . ' from complaints limit 1');
        } catch (\Throwable) {
            $db->exec('alter table complaints add column ' . $column . ' ' . $definition);
        }
    }
}
