create table if not exists taxi_vehicle_types (
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
);

create table if not exists taxi_drivers (
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
    availability_status varchar(40) not null default 'offline',
    rating decimal(3,2) not null default 0,
    status varchar(40) not null default 'pending',
    admin_note text null,
    last_seen_at timestamp null,
    created_at timestamp null,
    updated_at timestamp null,
    index taxi_drivers_phone_index (phone),
    index taxi_drivers_zone_index (zone_id)
);

create table if not exists taxi_rides (
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
    payment_method varchar(60) not null default 'cash',
    payment_status varchar(40) not null default 'unpaid',
    ride_status varchar(40) not null default 'requested',
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
);

set @schema_name = database();
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'cancellation_fee') = 0, 'alter table taxi_rides add column cancellation_fee decimal(12,2) not null default 0 after cancelled_by', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'driver_earning') = 0, 'alter table taxi_rides add column driver_earning decimal(12,2) not null default 0 after cancellation_fee', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'commission_amount') = 0, 'alter table taxi_rides add column commission_amount decimal(12,2) not null default 0 after driver_earning', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;

create table if not exists taxi_ride_status_history (
    id bigint unsigned primary key auto_increment,
    ride_id bigint unsigned not null,
    status varchar(60) not null,
    actor_type varchar(40) not null,
    actor_name varchar(190) null,
    note text null,
    created_at timestamp null,
    index taxi_ride_history_ride_index (ride_id)
);

create table if not exists taxi_quotes (
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
);

create table if not exists taxi_ride_driver_responses (
    id bigint unsigned primary key auto_increment,
    ride_id bigint unsigned not null,
    driver_id bigint unsigned not null,
    response varchar(30) not null,
    created_at timestamp null,
    unique key taxi_driver_response_unique (ride_id, driver_id),
    index taxi_driver_response_driver_index (driver_id)
);

set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'customer_id') = 0, 'alter table taxi_rides add column customer_id bigint unsigned null after guest_id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'quote_token') = 0, 'alter table taxi_rides add column quote_token varchar(128) null after commission_amount', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'route_polyline') = 0, 'alter table taxi_rides add column route_polyline longtext null after quote_token', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'route_source') = 0, 'alter table taxi_rides add column route_source varchar(40) null after route_polyline', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'driver_eta_minutes') = 0, 'alter table taxi_rides add column driver_eta_minutes int null after route_source', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'customer_rating') = 0, 'alter table taxi_rides add column customer_rating tinyint unsigned null after driver_eta_minutes', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_rides' and column_name = 'customer_review') = 0, 'alter table taxi_rides add column customer_review text null after customer_rating', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_vehicle_types' and column_name = 'service_fee') = 0, 'alter table taxi_vehicle_types add column service_fee decimal(12,2) not null default 0 after cancellation_fee', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_vehicle_types' and column_name = 'waiting_fee_per_minute') = 0, 'alter table taxi_vehicle_types add column waiting_fee_per_minute decimal(12,2) not null default 0 after service_fee', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_vehicle_types' and column_name = 'included_wait_minutes') = 0, 'alter table taxi_vehicle_types add column included_wait_minutes int not null default 3 after waiting_fee_per_minute', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;

create table if not exists taxi_driver_ledgers (
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
);

set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'delivery_men' and column_name = 'password') = 0, 'alter table delivery_men add column password varchar(255) null after email', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'delivery_men' and column_name = 'auth_token') = 0, 'alter table delivery_men add column auth_token varchar(255) null after password', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'delivery_men' and column_name = 'auth_token_expires_at') = 0, 'alter table delivery_men add column auth_token_expires_at timestamp null after auth_token', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'delivery_men' and column_name = 'zone_id') = 0, 'alter table delivery_men add column zone_id bigint unsigned null after auth_token', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'delivery_men' and column_name = 'availability_status') = 0, 'alter table delivery_men add column availability_status varchar(40) not null default "offline" after vehicle_number', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'delivery_men' and column_name = 'current_latitude') = 0, 'alter table delivery_men add column current_latitude decimal(10,7) null after availability_status', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'delivery_men' and column_name = 'current_longitude') = 0, 'alter table delivery_men add column current_longitude decimal(10,7) null after current_latitude', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'delivery_men' and column_name = 'last_seen_at') = 0, 'alter table delivery_men add column last_seen_at timestamp null after current_longitude', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;

