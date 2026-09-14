<?php

declare(strict_types=1);

namespace App\Support;

final class RestaurantSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        $mysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        if ($mysql) {
            $db->exec('create table if not exists restaurant_categories (
                id bigint unsigned primary key auto_increment,
                name varchar(190) not null,
                slug varchar(190) null,
                image varchar(255) null,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_owners (
                id bigint unsigned primary key auto_increment,
                zone_id bigint unsigned null,
                name varchar(190) not null,
                phone varchar(40) null,
                email varchar(190) null,
                password varchar(255) null,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurants (
                id bigint unsigned primary key auto_increment,
                owner_id bigint unsigned null,
                category_id bigint unsigned null,
                zone_id bigint unsigned null,
                name varchar(190) not null,
                slug varchar(190) null,
                description text null,
                cuisine varchar(190) null,
                address text not null,
                city varchar(120) null,
                area varchar(190) null,
                latitude decimal(10,7) null,
                longitude decimal(10,7) null,
                phone varchar(40) null,
                email varchar(190) null,
                opening_time varchar(20) null,
                closing_time varchar(20) null,
                average_cost decimal(12,2) not null default 0,
                thumbnail varchar(255) null,
                gallery_json text null,
                rating decimal(3,2) not null default 0,
                review_count int not null default 0,
                status tinyint(1) not null default 1,
                is_featured tinyint(1) not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_tables (
                id bigint unsigned primary key auto_increment,
                restaurant_id bigint unsigned not null,
                table_name varchar(120) not null,
                capacity int not null default 2,
                table_count int not null default 1,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_shifts (
                id bigint unsigned primary key auto_increment,
                restaurant_id bigint unsigned not null,
                day_of_week tinyint unsigned not null default 0,
                start_time varchar(20) not null,
                end_time varchar(20) not null,
                slot_interval_minutes int not null default 30,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_blackouts (
                id bigint unsigned primary key auto_increment,
                restaurant_id bigint unsigned null,
                blackout_date date not null,
                end_date date null,
                start_time varchar(20) null,
                end_time varchar(20) null,
                reason varchar(190) null,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_bookings (
                id bigint unsigned primary key auto_increment,
                booking_number varchar(80) not null unique,
                guest_id varchar(190) null,
                restaurant_id bigint unsigned not null,
                table_id bigint unsigned null,
                zone_id bigint unsigned null,
                customer_name varchar(190) not null,
                customer_phone varchar(40) not null,
                customer_email varchar(190) null,
                booking_date date not null,
                booking_time varchar(20) not null,
                party_size int not null default 2,
                special_request text null,
                booking_status varchar(40) not null default \'pending\',
                payment_method varchar(60) not null default \'pay_at_restaurant\',
                payment_status varchar(40) not null default \'unpaid\',
                amount decimal(12,2) not null default 0,
                payment_reference varchar(190) null,
                payment_note text null,
                cancellation_reason text null,
                admin_note text null,
                created_at timestamp null,
                updated_at timestamp null,
                confirmed_at timestamp null,
                cancelled_at timestamp null,
                completed_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_payment_transactions (
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
                index restaurant_payment_booking_index (booking_id)
            )');
            $db->exec('create table if not exists restaurant_waitlists (
                id bigint unsigned primary key auto_increment,
                guest_id varchar(190) null,
                restaurant_id bigint unsigned not null,
                zone_id bigint unsigned null,
                customer_name varchar(190) not null,
                customer_phone varchar(40) not null,
                customer_email varchar(190) null,
                booking_date date not null,
                booking_time varchar(20) not null,
                party_size int not null default 2,
                special_request text null,
                status varchar(40) not null default \'pending\',
                admin_note text null,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_reviews (
                id bigint unsigned primary key auto_increment,
                restaurant_id bigint unsigned not null,
                booking_id bigint unsigned null,
                guest_id varchar(190) null,
                customer_name varchar(190) not null,
                rating tinyint unsigned not null default 5,
                comment text null,
                status tinyint(1) not null default 1,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_food_categories (
                id bigint unsigned primary key auto_increment,
                name varchar(190) not null,
                slug varchar(190) null,
                icon varchar(80) null,
                image varchar(255) null,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_food_items (
                id bigint unsigned primary key auto_increment,
                restaurant_id bigint unsigned not null,
                category_id bigint unsigned null,
                name varchar(190) not null,
                slug varchar(190) null,
                description text null,
                price decimal(12,2) not null default 0,
                discount_price decimal(12,2) null,
                image varchar(255) null,
                prep_time_minutes int not null default 25,
                is_veg tinyint(1) not null default 0,
                stock int not null default 100,
                status tinyint(1) not null default 1,
                is_featured tinyint(1) not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_food_orders (
                id bigint unsigned primary key auto_increment,
                order_number varchar(80) not null unique,
                guest_id varchar(190) null,
                restaurant_id bigint unsigned not null,
                zone_id bigint unsigned null,
                customer_name varchar(190) not null,
                customer_phone varchar(40) not null,
                customer_email varchar(190) null,
                delivery_address text not null,
                subtotal decimal(12,2) not null default 0,
                delivery_fee decimal(12,2) not null default 0,
                total decimal(12,2) not null default 0,
                payment_method varchar(60) not null default \'cash_on_delivery\',
                payment_status varchar(40) not null default \'unpaid\',
                order_status varchar(40) not null default \'pending\',
                note text null,
                created_at timestamp null,
                updated_at timestamp null,
                confirmed_at timestamp null,
                delivered_at timestamp null,
                cancelled_at timestamp null
            )');
            $db->exec('create table if not exists restaurant_food_order_items (
                id bigint unsigned primary key auto_increment,
                order_id bigint unsigned not null,
                food_item_id bigint unsigned not null,
                restaurant_id bigint unsigned not null,
                item_name varchar(190) not null,
                unit_price decimal(12,2) not null default 0,
                quantity int not null default 1,
                line_total decimal(12,2) not null default 0,
                created_at timestamp null
            )');
        } else {
            $db->exec('create table if not exists restaurant_categories (
                id integer primary key autoincrement,
                name text not null,
                slug text,
                image text,
                status integer not null default 1,
                sort_order integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_owners (
                id integer primary key autoincrement,
                zone_id integer,
                name text not null,
                phone text,
                email text,
                password text,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurants (
                id integer primary key autoincrement,
                owner_id integer,
                category_id integer,
                zone_id integer,
                name text not null,
                slug text,
                description text,
                cuisine text,
                address text not null,
                city text,
                area text,
                latitude real,
                longitude real,
                phone text,
                email text,
                opening_time text,
                closing_time text,
                average_cost real not null default 0,
                thumbnail text,
                gallery_json text,
                rating real not null default 0,
                review_count integer not null default 0,
                status integer not null default 1,
                is_featured integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_tables (
                id integer primary key autoincrement,
                restaurant_id integer not null,
                table_name text not null,
                capacity integer not null default 2,
                table_count integer not null default 1,
                status integer not null default 1,
                sort_order integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_shifts (
                id integer primary key autoincrement,
                restaurant_id integer not null,
                day_of_week integer not null default 0,
                start_time text not null,
                end_time text not null,
                slot_interval_minutes integer not null default 30,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_blackouts (
                id integer primary key autoincrement,
                restaurant_id integer,
                blackout_date text not null,
                end_date text,
                start_time text,
                end_time text,
                reason text,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_bookings (
                id integer primary key autoincrement,
                booking_number text not null unique,
                guest_id text,
                restaurant_id integer not null,
                table_id integer,
                zone_id integer,
                customer_name text not null,
                customer_phone text not null,
                customer_email text,
                booking_date text not null,
                booking_time text not null,
                party_size integer not null default 2,
                special_request text,
                booking_status text not null default "pending",
                payment_method text not null default "pay_at_restaurant",
                payment_status text not null default "unpaid",
                amount real not null default 0,
                payment_reference text,
                payment_note text,
                cancellation_reason text,
                admin_note text,
                created_at text,
                updated_at text,
                confirmed_at text,
                cancelled_at text,
                completed_at text
            )');
            $db->exec('create table if not exists restaurant_payment_transactions (
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
            $db->exec('create table if not exists restaurant_waitlists (
                id integer primary key autoincrement,
                guest_id text,
                restaurant_id integer not null,
                zone_id integer,
                customer_name text not null,
                customer_phone text not null,
                customer_email text,
                booking_date text not null,
                booking_time text not null,
                party_size integer not null default 2,
                special_request text,
                status text not null default "pending",
                admin_note text,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_reviews (
                id integer primary key autoincrement,
                restaurant_id integer not null,
                booking_id integer,
                guest_id text,
                customer_name text not null,
                rating integer not null default 5,
                comment text,
                status integer not null default 1,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_food_categories (
                id integer primary key autoincrement,
                name text not null,
                slug text,
                icon text,
                image text,
                status integer not null default 1,
                sort_order integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_food_items (
                id integer primary key autoincrement,
                restaurant_id integer not null,
                category_id integer,
                name text not null,
                slug text,
                description text,
                price real not null default 0,
                discount_price real,
                image text,
                prep_time_minutes integer not null default 25,
                is_veg integer not null default 0,
                stock integer not null default 100,
                status integer not null default 1,
                is_featured integer not null default 0,
                created_at text,
                updated_at text
            )');
            $db->exec('create table if not exists restaurant_food_orders (
                id integer primary key autoincrement,
                order_number text not null unique,
                guest_id text,
                restaurant_id integer not null,
                zone_id integer,
                customer_name text not null,
                customer_phone text not null,
                customer_email text,
                delivery_address text not null,
                subtotal real not null default 0,
                delivery_fee real not null default 0,
                total real not null default 0,
                payment_method text not null default "cash_on_delivery",
                payment_status text not null default "unpaid",
                order_status text not null default "pending",
                note text,
                created_at text,
                updated_at text,
                confirmed_at text,
                delivered_at text,
                cancelled_at text
            )');
            $db->exec('create table if not exists restaurant_food_order_items (
                id integer primary key autoincrement,
                order_id integer not null,
                food_item_id integer not null,
                restaurant_id integer not null,
                item_name text not null,
                unit_price real not null default 0,
                quantity integer not null default 1,
                line_total real not null default 0,
                created_at text
            )');
        }

        self::addColumnIfMissing('restaurant_payment_transactions', 'gateway_response', 'text');
        self::addColumnIfMissing('restaurant_payment_transactions', 'reconciled_at', $mysql ? 'timestamp null' : 'text');
        self::addColumnIfMissing('restaurant_payment_transactions', 'reconciled_by', $mysql ? 'varchar(190) null' : 'text');
        self::addColumnIfMissing('restaurant_payment_transactions', 'verified_at', $mysql ? 'timestamp null' : 'text');
        self::addColumnIfMissing('restaurants', 'delivery_enabled', $mysql ? 'tinyint(1) not null default 1' : 'integer not null default 1');
        self::addColumnIfMissing('restaurants', 'delivery_time_minutes', $mysql ? 'int not null default 30' : 'integer not null default 30');
        self::addColumnIfMissing('restaurants', 'delivery_fee', $mysql ? 'decimal(12,2) not null default 0' : 'real not null default 0');

        self::seed($mysql);
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

    private static function seed(bool $mysql): void
    {
        $db = Database::connection();
        $prefix = $mysql ? 'insert ignore' : 'insert or ignore';
        $db->exec($prefix . " into restaurant_categories (id, name, slug, status, sort_order, created_at, updated_at) values
            (1, 'Fine Dining', 'fine-dining', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (2, 'Family Restaurant', 'family-restaurant', 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (3, 'Cafe', 'cafe', 1, 3, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $db->exec($prefix . " into restaurant_food_categories (id, name, slug, icon, status, sort_order, created_at, updated_at) values
            (1, 'Pizza', 'pizza', 'local_pizza', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (2, 'Biryani', 'biryani', 'rice_bowl', 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (3, 'Burger', 'burger', 'lunch_dining', 1, 3, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (4, 'Indian', 'indian', 'restaurant', 1, 4, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $db->exec($prefix . " into restaurant_owners (id, name, phone, email, status, created_at, updated_at) values
            (1, 'Demo Restaurant Owner', '9999999999', 'restaurant-owner@example.com', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $db->exec($prefix . " into restaurants
            (id, owner_id, category_id, name, slug, description, cuisine, address, city, area, phone, opening_time, closing_time, average_cost, rating, review_count, status, is_featured, delivery_enabled, delivery_time_minutes, delivery_fee, created_at, updated_at) values
            (1, 1, 2, 'City Spice Table', 'city-spice-table', 'Family friendly restaurant with quick table reservations and fresh North Indian meals.', 'North Indian, Chinese', 'Hazratganj, Lucknow', 'Lucknow', 'Hazratganj', '9999999999', '11:00', '23:00', 800, 4.6, 128, 1, 1, 1, 30, 30, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (2, 1, 3, 'Urban Brew Cafe', 'urban-brew-cafe', 'Cafe for coffee, snacks, and casual meetings.', 'Cafe, Fast Food', 'Gomti Nagar, Lucknow', 'Lucknow', 'Gomti Nagar', '9999999998', '09:00', '22:00', 500, 4.4, 82, 1, 1, 1, 25, 25, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $db->exec($prefix . " into restaurant_food_items
            (id, restaurant_id, category_id, name, slug, description, price, discount_price, prep_time_minutes, is_veg, stock, status, is_featured, created_at, updated_at) values
            (1, 1, 4, 'Butter Chicken', 'butter-chicken', 'Creamy North Indian butter chicken with rich gravy.', 349, null, 30, 0, 50, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (2, 1, 4, 'Paneer Tikka', 'paneer-tikka', 'Smoky paneer tikka with mint chutney.', 249, 229, 25, 1, 45, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (3, 1, 2, 'Chicken Biryani', 'chicken-biryani', 'Aromatic biryani with raita.', 299, 269, 35, 0, 40, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (4, 2, 3, 'Classic Burger', 'classic-burger', 'Cafe style burger with fries.', 169, 149, 20, 0, 35, 1, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (5, 2, 1, 'Margherita Pizza', 'margherita-pizza', 'Cheese pizza with basil and tomato sauce.', 249, null, 25, 1, 30, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $db->exec($prefix . " into restaurant_tables (id, restaurant_id, table_name, capacity, table_count, status, sort_order, created_at, updated_at) values
            (1, 1, 'Couple Table', 2, 6, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (2, 1, 'Family Table', 4, 8, 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (3, 1, 'Large Table', 8, 2, 1, 3, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (4, 2, 'Cafe Table', 2, 10, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (5, 2, 'Group Table', 6, 3, 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        for ($day = 0; $day <= 6; $day++) {
            $db->exec($prefix . " into restaurant_shifts (restaurant_id, day_of_week, start_time, end_time, slot_interval_minutes, status, created_at, updated_at) values
                (1, {$day}, '12:00', '22:30', 30, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
                (2, {$day}, '10:00', '21:00', 30, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        }
    }
}
