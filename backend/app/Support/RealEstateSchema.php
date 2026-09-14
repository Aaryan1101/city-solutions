<?php

declare(strict_types=1);

namespace App\Support;

final class RealEstateSchema
{
    public static function ensure(): void
    {
        $db = Database::connection();
        $mysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';

        if ($mysql) {
            $db->exec('create table if not exists re_agents (
                id bigint unsigned primary key auto_increment,
                zone_id bigint unsigned null,
                business_name varchar(190) not null,
                owner_name varchar(190) not null,
                phone varchar(40) null,
                email varchar(190) null,
                password varchar(255) null,
                license_no varchar(120) null,
                bio text null,
                profile_image varchar(255) null,
                status varchar(40) not null default \'pending\',
                admin_note text null,
                created_at timestamp null,
                updated_at timestamp null,
                index re_agents_zone_index (zone_id),
                index re_agents_status_index (status)
            )');
            $db->exec('create table if not exists re_agent_documents (
                id bigint unsigned primary key auto_increment,
                agent_id bigint unsigned not null,
                doc_type varchar(80) not null,
                file_url varchar(255) null,
                status varchar(40) not null default \'pending\',
                admin_note text null,
                created_at timestamp null,
                updated_at timestamp null,
                index re_agent_documents_agent_index (agent_id)
            )');
            $db->exec('create table if not exists re_amenities (
                id bigint unsigned primary key auto_increment,
                name varchar(190) not null,
                icon varchar(80) null,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null
            )');
            $db->exec('create table if not exists re_properties (
                id bigint unsigned primary key auto_increment,
                agent_id bigint unsigned null,
                zone_id bigint unsigned null,
                title varchar(190) not null,
                slug varchar(220) null,
                listing_purpose varchar(40) not null default \'sell\',
                property_category varchar(80) not null default \'apartment\',
                price decimal(14,2) not null default 0,
                price_unit varchar(40) null,
                maintenance_charge decimal(12,2) not null default 0,
                deposit_amount decimal(12,2) not null default 0,
                city varchar(120) not null,
                area varchar(190) null,
                address text not null,
                latitude decimal(11,7) null,
                longitude decimal(11,7) null,
                description text null,
                bedrooms int not null default 0,
                bathrooms int not null default 0,
                balconies int not null default 0,
                parking int not null default 0,
                built_up_area decimal(12,2) not null default 0,
                carpet_area decimal(12,2) not null default 0,
                plot_area decimal(12,2) not null default 0,
                area_unit varchar(40) not null default \'sq ft\',
                furnishing varchar(80) null,
                ownership_type varchar(80) null,
                property_age varchar(80) null,
                contact_preference varchar(80) not null default \'inquiry\',
                thumbnail varchar(255) null,
                status varchar(40) not null default \'pending\',
                availability_status varchar(40) not null default \'available\',
                is_verified tinyint(1) not null default 0,
                verified_at timestamp null,
                is_featured tinyint(1) not null default 0,
                rejection_reason text null,
                created_at timestamp null,
                updated_at timestamp null,
                index re_properties_zone_index (zone_id),
                index re_properties_agent_index (agent_id),
                index re_properties_status_index (status, availability_status),
                index re_properties_listing_index (listing_purpose, property_category)
            )');
            $db->exec('create table if not exists re_property_images (
                id bigint unsigned primary key auto_increment,
                property_id bigint unsigned not null,
                image_url varchar(255) not null,
                sort_order int not null default 0,
                created_at timestamp null,
                index re_property_images_property_index (property_id)
            )');
            $db->exec('create table if not exists re_property_amenities (
                id bigint unsigned primary key auto_increment,
                property_id bigint unsigned not null,
                amenity_id bigint unsigned not null,
                created_at timestamp null,
                unique key re_property_amenity_unique (property_id, amenity_id)
            )');
            $db->exec('create table if not exists re_floor_plans (
                id bigint unsigned primary key auto_increment,
                property_id bigint unsigned not null,
                name varchar(190) not null,
                image_url varchar(255) null,
                area decimal(12,2) not null default 0,
                bedrooms int not null default 0,
                bathrooms int not null default 0,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null,
                index re_floor_plans_property_index (property_id)
            )');
            $db->exec('create table if not exists re_projects (
                id bigint unsigned primary key auto_increment,
                builder_id bigint unsigned null,
                zone_id bigint unsigned null,
                name varchar(190) not null,
                slug varchar(220) null,
                city varchar(120) not null,
                area varchar(190) null,
                address text null,
                latitude decimal(11,7) null,
                longitude decimal(11,7) null,
                description text null,
                launch_date date null,
                thumbnail varchar(255) null,
                status varchar(40) not null default \'pending\',
                is_featured tinyint(1) not null default 0,
                created_at timestamp null,
                updated_at timestamp null,
                index re_projects_zone_index (zone_id),
                index re_projects_builder_index (builder_id)
            )');
            $db->exec('create table if not exists re_project_units (
                id bigint unsigned primary key auto_increment,
                project_id bigint unsigned not null,
                name varchar(190) not null,
                price_from decimal(14,2) not null default 0,
                area decimal(12,2) not null default 0,
                bedrooms int not null default 0,
                bathrooms int not null default 0,
                floor_plan_image varchar(255) null,
                status tinyint(1) not null default 1,
                sort_order int not null default 0,
                created_at timestamp null,
                updated_at timestamp null,
                index re_project_units_project_index (project_id)
            )');
            $db->exec('create table if not exists re_favorites (
                id bigint unsigned primary key auto_increment,
                guest_id varchar(190) not null,
                property_id bigint unsigned not null,
                created_at timestamp null,
                unique key re_favorites_unique (guest_id, property_id)
            )');
            $db->exec('create table if not exists re_saved_searches (
                id bigint unsigned primary key auto_increment,
                guest_id varchar(190) not null,
                name varchar(190) not null,
                filters_json text null,
                notify tinyint(1) not null default 0,
                created_at timestamp null,
                updated_at timestamp null,
                index re_saved_searches_guest_index (guest_id)
            )');
            $db->exec('create table if not exists re_inquiries (
                id bigint unsigned primary key auto_increment,
                inquiry_number varchar(80) not null unique,
                guest_id varchar(190) null,
                property_id bigint unsigned not null,
                agent_id bigint unsigned null,
                customer_name varchar(190) not null,
                customer_phone varchar(40) not null,
                customer_email varchar(190) null,
                message text null,
                status varchar(40) not null default \'open\',
                agent_reply text null,
                replied_at timestamp null,
                created_at timestamp null,
                updated_at timestamp null,
                index re_inquiries_property_index (property_id),
                index re_inquiries_agent_index (agent_id)
            )');
            $db->exec('create table if not exists re_site_visits (
                id bigint unsigned primary key auto_increment,
                visit_number varchar(80) not null unique,
                guest_id varchar(190) null,
                property_id bigint unsigned not null,
                agent_id bigint unsigned null,
                customer_name varchar(190) not null,
                customer_phone varchar(40) not null,
                customer_email varchar(190) null,
                address text null,
                requested_date date not null,
                requested_time varchar(40) not null,
                note text null,
                status varchar(40) not null default \'pending\',
                admin_note text null,
                confirmed_at timestamp null,
                completed_at timestamp null,
                cancelled_at timestamp null,
                created_at timestamp null,
                updated_at timestamp null,
                index re_site_visits_guest_index (guest_id),
                index re_site_visits_agent_index (agent_id)
            )');
            $db->exec('create table if not exists re_complaints (
                id bigint unsigned primary key auto_increment,
                complaint_number varchar(80) not null unique,
                guest_id varchar(190) null,
                property_id bigint unsigned null,
                agent_id bigint unsigned null,
                customer_name varchar(190) null,
                customer_phone varchar(40) null,
                message text not null,
                status varchar(40) not null default \'pending\',
                admin_note text null,
                created_at timestamp null,
                updated_at timestamp null
            )');
        } else {
            foreach (self::sqliteTables() as $sql) {
                $db->exec($sql);
            }
        }

        self::seedDemo();
    }