set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_providers' and column_name = 'auth_token') = 0, 'alter table service_providers add column auth_token varchar(255) null after password', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_providers' and column_name = 'auth_token_expires_at') = 0, 'alter table service_providers add column auth_token_expires_at timestamp null after auth_token', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_providers' and column_name = 'availability_status') = 0, 'alter table service_providers add column availability_status varchar(40) not null default "offline" after auth_token', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_drivers' and column_name = 'auth_token_expires_at') = 0, 'alter table taxi_drivers add column auth_token_expires_at timestamp null after auth_token', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_drivers' and column_name = 'rc_number') = 0, 'alter table taxi_drivers add column rc_number varchar(120) null after license_number', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_drivers' and column_name = 'profile_photo') = 0, 'alter table taxi_drivers add column profile_photo varchar(500) null after rc_number', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_drivers' and column_name = 'license_document') = 0, 'alter table taxi_drivers add column license_document varchar(500) null after profile_photo', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_drivers' and column_name = 'vehicle_document') = 0, 'alter table taxi_drivers add column vehicle_document varchar(500) null after license_document', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_drivers' and column_name = 'insurance_document') = 0, 'alter table taxi_drivers add column insurance_document varchar(500) null after vehicle_document', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_drivers' and column_name = 'license_expiry') = 0, 'alter table taxi_drivers add column license_expiry date null after insurance_document', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'taxi_drivers' and column_name = 'insurance_expiry') = 0, 'alter table taxi_drivers add column insurance_expiry date null after license_expiry', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_providers' and column_name = 'last_seen_at') = 0, 'alter table service_providers add column last_seen_at timestamp null after availability_status', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;

insert into taxi_vehicle_types
(id, name, slug, seats, base_fare, per_km_fare, per_minute_fare, minimum_fare, cancellation_fee, icon, status, sort_order, created_at, updated_at) values
(1, 'Bike', 'bike', 1, 25, 8, 1, 35, 15, 'two_wheeler', 1, 1, current_timestamp, current_timestamp),
(2, 'Mini', 'mini', 4, 45, 14, 1.5, 75, 35, 'local_taxi', 1, 2, current_timestamp, current_timestamp),
(3, 'Sedan', 'sedan', 4, 60, 18, 2, 95, 45, 'directions_car', 1, 3, current_timestamp, current_timestamp),
(4, 'SUV', 'suv', 6, 85, 24, 2.5, 140, 60, 'airport_shuttle', 1, 4, current_timestamp, current_timestamp)
on duplicate key update name = values(name), seats = values(seats), base_fare = values(base_fare), per_km_fare = values(per_km_fare), per_minute_fare = values(per_minute_fare), minimum_fare = values(minimum_fare), status = values(status), updated_at = current_timestamp;

insert into taxi_drivers
(id, zone_id, name, phone, email, password, vehicle_type_id, vehicle_name, vehicle_number, license_number, availability_status, rating, status, created_at, updated_at) values
(1, 1, 'Ravi Cab Driver', '9000000001', 'ravi.driver@example.com', '$2y$12$UGY2FDNZYld97uSH7l329.mfQcYNrtngqrwrMQVTnmzTPCmEyloYC', 2, 'Swift Dzire', 'UP32 CT 1001', 'DL-LKO-1001', 'offline', 4.70, 'approved', current_timestamp, current_timestamp)
on duplicate key update zone_id = values(zone_id), name = values(name), phone = values(phone), vehicle_type_id = values(vehicle_type_id), vehicle_name = values(vehicle_name), vehicle_number = values(vehicle_number), status = values(status), updated_at = current_timestamp;

-- Demo worker password for the taxi driver above: 123456
