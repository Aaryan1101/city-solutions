<?php

declare(strict_types=1);

namespace App\Support;

final class TaxiSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        $mysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';

        if ($mysql) {
            $db->exec('create table if not exists taxi_vehicle_types (
                id bigint unsigned primary key auto_increment,
                name varchar(120) not null,
                slug varchar(120) null,
                seats int not null default 4,
                base_fare decimal(12,2) not null default 0,
                per_km_fare decimal(12,2) not null default 0,
                per_minute_fare decimal(12,2) not null default 0,
                minimum_fare decimal(12,2) not null default 0,
                cancellation_fee decimal(12,2) not null default 0,
                service_fee decimal(12,2) not null default 0,
                waiting_fee_per_minute decimal(12,2) not null default 0,
                included_wait_minutes int not null default 3,
                icon varchar(80) null,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists taxi_drivers (
                id bigint unsigned primary key auto_increment,
                zone_id bigint unsigned null,
                name varchar(190) not null,
                phone varchar(40) not null,
                email varchar(190) null,
                password varchar(255) null,
                auth_token varchar(255) null,
                auth_token_expires_at timestamp null,
                vehicle_type_id bigint unsigned null,
                vehicle_name varchar(190) null,
                vehicle_number varchar(80) null,
                license_number varchar(120) null,
                rc_number varchar(120) null,
                profile_photo varchar(500) null,
                license_document varchar(500) null,
                vehicle_document varchar(500) null,
                insurance_document varchar(500) null,
                license_expiry date null,
                insurance_expiry date null,
                current_latitude decimal(10,7) null,
                current_longitude decimal(10,7) null,
                availability_status varchar(40) not null default \'offline\',
                rating decimal(3,2) not null default 0,
                status varchar(40) not null default \'pending\',
                admin_note text null,
                last_seen_at timestamp null,
                created_at timestamp null,
                updated_at timestamp null,
                index taxi_drivers_phone_index (phone),
                index taxi_drivers_zone_index (zone_id)
            )');
            $db->exec('create table if not exists taxi_rides (
                id bigint unsigned primary key auto_increment,
                ride_number varchar(80) not null unique,
                guest_id varchar(190) null,
                customer_id bigint unsigned null,
                customer_name varchar(190) not null,
                customer_phone varchar(40) not null,
                customer_email varchar(190) null,
                zone_id bigint unsigned null,
                driver_id bigint unsigned null,
                vehicle_type_id bigint unsigned null,
                pickup_address text not null,
                pickup_latitude decimal(10,7) null,
                pickup_longitude decimal(10,7) null,
                drop_address text not null,
                drop_latitude decimal(10,7) null,
                drop_longitude decimal(10,7) null,
                distance_km decimal(10,2) not null default 0,
                duration_minutes int not null default 0,
                estimated_fare decimal(12,2) not null default 0,
                final_fare decimal(12,2) not null default 0,
                payment_method varchar(60) not null default \'cash\',
                payment_status varchar(40) not null default \'unpaid\',
                ride_status varchar(40) not null default \'requested\',
                otp_code varchar(12) null,
                customer_note text null,
                cancellation_reason text null,
                cancelled_by varchar(40) null,
                cancellation_fee decimal(12,2) not null default 0,
                driver_earning decimal(12,2) not null default 0,
                commission_amount decimal(12,2) not null default 0,
                quote_token varchar(128) null,
                route_polyline longtext null,
                route_source varchar(40) null,
                driver_eta_minutes int null,
                customer_rating tinyint unsigned null,
                customer_review text null,
                accepted_at timestamp null,
                arrived_at timestamp null,
                started_at timestamp null,
                completed_at timestamp null,
                cancelled_at timestamp null,
                created_at timestamp null,
                updated_at timestamp null,
                index taxi_rides_guest_index (guest_id),
                index taxi_rides_driver_index (driver_id),
                index taxi_rides_zone_index (zone_id)
            )');
            $db->exec('create table if not exists taxi_ride_status_history (
                id bigint unsigned primary key auto_increment,
                ride_id bigint unsigned not null,
                status varchar(60) not null,
                actor_type varchar(40) not null,
                actor_name varchar(190) null,
                note text null,
                created_at timestamp null,
                index taxi_ride_history_ride_index (ride_id)
            )');
            $db->exec('create table if not exists taxi_quotes (
                id bigint unsigned primary key auto_increment,
                quote_token varchar(128) not null unique,
                customer_id bigint unsigned null,
                zone_id bigint unsigned null,
                pickup_address text not null,
                pickup_latitude decimal(10,7) not null,
                pickup_longitude decimal(10,7) not null,
                drop_address text not null,
                drop_latitude decimal(10,7) not null,
                drop_longitude decimal(10,7) not null,
                distance_km decimal(10,2) not null,
                duration_minutes int not null,
                route_polyline longtext null,
                route_source varchar(40) null,
                options_json longtext not null,
                expires_at timestamp not null,
                consumed_at timestamp null,
                created_at timestamp null,
                index taxi_quotes_expiry_index (expires_at)
            )');
            $db->exec('create table if not exists taxi_ride_driver_responses (
                id bigint unsigned primary key auto_increment,
                ride_id bigint unsigned not null,
                driver_id bigint unsigned not null,
                response varchar(30) not null,
                created_at timestamp null,
                unique key taxi_driver_response_unique (ride_id, driver_id),
                index taxi_driver_response_driver_index (driver_id)
            )');
        } else {
            $db->exec('create table if not exists taxi_vehicle_types (
                id integer primary key autoincrement,
                name text not null,
                slug text,
                seats integer not null default 4,
                base_fare real not null default 0,
                per_km_fare real not null default 0,
                per_minute_fare real not null default 0,
                minimum_fare real not null default 0,
                cancellation_fee real not null default 0,
                service_fee real not null default 0,
                waiting_fee_per_minute real not null default 0,
                included_wait_minutes integer not null default 3,
                icon text,
                status integer not null default 1,
                sort_order integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists taxi_drivers (
                id integer primary key autoincrement,
                zone_id integer,
                name text not null,
                phone text not null,
                email text,
                password text,
                auth_token text,
                auth_token_expires_at text,
                vehicle_type_id integer,
                vehicle_name text,
                vehicle_number text,
                license_number text,
                rc_number text,
                profile_photo text,
                license_document text,
                vehicle_document text,
                insurance_document text,
                license_expiry text,
                insurance_expiry text,
                current_latitude real,
                current_longitude real,
                availability_status text not null default "offline",
                rating real not null default 0,
                status text not null default "pending",
                admin_note text,
                last_seen_at text,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists taxi_rides (
                id integer primary key autoincrement,
                ride_number text not null unique,
                guest_id text,
                customer_id integer,
                customer_name text not null,
                customer_phone text not null,
                customer_email text,
                zone_id integer,
                driver_id integer,
                vehicle_type_id integer,
                pickup_address text not null,
                pickup_latitude real,
                pickup_longitude real,
                drop_address text not null,
                drop_latitude real,
                drop_longitude real,
                distance_km real not null default 0,
                duration_minutes integer not null default 0,
                estimated_fare real not null default 0,
                final_fare real not null default 0,
                payment_method text not null default "cash",
                payment_status text not null default "unpaid",
                ride_status text not null default "requested",
                otp_code text,
                customer_note text,
                cancellation_reason text,
                cancelled_by text,
                cancellation_fee real not null default 0,
                driver_earning real not null default 0,
                commission_amount real not null default 0,
                quote_token text,
                route_polyline text,
                route_source text,
                driver_eta_minutes integer,
                customer_rating integer,
                customer_review text,
                accepted_at text,
                arrived_at text,
                started_at text,
                completed_at text,
                cancelled_at text,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists taxi_ride_status_history (
                id integer primary key autoincrement,
                ride_id integer not null,
                status text not null,
                actor_type text not null,
                actor_name text,
                note text,
                created_at text
            )');
            $db->exec('create table if not exists taxi_quotes (
                id integer primary key autoincrement,
                quote_token text not null unique,
                customer_id integer,
                zone_id integer,
                pickup_address text not null,
                pickup_latitude real not null,
                pickup_longitude real not null,
                drop_address text not null,
                drop_latitude real not null,
                drop_longitude real not null,
                distance_km real not null,
                duration_minutes integer not null,
                route_polyline text,
                route_source text,
                options_json text not null,
                expires_at text not null,
                consumed_at text,
                created_at text
            )');
            $db->exec('create table if not exists taxi_ride_driver_responses (
                id integer primary key autoincrement,
                ride_id integer not null,
                driver_id integer not null,
                response text not null,
                created_at text
            )');
            $db->exec('create unique index if not exists taxi_driver_response_unique on taxi_ride_driver_responses (ride_id, driver_id)');
        }

        ZoneSchema::ensure();
        self::addColumnIfMissing('taxi_drivers', 'auth_token_expires_at', $mysql ? 'timestamp null' : 'text');
        self::addColumnIfMissing('taxi_drivers', 'rc_number', $mysql ? 'varchar(120) null' : 'text');
        self::addColumnIfMissing('taxi_drivers', 'profile_photo', $mysql ? 'varchar(500) null' : 'text');
        self::addColumnIfMissing('taxi_drivers', 'license_document', $mysql ? 'varchar(500) null' : 'text');
        self::addColumnIfMissing('taxi_drivers', 'vehicle_document', $mysql ? 'varchar(500) null' : 'text');
        self::addColumnIfMissing('taxi_drivers', 'insurance_document', $mysql ? 'varchar(500) null' : 'text');
        self::addColumnIfMissing('taxi_drivers', 'license_expiry', $mysql ? 'date null' : 'text');
        self::addColumnIfMissing('taxi_drivers', 'insurance_expiry', $mysql ? 'date null' : 'text');
        self::addColumnIfMissing('taxi_rides', 'cancellation_fee', $mysql ? 'decimal(12,2) not null default 0' : 'real not null default 0');
        self::addColumnIfMissing('taxi_rides', 'driver_earning', $mysql ? 'decimal(12,2) not null default 0' : 'real not null default 0');
        self::addColumnIfMissing('taxi_rides', 'commission_amount', $mysql ? 'decimal(12,2) not null default 0' : 'real not null default 0');
        self::addColumnIfMissing('taxi_rides', 'customer_id', $mysql ? 'bigint unsigned null' : 'integer');
        self::addColumnIfMissing('taxi_rides', 'quote_token', $mysql ? 'varchar(128) null' : 'text');
        self::addColumnIfMissing('taxi_rides', 'route_polyline', $mysql ? 'longtext null' : 'text');
        self::addColumnIfMissing('taxi_rides', 'route_source', $mysql ? 'varchar(40) null' : 'text');
        self::addColumnIfMissing('taxi_rides', 'driver_eta_minutes', $mysql ? 'int null' : 'integer');
        self::addColumnIfMissing('taxi_rides', 'customer_rating', $mysql ? 'tinyint unsigned null' : 'integer');
        self::addColumnIfMissing('taxi_rides', 'customer_review', $mysql ? 'text null' : 'text');
        self::addColumnIfMissing('taxi_vehicle_types', 'service_fee', $mysql ? 'decimal(12,2) not null default 0' : 'real not null default 0');
        self::addColumnIfMissing('taxi_vehicle_types', 'waiting_fee_per_minute', $mysql ? 'decimal(12,2) not null default 0' : 'real not null default 0');
        self::addColumnIfMissing('taxi_vehicle_types', 'included_wait_minutes', $mysql ? 'int not null default 3' : 'integer not null default 3');
        self::ensureLedger($mysql);
        self::seedVehicleTypes();
    }

    public static function recordStatus(int $rideId, string $status, string $actorType, string $actorName = '', string $note = ''): void
    {
        $stmt = Database::connection()->prepare(
            'insert into taxi_ride_status_history (ride_id, status, actor_type, actor_name, note, created_at)
             values (:ride_id, :status, :actor_type, :actor_name, :note, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'ride_id' => $rideId,
            'status' => $status,
            'actor_type' => $actorType,
            'actor_name' => $actorName === '' ? null : $actorName,
            'note' => $note === '' ? null : $note,
        ]);
    }

    private static function seedVehicleTypes(): void
    {
        $db = Database::connection();
        $count = (int) $db->query('select count(*) from taxi_vehicle_types')->fetchColumn();
        if ($count > 0) {
            return;
        }
        $stmt = $db->prepare(
            'insert into taxi_vehicle_types
             (name, slug, seats, base_fare, per_km_fare, per_minute_fare, minimum_fare, cancellation_fee, icon, status, sort_order, created_at, updated_at)
             values (:name, :slug, :seats, :base_fare, :per_km_fare, :per_minute_fare, :minimum_fare, :cancellation_fee, :icon, 1, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        foreach ([
            ['Bike', 'bike', 1, 25, 8, 1, 35, 15, 'two_wheeler', 1],
            ['Mini', 'mini', 4, 45, 14, 1.5, 75, 35, 'local_taxi', 2],
            ['Sedan', 'sedan', 4, 60, 18, 2, 95, 45, 'directions_car', 3],
            ['SUV', 'suv', 6, 85, 24, 2.5, 140, 60, 'airport_shuttle', 4],
        ] as $type) {
            $stmt->execute([
                'name' => $type[0],
                'slug' => $type[1],
                'seats' => $type[2],
                'base_fare' => $type[3],
                'per_km_fare' => $type[4],
                'per_minute_fare' => $type[5],
                'minimum_fare' => $type[6],
                'cancellation_fee' => $type[7],
                'icon' => $type[8],
                'sort_order' => $type[9],
            ]);
        }
    }

    private static function ensureLedger(bool $mysql): void
    {
        $db = Database::connection();
        if ($mysql) {
            $db->exec('create table if not exists taxi_driver_ledgers (
                id bigint unsigned primary key auto_increment,
                driver_id bigint unsigned not null,
                ride_id bigint unsigned null,
                direction varchar(20) not null,
                amount decimal(12,2) not null default 0,
                entry_type varchar(60) not null,
                description text null,
                created_at timestamp null,
                updated_at timestamp null,
                unique key taxi_driver_ledger_unique (driver_id, ride_id, entry_type),
                index taxi_driver_ledger_driver_index (driver_id)
            )');
            return;
        }
        $db->exec('create table if not exists taxi_driver_ledgers (
            id integer primary key autoincrement,
            driver_id integer not null,
            ride_id integer,
            direction text not null,
            amount real not null default 0,
            entry_type text not null,
            description text,
            created_at text,
            updated_at text
        )');
        $db->exec('create unique index if not exists taxi_driver_ledger_unique on taxi_driver_ledgers (driver_id, ride_id, entry_type)');
    }

    private static function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $db = Database::connection();
        $mysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        try {
            if ($mysql) {
                $stmt = $db->prepare('select count(*) from information_schema.columns where table_schema = database() and table_name = :table and column_name = :column');
                $stmt->execute(['table' => $table, 'column' => $column]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $definition);
                }
                return;
            }
            foreach ($db->query('pragma table_info(' . $table . ')')->fetchAll() as $existing) {
                if (($existing['name'] ?? '') === $column) {
                    return;
                }
            }
            $db->exec('alter table ' . $table . ' add column ' . $column . ' ' . $definition);
        } catch (\Throwable) {
            // Runtime migrations should not break older partial uploads.
        }
    }
}