    private static function sqliteTables(): array
    {
        return [
            'create table if not exists re_agents (id integer primary key autoincrement, zone_id integer, business_name text not null, owner_name text not null, phone text, email text, password text, license_no text, bio text, profile_image text, status text not null default "pending", admin_note text, created_at text, updated_at text)',
            'create table if not exists re_agent_documents (id integer primary key autoincrement, agent_id integer not null, doc_type text not null, file_url text, status text not null default "pending", admin_note text, created_at text, updated_at text)',
            'create table if not exists re_amenities (id integer primary key autoincrement, name text not null, icon text, status integer not null default 1, sort_order integer not null default 0, created_at text, updated_at text)',
            'create table if not exists re_properties (id integer primary key autoincrement, agent_id integer, zone_id integer, title text not null, slug text, listing_purpose text not null default "sell", property_category text not null default "apartment", price real not null default 0, price_unit text, maintenance_charge real not null default 0, deposit_amount real not null default 0, city text not null, area text, address text not null, latitude real, longitude real, description text, bedrooms integer not null default 0, bathrooms integer not null default 0, balconies integer not null default 0, parking integer not null default 0, built_up_area real not null default 0, carpet_area real not null default 0, plot_area real not null default 0, area_unit text not null default "sq ft", furnishing text, ownership_type text, property_age text, contact_preference text not null default "inquiry", thumbnail text, status text not null default "pending", availability_status text not null default "available", is_verified integer not null default 0, verified_at text, is_featured integer not null default 0, rejection_reason text, created_at text, updated_at text)',
            'create table if not exists re_property_images (id integer primary key autoincrement, property_id integer not null, image_url text not null, sort_order integer not null default 0, created_at text)',
            'create table if not exists re_property_amenities (id integer primary key autoincrement, property_id integer not null, amenity_id integer not null, created_at text)',
            'create table if not exists re_floor_plans (id integer primary key autoincrement, property_id integer not null, name text not null, image_url text, area real not null default 0, bedrooms integer not null default 0, bathrooms integer not null default 0, sort_order integer not null default 0, created_at text, updated_at text)',
            'create table if not exists re_projects (id integer primary key autoincrement, builder_id integer, zone_id integer, name text not null, slug text, city text not null, area text, address text, latitude real, longitude real, description text, launch_date text, thumbnail text, status text not null default "pending", is_featured integer not null default 0, created_at text, updated_at text)',
            'create table if not exists re_project_units (id integer primary key autoincrement, project_id integer not null, name text not null, price_from real not null default 0, area real not null default 0, bedrooms integer not null default 0, bathrooms integer not null default 0, floor_plan_image text, status integer not null default 1, sort_order integer not null default 0, created_at text, updated_at text)',
            'create table if not exists re_favorites (id integer primary key autoincrement, guest_id text not null, property_id integer not null, created_at text)',
            'create table if not exists re_saved_searches (id integer primary key autoincrement, guest_id text not null, name text not null, filters_json text, notify integer not null default 0, created_at text, updated_at text)',
            'create table if not exists re_inquiries (id integer primary key autoincrement, inquiry_number text not null unique, guest_id text, property_id integer not null, agent_id integer, customer_name text not null, customer_phone text not null, customer_email text, message text, status text not null default "open", agent_reply text, replied_at text, created_at text, updated_at text)',
            'create table if not exists re_site_visits (id integer primary key autoincrement, visit_number text not null unique, guest_id text, property_id integer not null, agent_id integer, customer_name text not null, customer_phone text not null, customer_email text, address text, requested_date text not null, requested_time text not null, note text, status text not null default "pending", admin_note text, confirmed_at text, completed_at text, cancelled_at text, created_at text, updated_at text)',
            'create table if not exists re_complaints (id integer primary key autoincrement, complaint_number text not null unique, guest_id text, property_id integer, agent_id integer, customer_name text, customer_phone text, message text not null, status text not null default "pending", admin_note text, created_at text, updated_at text)',
        ];
    }

    private static function seedDemo(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }
        $now = self::nowExpression();
        $password = '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim';

        $db->exec("insert into re_agents
            (id, zone_id, business_name, owner_name, phone, email, password, license_no, bio, profile_image, status, admin_note, created_at, updated_at) values
            (1, 1, 'City Nest Realty', 'Amit Verma', '9000000701', 'agent.realestate@example.com', '$password', 'UP-RE-DEMO-001', 'Demo approved agent for City Solutions real estate.', '/uploads/demo-assets/real_estate_house.png', 'approved', 'Demo approved agent.', $now, $now),
            (2, 2, 'Gomti Builders', 'Neha Singh', '9000000702', 'builder.realestate@example.com', '$password', 'UP-RE-DEMO-002', 'Demo builder account for projects and new launches.', '/uploads/demo-assets/real_estate_apartment.png', 'approved', 'Demo approved builder.', $now, $now)
            on duplicate key update zone_id = values(zone_id), business_name = values(business_name), owner_name = values(owner_name), phone = values(phone), email = values(email), license_no = values(license_no), bio = values(bio), profile_image = values(profile_image), status = values(status), updated_at = $now");

        $db->exec("insert into re_amenities
            (id, name, icon, status, sort_order, created_at, updated_at) values
            (1, 'Parking', 'local_parking', 1, 1, $now, $now),
            (2, 'Lift', 'elevator', 1, 2, $now, $now),
            (3, 'Security', 'security', 1, 3, $now, $now),
            (4, 'Power Backup', 'bolt', 1, 4, $now, $now),
            (5, 'Park Facing', 'park', 1, 5, $now, $now)
            on duplicate key update name = values(name), icon = values(icon), status = values(status), sort_order = values(sort_order), updated_at = $now");

        $db->exec("insert into re_properties
            (id, agent_id, zone_id, title, slug, listing_purpose, property_category, price, price_unit, maintenance_charge, deposit_amount, city, area, address, latitude, longitude, description, bedrooms, bathrooms, balconies, parking, built_up_area, carpet_area, plot_area, area_unit, furnishing, ownership_type, property_age, contact_preference, thumbnail, status, availability_status, is_verified, verified_at, is_featured, created_at, updated_at) values
            (1, 1, 1, 'Modern 3 BHK Apartment in Hazratganj', 'modern-3bhk-hazratganj', 'sell', 'apartment', 7600000, 'total', 3500, 0, 'Lucknow', 'Hazratganj', 'Hazratganj, Lucknow', 26.8467, 80.9462, 'Ready-to-move apartment near shopping, schools and offices.', 3, 2, 2, 1, 1450, 1180, 0, 'sq ft', 'Semi Furnished', 'Freehold', '2 years', 'inquiry', '/uploads/demo-assets/real_estate_apartment.png', 'approved', 'available', 1, $now, 1, $now, $now),
            (2, 1, 2, 'Independent House near Gomti Nagar', 'independent-house-gomti-nagar', 'sell', 'villa', 12500000, 'total', 0, 0, 'Lucknow', 'Gomti Nagar', 'Gomti Nagar, Lucknow', 26.8519, 81.0108, 'Spacious independent house with parking and garden space.', 4, 3, 2, 2, 2400, 1900, 1800, 'sq ft', 'Unfurnished', 'Freehold', '5 years', 'call,inquiry', '/uploads/demo-assets/real_estate_house.png', 'approved', 'available', 1, $now, 1, $now, $now),
            (3, 2, 1, 'Commercial Office Space in Aliganj', 'commercial-office-aliganj', 'rent', 'office', 45000, 'monthly', 5000, 90000, 'Lucknow', 'Aliganj', 'Aliganj, Lucknow', 26.8898, 80.9436, 'Road-facing commercial office suitable for teams and clinics.', 0, 1, 0, 1, 900, 780, 0, 'sq ft', 'Furnished', 'Leasehold', '1 year', 'inquiry', '/uploads/demo-assets/real_estate_living_room.png', 'approved', 'available', 0, null, 0, $now, $now)
            on duplicate key update agent_id = values(agent_id), zone_id = values(zone_id), title = values(title), listing_purpose = values(listing_purpose), property_category = values(property_category), price = values(price), price_unit = values(price_unit), city = values(city), area = values(area), address = values(address), latitude = values(latitude), longitude = values(longitude), description = values(description), bedrooms = values(bedrooms), bathrooms = values(bathrooms), balconies = values(balconies), parking = values(parking), built_up_area = values(built_up_area), carpet_area = values(carpet_area), plot_area = values(plot_area), furnishing = values(furnishing), ownership_type = values(ownership_type), property_age = values(property_age), thumbnail = values(thumbnail), status = values(status), availability_status = values(availability_status), is_verified = values(is_verified), is_featured = values(is_featured), updated_at = $now");

        $db->exec('delete from re_property_images where property_id in (1,2,3)');
        $db->exec("insert into re_property_images (property_id, image_url, sort_order, created_at) values
            (1, '/uploads/demo-assets/real_estate_apartment.png', 1, $now),
            (1, '/uploads/demo-assets/real_estate_living_room.png', 2, $now),
            (2, '/uploads/demo-assets/real_estate_house.png', 1, $now),
            (2, '/uploads/demo-assets/real_estate_living_room.png', 2, $now),
            (3, '/uploads/demo-assets/real_estate_living_room.png', 1, $now)");

        $db->exec('delete from re_property_amenities where property_id in (1,2,3)');
        $db->exec("insert into re_property_amenities (property_id, amenity_id, created_at) values
            (1, 1, $now), (1, 2, $now), (1, 3, $now), (1, 4, $now),
            (2, 1, $now), (2, 3, $now), (2, 5, $now),
            (3, 1, $now), (3, 4, $now)");

        $db->exec("insert into re_floor_plans
            (id, property_id, name, image_url, area, bedrooms, bathrooms, sort_order, created_at, updated_at) values
            (1, 1, '3 BHK Layout', '/uploads/demo-assets/real_estate_living_room.png', 1450, 3, 2, 1, $now, $now),
            (2, 2, 'House Layout', '/uploads/demo-assets/real_estate_house.png', 2400, 4, 3, 1, $now, $now)
            on duplicate key update name = values(name), image_url = values(image_url), area = values(area), bedrooms = values(bedrooms), bathrooms = values(bathrooms), sort_order = values(sort_order), updated_at = $now");

        $db->exec("insert into re_projects
            (id, builder_id, zone_id, name, slug, city, area, address, latitude, longitude, description, launch_date, thumbnail, status, is_featured, created_at, updated_at) values
            (1, 2, 2, 'Gomti Greens Residency', 'gomti-greens-residency', 'Lucknow', 'Gomti Nagar Extension', 'Gomti Nagar Extension, Lucknow', 26.8519, 81.0108, 'Demo new launch project with apartments and amenities.', current_date, '/uploads/demo-assets/real_estate_apartment.png', 'approved', 1, $now, $now)
            on duplicate key update builder_id = values(builder_id), zone_id = values(zone_id), name = values(name), city = values(city), area = values(area), address = values(address), description = values(description), thumbnail = values(thumbnail), status = values(status), is_featured = values(is_featured), updated_at = $now");

        $db->exec("insert into re_project_units
            (id, project_id, name, price_from, area, bedrooms, bathrooms, floor_plan_image, status, sort_order, created_at, updated_at) values
            (1, 1, '2 BHK Apartment', 5200000, 1050, 2, 2, '/uploads/demo-assets/real_estate_living_room.png', 1, 1, $now, $now),
            (2, 1, '3 BHK Apartment', 7200000, 1450, 3, 2, '/uploads/demo-assets/real_estate_apartment.png', 1, 2, $now, $now)
            on duplicate key update name = values(name), price_from = values(price_from), area = values(area), bedrooms = values(bedrooms), bathrooms = values(bathrooms), floor_plan_image = values(floor_plan_image), status = values(status), sort_order = values(sort_order), updated_at = $now");
    }

    private static function nowExpression(): string
    {
        $driver = Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return $driver === 'mysql' ? 'current_timestamp' : "datetime('now')";
    }
}
