set foreign_key_checks = 0;

insert into admins
(id, name, email, password, role, created_at, updated_at) values
(1, 'Admin', 'admin@citysolutions.local', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 'super_admin', current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
password = values(password),
role = values(role),
updated_at = current_timestamp;

insert into zones
(id, name, city, state, pincode, pincodes, latitude, longitude, radius_km, status, sort_order, created_at, updated_at) values
(1, 'Lucknow Central', 'Lucknow', 'Uttar Pradesh', '226001', '226001,226018,226019', 26.8467000, 80.9462000, 12.00, 1, 1, current_timestamp, current_timestamp),
(2, 'Gomti Nagar', 'Lucknow', 'Uttar Pradesh', '226010', '226010,226016,226028', 26.8519000, 81.0108000, 10.00, 1, 2, current_timestamp, current_timestamp),
(3, 'Aliganj', 'Lucknow', 'Uttar Pradesh', '226024', '226020,226021,226024', 26.8898000, 80.9436000, 8.00, 1, 3, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
city = values(city),
state = values(state),
pincode = values(pincode),
pincodes = values(pincodes),
latitude = values(latitude),
longitude = values(longitude),
radius_km = values(radius_km),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

create table if not exists service_categories (
    id bigint unsigned primary key auto_increment,
    name varchar(190) not null,
    description text null,
    icon varchar(80) null,
    status tinyint(1) not null default 1,
    sort_order int not null default 0,
    created_at timestamp null,
    updated_at timestamp null
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists service_providers (
    id bigint unsigned primary key auto_increment,
    zone_id bigint unsigned null,
    name varchar(190) not null,
    phone varchar(40) null,
    email varchar(190) null,
    area varchar(190) null,
    password varchar(255) null,
    commission_percent decimal(5,2) not null default 0,
    status tinyint(1) not null default 1,
    created_at timestamp null,
    updated_at timestamp null
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists services (
    id bigint unsigned primary key auto_increment,
    zone_id bigint unsigned null,
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
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists service_addons (
    id bigint unsigned primary key auto_increment,
    service_id bigint unsigned not null,
    name varchar(190) not null,
    description text null,
    price decimal(12,2) not null default 0,
    status tinyint(1) not null default 1,
    sort_order int not null default 0,
    created_at timestamp null,
    updated_at timestamp null
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists service_slots (
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
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists service_bookings (
    id bigint unsigned primary key auto_increment,
    booking_number varchar(80) not null unique,
    zone_id bigint unsigned null,
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
    payment_method varchar(60) not null default 'cash_on_service',
    payment_status varchar(40) not null default 'unpaid',
    booking_status varchar(40) not null default 'pending',
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
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

set @schema_name = database();
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_providers' and column_name = 'zone_id') = 0, 'alter table service_providers add column zone_id bigint unsigned null after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'services' and column_name = 'zone_id') = 0, 'alter table services add column zone_id bigint unsigned null after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'services' and column_name = 'warranty_days') = 0, 'alter table services add column warranty_days int not null default 0 after duration_minutes', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'services' and column_name = 'checklist_json') = 0, 'alter table services add column checklist_json text null after description', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_bookings' and column_name = 'zone_id') = 0, 'alter table service_bookings add column zone_id bigint unsigned null after booking_number', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_bookings' and column_name = 'warranty_days') = 0, 'alter table service_bookings add column warranty_days int not null default 0 after completed_at', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_bookings' and column_name = 'warranty_until') = 0, 'alter table service_bookings add column warranty_until date null after warranty_days', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;

insert into categories
(id, module_key, name, slug, image, shipping_cost, status, sort_order, created_at, updated_at) values
(1, 'mart', 'Fruits & Vegetables', 'fruits-vegetables', null, 20.00, 1, 1, current_timestamp, current_timestamp),
(2, 'mart', 'Dairy & Bakery', 'dairy-bakery', null, 20.00, 1, 2, current_timestamp, current_timestamp),
(3, 'mart', 'Grocery Staples', 'grocery-staples', null, 30.00, 1, 3, current_timestamp, current_timestamp),
(4, 'mart', 'Snacks & Beverages', 'snacks-beverages', null, 20.00, 1, 4, current_timestamp, current_timestamp),
(101, 'ecommerce', 'Mobiles & Accessories', 'mobiles-accessories', null, 49.00, 1, 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 'Fashion', 'fashion', null, 39.00, 1, 2, current_timestamp, current_timestamp),
(103, 'ecommerce', 'Home Appliances', 'home-appliances', null, 79.00, 1, 3, current_timestamp, current_timestamp),
(501, 'medical', 'Pain Relief', 'pain-relief', null, 20.00, 1, 1, current_timestamp, current_timestamp),
(502, 'medical', 'Cold & Cough', 'cold-cough', null, 20.00, 1, 2, current_timestamp, current_timestamp),
(503, 'medical', 'Vitamins', 'vitamins', null, 20.00, 1, 3, current_timestamp, current_timestamp)
on duplicate key update
module_key = values(module_key),
name = values(name),
slug = values(slug),
shipping_cost = values(shipping_cost),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into brands
(id, module_key, name, slug, image, status, sort_order, created_at, updated_at) values
(1, 'mart', 'FreshKart', 'freshkart', null, 1, 1, current_timestamp, current_timestamp),
(2, 'mart', 'Daily Dairy', 'daily-dairy', null, 1, 2, current_timestamp, current_timestamp),
(3, 'mart', 'SnackJoy', 'snackjoy', null, 1, 3, current_timestamp, current_timestamp),
(101, 'ecommerce', 'UrbanTech', 'urbantech', null, 1, 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 'DailyWear', 'dailywear', null, 1, 2, current_timestamp, current_timestamp),
(103, 'ecommerce', 'HomePro', 'homepro', null, 1, 3, current_timestamp, current_timestamp),
(501, 'medical', 'HealthPlus', 'healthplus', null, 1, 1, current_timestamp, current_timestamp),
(502, 'medical', 'CureWell', 'curewell', null, 1, 2, current_timestamp, current_timestamp),
(503, 'medical', 'DailyCare', 'dailycare', null, 1, 3, current_timestamp, current_timestamp)
on duplicate key update
module_key = values(module_key),
name = values(name),
slug = values(slug),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into vendors
(id, module_key, zone_id, shop_name, owner_name, phone, email, password, address, city, status, admin_note, created_at, updated_at) values
(1, 'mart', 1, 'Lucknow Fresh Store', 'Amit Verma', '9000000001', 'freshstore@example.com', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 'Hazratganj Market', 'Lucknow', 'approved', 'Demo mart vendor.', current_timestamp, current_timestamp),
(2, 'mart', 2, 'Daily Dairy Hub', 'Neha Singh', '9000000002', 'dairyhub@example.com', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 'Gomti Nagar', 'Lucknow', 'approved', 'Demo mart vendor.', current_timestamp, current_timestamp),
(101, 'ecommerce', 1, 'UrbanTech Store', 'Amit Verma', '9000000101', 'urbantech@example.com', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 'Hazratganj, Lucknow', 'Lucknow', 'approved', 'Demo e-commerce vendor.', current_timestamp, current_timestamp),
(102, 'ecommerce', 2, 'City Lifestyle Hub', 'Neha Singh', '9000000102', 'lifestyle@example.com', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 'Gomti Nagar, Lucknow', 'Lucknow', 'approved', 'Demo e-commerce vendor.', current_timestamp, current_timestamp),
(501, 'medical', 1, 'City Pharmacy', 'Dr. Kavita', '9000000501', 'pharmacy@example.com', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 'Hazratganj, Lucknow', 'Lucknow', 'approved', 'Demo medical vendor.', current_timestamp, current_timestamp)
on duplicate key update
module_key = values(module_key),
zone_id = values(zone_id),
shop_name = values(shop_name),
owner_name = values(owner_name),
email = values(email),
address = values(address),
city = values(city),
status = values(status),
admin_note = values(admin_note),
updated_at = current_timestamp;

insert into products
(id, module_key, zone_id, vendor_id, brand_id, category_id, name, slug, description, unit, price, discount_price, stock, sku, thumbnail, tax_percent, shipping_cost, barcode, seo_title, seo_description, attributes_json, colors_json, is_digital, digital_file_url, is_flash_deal, flash_deal_ends_at, is_clearance, status, is_featured, created_at, updated_at) values
(1, 'mart', 1, 1, 1, 1, 'Fresh Apple', 'fresh-apple', 'Crisp seasonal apples for daily fruit bowls and snacks.', '1 kg', 180.00, 160.00, 25, 'MART-APL-001', null, 5.00, 20.00, '890100000001', 'Fresh Apple 1 kg', 'Seasonal apples with crisp texture.', '["Origin: Himachal","Storage: Cool place"]', '["Red","Green"]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(2, 'mart', 1, 1, 1, 1, 'Banana Robusta', 'banana-robusta', 'Naturally sweet bananas, ideal for breakfast.', '1 dozen', 72.00, 65.00, 40, 'MART-BAN-001', null, 5.00, 20.00, '890100000002', 'Banana Robusta', 'Breakfast-friendly bananas.', '["Ripeness: Medium","Use: Smoothies"]', '["Yellow"]', 0, null, 0, null, 1, 1, 1, current_timestamp, current_timestamp),
(3, 'mart', 2, 2, 2, 2, 'Full Cream Milk', 'full-cream-milk', 'Fresh full cream milk pack for tea and cooking.', '1 litre', 68.00, null, 60, 'MART-MLK-001', null, 5.00, 20.00, '890100000003', 'Full Cream Milk 1 litre', 'Fresh dairy milk.', '["Type: Full Cream","Pack: 1 litre"]', '["White"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(4, 'mart', 2, 2, 3, 4, 'Potato Chips', 'potato-chips', 'Classic salted potato chips for quick snacking.', '150 g', 50.00, 45.00, 80, 'MART-CHP-001', null, 12.00, 20.00, '890100000004', 'Potato Chips 150 g', 'Salted chips for snacks.', '["Flavor: Salted","Pack: 150 g"]', '["Gold"]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(101, 'ecommerce', 1, 101, 101, 101, 'Wireless Earbuds', 'wireless-earbuds', 'Bluetooth earbuds with charging case and clear calling.', 'piece', 1499.00, 1299.00, 18, 'ECM-EAR-101', null, 18.00, 49.00, '890200000101', 'Wireless Earbuds', 'Compact earbuds with long battery backup.', '["Connectivity: Bluetooth","Battery: 24 hours"]', '["Black","White"]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 1, 101, 101, 101, 'Fast Charging Cable', 'fast-charging-cable', 'Durable type-C cable for fast charging.', 'piece', 299.00, 249.00, 75, 'ECM-CBL-102', null, 18.00, 29.00, '890200000102', 'Fast Charging Cable', 'Durable fast charging type-C cable.', '["Length: 1 metre","Connector: Type-C"]', '["White"]', 0, null, 0, null, 1, 1, 1, current_timestamp, current_timestamp),
(103, 'ecommerce', 2, 102, 102, 102, 'Cotton T-Shirt', 'cotton-t-shirt', 'Soft cotton round-neck t-shirt for daily wear.', 'piece', 499.00, 399.00, 40, 'ECM-TSH-103', null, 5.00, 39.00, '890200000103', 'Cotton T-Shirt', 'Comfortable daily wear t-shirt.', '["Size: M,L,XL","Fabric: Cotton"]', '["Blue","Grey"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(104, 'ecommerce', 2, 102, 103, 103, 'Electric Kettle', 'electric-kettle', '1.8 litre stainless steel electric kettle.', 'piece', 1199.00, 999.00, 16, 'ECM-KTL-104', null, 18.00, 79.00, '890200000104', 'Electric Kettle 1.8L', 'Fast boiling kettle.', '["Capacity: 1.8 litre","Material: Stainless steel"]', '["Silver"]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(501, 'medical', 1, 501, 501, 501, 'Paracetamol 500mg', 'paracetamol-500mg', 'Common fever and pain relief tablet. Use as directed.', '10 tablets', 35.00, 30.00, 100, 'MED-PARA-500', null, 5.00, 20.00, '890300000501', 'Paracetamol 500mg', 'Fever and pain relief tablets.', '["Prescription: Not required","Pack: 10 tablets"]', '[]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(502, 'medical', 1, 501, 502, 502, 'Cough Syrup', 'cough-syrup', 'Cough relief syrup for dry and wet cough symptoms.', '100 ml', 120.00, 99.00, 60, 'MED-COUGH-100', null, 12.00, 20.00, '890300000502', 'Cough Syrup 100ml', 'Cough relief syrup.', '["Volume: 100 ml","Use: Cough care"]', '[]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(503, 'medical', 2, 501, 503, 503, 'Vitamin C Tablets', 'vitamin-c-tablets', 'Daily vitamin C supplement for immunity support.', '30 tablets', 180.00, 149.00, 45, 'MED-VITC-30', null, 12.00, 20.00, '890300000503', 'Vitamin C Tablets', 'Daily immunity supplement.', '["Pack: 30 tablets","Type: Supplement"]', '[]', 0, null, 1, '2030-12-31 23:59:59', 1, 1, 1, current_timestamp, current_timestamp)
on duplicate key update
module_key = values(module_key),
zone_id = values(zone_id),
vendor_id = values(vendor_id),
brand_id = values(brand_id),
category_id = values(category_id),
name = values(name),
slug = values(slug),
description = values(description),
unit = values(unit),
price = values(price),
discount_price = values(discount_price),
stock = values(stock),
sku = values(sku),
tax_percent = values(tax_percent),
shipping_cost = values(shipping_cost),
barcode = values(barcode),
seo_title = values(seo_title),
seo_description = values(seo_description),
attributes_json = values(attributes_json),
colors_json = values(colors_json),
is_digital = values(is_digital),
digital_file_url = values(digital_file_url),
is_flash_deal = values(is_flash_deal),
flash_deal_ends_at = values(flash_deal_ends_at),
is_clearance = values(is_clearance),
status = values(status),
is_featured = values(is_featured),
updated_at = current_timestamp;

set @schema_name = database();
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'medicine_type') = 0, 'alter table products add column medicine_type varchar(255) null', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'schedule_tag') = 0, 'alter table products add column schedule_tag varchar(255) null', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'max_qty_per_order') = 0, 'alter table products add column max_qty_per_order int null', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'max_qty_per_month') = 0, 'alter table products add column max_qty_per_month int null', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'requires_pharmacist_review') = 0, 'alter table products add column requires_pharmacist_review tinyint(1) not null default 0', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'requires_age_confirmation') = 0, 'alter table products add column requires_age_confirmation tinyint(1) not null default 0', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;

update products set medicine_type = 'otc', max_qty_per_order = 6, max_qty_per_month = null, requires_pharmacist_review = 0, requires_age_confirmation = 0 where id in (501, 502, 503) and module_key = 'medical';

insert into product_variants
(id, product_id, name, unit, price, discount_price, stock, sku, status, created_at, updated_at) values
(1, 1, 'Small Pack', '500 g', 95.00, 85.00, 18, 'MART-APL-500G', 1, current_timestamp, current_timestamp),
(2, 1, 'Family Pack', '2 kg', 340.00, 310.00, 10, 'MART-APL-2KG', 1, current_timestamp, current_timestamp),
(3, 3, 'Single Pack', '500 ml', 36.00, null, 50, 'MART-MLK-500ML', 1, current_timestamp, current_timestamp),
(101, 101, 'Black', 'piece', 1499.00, 1299.00, 10, 'ECM-EAR-BLK', 1, current_timestamp, current_timestamp),
(102, 101, 'White', 'piece', 1499.00, 1299.00, 8, 'ECM-EAR-WHT', 1, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
unit = values(unit),
price = values(price),
discount_price = values(discount_price),
stock = values(stock),
sku = values(sku),
status = values(status),
updated_at = current_timestamp;

insert into banners
(id, module_key, title, image, link_type, link_value, status, sort_order, created_at, updated_at) values
(1, 'mart', 'Fresh groceries delivered fast', null, 'category', '1', 1, 1, current_timestamp, current_timestamp),
(2, 'mart', 'Daily essentials in your zone', null, 'category', '3', 1, 2, current_timestamp, current_timestamp),
(101, 'ecommerce', 'E-Commerce Launch Sale', null, 'category', '101', 1, 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 'Electronics Deals', null, 'category', '101', 1, 2, current_timestamp, current_timestamp),
(501, 'medical', 'Medicine delivery at home', null, 'category', '501', 1, 1, current_timestamp, current_timestamp),
(502, 'medical', 'Health essentials', null, 'category', '503', 1, 2, current_timestamp, current_timestamp)
on duplicate key update
module_key = values(module_key),
title = values(title),
link_type = values(link_type),
link_value = values(link_value),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into coupons
(id, module_key, code, title, discount_type, discount_value, minimum_order_amount, maximum_discount, usage_limit, used_count, starts_at, expires_at, status, created_at, updated_at) values
(1, 'mart', 'SAVE50', 'Demo flat saving', 'flat', 50.00, 199.00, null, 100, 0, current_date, '2030-12-31', 1, current_timestamp, current_timestamp),
(101, 'ecommerce', 'ECOM10', 'E-Commerce Demo Offer', 'percent', 10.00, 299.00, 150.00, 100, 0, current_date, '2030-12-31', 1, current_timestamp, current_timestamp),
(501, 'medical', 'MED10', 'Medical Demo Offer', 'percent', 10.00, 99.00, 100.00, 100, 0, current_date, '2030-12-31', 1, current_timestamp, current_timestamp)
on duplicate key update
module_key = values(module_key),
title = values(title),
discount_type = values(discount_type),
discount_value = values(discount_value),
minimum_order_amount = values(minimum_order_amount),
maximum_discount = values(maximum_discount),
usage_limit = values(usage_limit),
used_count = values(used_count),
starts_at = values(starts_at),
expires_at = values(expires_at),
status = values(status),
updated_at = current_timestamp;

insert into shipping_methods
(id, name, description, cost, expected_days, sort_order, status, created_at, updated_at) values
(1, 'Standard Delivery', 'Delivery in 1-2 days.', 30.00, '1-2 days', 1, 1, current_timestamp, current_timestamp),
(2, 'Express Delivery', 'Same day priority delivery where available.', 60.00, 'Same day', 2, 1, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
description = values(description),
cost = values(cost),
expected_days = values(expected_days),
sort_order = values(sort_order),
status = values(status),
updated_at = current_timestamp;

insert into orders
(id, module_key, zone_id, vendor_id, delivery_man_id, order_number, guest_id, customer_name, customer_phone, customer_email, address, order_amount, coupon_code, coupon_discount, shipping_method_id, shipping_method_name, shipping_cost, expected_delivery, payment_method, payment_status, order_status, delivery_assigned_at, order_note, created_at, updated_at) values
(1, 'mart', 1, 1, null, 'MART-DEMO-1001', 'demo-guest', 'Demo Customer', '9999999999', 'demo@example.com', 'Hazratganj, Lucknow, Uttar Pradesh 226001', 245.00, 'SAVE50', 50.00, 1, 'Standard Delivery', 30.00, '1-2 days', 'cash_on_delivery', 'unpaid', 'pending', null, 'Demo mart order.', current_timestamp, current_timestamp),
(101, 'ecommerce', 1, 101, null, 'ECOM-DEMO-1001', 'demo-guest', 'Demo Customer', '9999999999', 'demo@example.com', 'Hazratganj, Lucknow, Uttar Pradesh 226001', 1328.00, 'ECOM10', 150.00, 1, 'Standard Delivery', 49.00, '1-2 days', 'cash_on_delivery', 'unpaid', 'pending', null, 'Demo e-commerce order.', current_timestamp, current_timestamp),
(501, 'medical', 1, 501, null, 'MED-DEMO-1001', 'demo-guest', 'Demo Customer', '9999999999', 'demo@example.com', 'Hazratganj, Lucknow, Uttar Pradesh 226001', 149.00, null, 0.00, 1, 'Standard Delivery', 20.00, '1-2 days', 'cash_on_delivery', 'unpaid', 'pending', null, 'Demo medical order.', current_timestamp, current_timestamp)
on duplicate key update
module_key = values(module_key),
zone_id = values(zone_id),
vendor_id = values(vendor_id),
customer_name = values(customer_name),
customer_phone = values(customer_phone),
address = values(address),
order_amount = values(order_amount),
coupon_code = values(coupon_code),
coupon_discount = values(coupon_discount),
shipping_method_id = values(shipping_method_id),
shipping_method_name = values(shipping_method_name),
shipping_cost = values(shipping_cost),
expected_delivery = values(expected_delivery),
payment_method = values(payment_method),
payment_status = values(payment_status),
order_status = values(order_status),
order_note = values(order_note),
updated_at = current_timestamp;

insert into order_items
(id, order_id, vendor_id, product_id, variant_id, variant_name, product_name, quantity, price, total, status, created_at, updated_at) values
(1, 1, 1, 1, 1, 'Small Pack', 'Fresh Apple', 1, 85.00, 85.00, 'pending', current_timestamp, current_timestamp),
(2, 1, 2, 3, 3, 'Single Pack', 'Full Cream Milk', 2, 36.00, 72.00, 'pending', current_timestamp, current_timestamp),
(101, 101, 101, 101, 101, 'Black', 'Wireless Earbuds', 1, 1299.00, 1299.00, 'pending', current_timestamp, current_timestamp),
(501, 501, 501, 501, null, null, 'Paracetamol 500mg', 2, 30.00, 60.00, 'pending', current_timestamp, current_timestamp),
(502, 501, 501, 502, null, null, 'Cough Syrup', 1, 99.00, 99.00, 'pending', current_timestamp, current_timestamp)
on duplicate key update
vendor_id = values(vendor_id),
product_id = values(product_id),
variant_id = values(variant_id),
variant_name = values(variant_name),
product_name = values(product_name),
quantity = values(quantity),
price = values(price),
total = values(total),
status = values(status),
updated_at = current_timestamp;

insert into product_reviews
(id, product_id, vendor_id, guest_id, customer_name, rating, comment, reply, status, created_at, updated_at) values
(1, 1, 1, 'demo-guest', 'Demo Customer', 5, 'Fresh and nicely packed apples.', 'Thank you for your review.', 1, current_timestamp, current_timestamp),
(2, 101, 101, 'demo-guest', 'Demo Customer', 4, 'Earbuds are good for the price.', null, 1, current_timestamp, current_timestamp),
(3, 501, 501, 'demo-guest', 'Demo Customer', 5, 'Medicine delivered quickly.', null, 1, current_timestamp, current_timestamp)
on duplicate key update
rating = values(rating),
comment = values(comment),
reply = values(reply),
status = values(status),
updated_at = current_timestamp;

insert into medical_prescriptions
(id, order_id, guest_id, customer_name, customer_phone, reference, file_path, note, status, admin_note, reviewed_by, reviewed_at, created_at, updated_at) values
(1, 501, 'demo-guest', 'Demo Customer', '9999999999', 'RX-DEMO-1001', null, 'Demo prescription waiting for pharmacist/admin review.', 'pending', null, null, null, current_timestamp, current_timestamp)
on duplicate key update
order_id = values(order_id),
guest_id = values(guest_id),
customer_name = values(customer_name),
customer_phone = values(customer_phone),
reference = values(reference),
note = values(note),
status = values(status),
admin_note = values(admin_note),
updated_at = current_timestamp;

insert into refund_requests
(id, order_id, order_item_id, vendor_id, guest_id, customer_name, customer_phone, amount, reason, note, admin_note, status, created_at, updated_at) values
(1, 1, 2, 2, 'demo-guest', 'Demo Customer', '9999999999', 72.00, 'Item issue', 'Demo refund request for testing.', null, 'pending', current_timestamp, current_timestamp)
on duplicate key update
amount = values(amount),
reason = values(reason),
note = values(note),
status = values(status),
updated_at = current_timestamp;

insert into wallet_accounts
(id, owner_type, owner_key, balance, created_at, updated_at) values
(1, 'customer', 'customer-1', 72.00, current_timestamp, current_timestamp),
(2, 'vendor', '1', 250.00, current_timestamp, current_timestamp)
on duplicate key update
balance = values(balance),
updated_at = current_timestamp;

insert into wallet_ledgers
(id, wallet_account_id, owner_type, owner_key, direction, amount, entry_type, reference_key, description, created_at, updated_at) values
(1, 1, 'customer', 'customer-1', 'credit', 72.00, 'refund_credit', 'refund:1', 'Demo refund credited to wallet.', current_timestamp, current_timestamp),
(2, 2, 'vendor', '1', 'credit', 250.00, 'order_settlement', 'payment:1:vendor:1', 'Demo vendor settlement.', current_timestamp, current_timestamp)
on duplicate key update
amount = values(amount),
description = values(description),
updated_at = current_timestamp;

insert into hotel_categories
(id, name, description, status, sort_order, created_at, updated_at) values
(1, 'Business Hotels', 'Work-friendly stays with fast connectivity.', 1, 1, current_timestamp, current_timestamp),
(2, 'Family Hotels', 'Comfortable stays for families and groups.', 1, 2, current_timestamp, current_timestamp),
(3, 'Premium Stays', 'Upscale hotels with richer amenities.', 1, 3, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
description = values(description),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into hotel_owners
(id, name, phone, email, password, status, created_at, updated_at) values
(1, 'Hotel Demo Owner', '9876543210', 'hotelowner@example.com', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 1, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
phone = values(phone),
email = values(email),
password = values(password),
status = values(status),
updated_at = current_timestamp;

insert into hotels
(id, zone_id, owner_id, category_id, name, city, area, address, description, star_rating, rating, review_count, amenities_json, thumbnail, gallery_json, status, is_featured, created_at, updated_at) values
(1, 1, 1, 1, 'City Central Hotel', 'Lucknow', 'Hazratganj', 'Hazratganj, Lucknow', 'A central business hotel near shopping streets and offices.', 4.0, 4.5, 2, '["WiFi","Breakfast","Parking","Conference Room"]', null, '[]', 1, 1, current_timestamp, current_timestamp),
(2, 2, 1, 2, 'Gomti Family Inn', 'Lucknow', 'Gomti Nagar', 'Gomti Nagar, Lucknow', 'Family friendly hotel with spacious rooms and easy city access.', 3.5, 4.2, 1, '["WiFi","Restaurant","Lift","Room Service"]', null, '[]', 1, 1, current_timestamp, current_timestamp),
(3, 1, 1, 3, 'Royal Heritage Suites', 'Lucknow', 'Charbagh', 'Charbagh, Lucknow', 'Premium suite property for comfortable city stays.', 5.0, 4.8, 3, '["WiFi","Pool","Gym","Breakfast","Airport Pickup"]', null, '[]', 1, 0, current_timestamp, current_timestamp)
on duplicate key update
zone_id = values(zone_id),
owner_id = values(owner_id),
category_id = values(category_id),
name = values(name),
city = values(city),
area = values(area),
address = values(address),
description = values(description),
star_rating = values(star_rating),
rating = values(rating),
review_count = values(review_count),
amenities_json = values(amenities_json),
status = values(status),
is_featured = values(is_featured),
updated_at = current_timestamp;

insert into hotel_rooms
(id, hotel_id, name, description, capacity_adults, capacity_children, total_rooms, price_per_night, discount_price, tax_percent, amenities_json, thumbnail, status, created_at, updated_at) values
(1, 1, 'Deluxe King Room', 'King bed room with desk and city view.', 2, 1, 8, 3200.00, 2899.00, 12.00, '["King Bed","Air Conditioning","Work Desk","Tea Kit"]', null, 1, current_timestamp, current_timestamp),
(2, 1, 'Executive Twin Room', 'Twin room for business travellers.', 2, 0, 6, 3600.00, null, 12.00, '["Twin Beds","Air Conditioning","Work Desk"]', null, 1, current_timestamp, current_timestamp),
(3, 2, 'Family Room', 'Large family room with extra bedding.', 3, 2, 5, 2800.00, 2499.00, 12.00, '["Queen Bed","Extra Mattress","TV","Room Service"]', null, 1, current_timestamp, current_timestamp)
on duplicate key update
hotel_id = values(hotel_id),
name = values(name),
description = values(description),
capacity_adults = values(capacity_adults),
capacity_children = values(capacity_children),
total_rooms = values(total_rooms),
price_per_night = values(price_per_night),
discount_price = values(discount_price),
tax_percent = values(tax_percent),
amenities_json = values(amenities_json),
status = values(status),
updated_at = current_timestamp;

insert into hotel_reviews
(id, hotel_id, booking_id, guest_id, customer_name, rating, comment, status, created_at, updated_at) values
(1, 1, null, 'demo', 'Amit', 5, 'Clean room and quick check-in.', 1, current_timestamp, current_timestamp),
(2, 1, null, 'demo', 'Neha', 4, 'Good location and breakfast.', 1, current_timestamp, current_timestamp),
(3, 2, null, 'demo', 'Rahul', 4, 'Comfortable for family stay.', 1, current_timestamp, current_timestamp)
on duplicate key update
hotel_id = values(hotel_id),
customer_name = values(customer_name),
rating = values(rating),
comment = values(comment),
status = values(status),
updated_at = current_timestamp;

insert into hotel_bookings
(id, booking_number, zone_id, hotel_id, room_id, guest_id, customer_name, customer_phone, customer_email, check_in, check_out, nights, rooms, adults, children, room_total, tax_total, grand_total, payment_method, payment_reference, payment_note, payment_status, booking_status, cancellation_reason, refund_status, refund_amount, refund_note, admin_note, cancelled_at, completed_at, created_at, updated_at) values
(1, 'HTL-DEMO-1001', 1, 1, 1, 'demo-guest', 'Demo Customer', '9999999999', 'demo@example.com', date_add(current_date, interval 1 day), date_add(current_date, interval 2 day), 1, 1, 2, 0, 2899.00, 347.88, 3246.88, 'pay_at_hotel', null, null, 'unpaid', 'pending', null, 'none', 0.00, null, 'Demo hotel booking.', null, null, current_timestamp, current_timestamp)
on duplicate key update
zone_id = values(zone_id),
hotel_id = values(hotel_id),
room_id = values(room_id),
guest_id = values(guest_id),
customer_name = values(customer_name),
customer_phone = values(customer_phone),
customer_email = values(customer_email),
check_in = values(check_in),
check_out = values(check_out),
nights = values(nights),
rooms = values(rooms),
adults = values(adults),
children = values(children),
room_total = values(room_total),
tax_total = values(tax_total),
grand_total = values(grand_total),
payment_method = values(payment_method),
payment_status = values(payment_status),
booking_status = values(booking_status),
admin_note = values(admin_note),
updated_at = current_timestamp;

insert into hotel_payment_transactions
(id, booking_id, guest_id, customer_name, customer_phone, payment_method, amount, reference, note, status, gateway_response, reconciled_at, reconciled_by, created_at, updated_at) values
(1, 1, 'demo-guest', 'Demo Customer', '9999999999', 'pay_at_hotel', 3246.88, null, 'Demo hotel pay-at-hotel transaction.', 'pending', null, null, null, current_timestamp, current_timestamp)
on duplicate key update
booking_id = values(booking_id),
guest_id = values(guest_id),
customer_name = values(customer_name),
customer_phone = values(customer_phone),
payment_method = values(payment_method),
amount = values(amount),
note = values(note),
status = values(status),
updated_at = current_timestamp;

insert into service_categories
(id, name, description, icon, status, sort_order, created_at, updated_at) values
(1, 'Home Repair', 'Electrician, plumber and appliance repair services.', 'repair', 1, 1, current_timestamp, current_timestamp),
(2, 'Cleaning', 'Home cleaning and deep cleaning services.', 'cleaning', 1, 2, current_timestamp, current_timestamp),
(3, 'Beauty & Wellness', 'At-home salon and grooming services.', 'salon', 1, 3, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
description = values(description),
icon = values(icon),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into service_providers
(id, zone_id, name, phone, email, area, password, commission_percent, status, created_at, updated_at) values
(1, 1, 'Ravi Home Services', '9000000201', 'ravi.services@example.com', 'Lucknow Central', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 12.00, 1, current_timestamp, current_timestamp),
(2, 2, 'Sparkle Care Team', '9000000202', 'sparkle@example.com', 'Gomti Nagar', '$2y$12$/XmXUR2iGICCPocbg50EguOtExsKXxVeIzJsgkXgSd/QBGy/Afnim', 15.00, 1, current_timestamp, current_timestamp)
on duplicate key update
zone_id = values(zone_id),
name = values(name),
phone = values(phone),
email = values(email),
area = values(area),
commission_percent = values(commission_percent),
status = values(status),
updated_at = current_timestamp;

insert into services
(id, zone_id, category_id, provider_id, vendor_id, name, description, checklist_json, duration_minutes, warranty_days, price, discount_price, image, status, is_featured, created_at, updated_at) values
(1, 1, 1, 1, null, 'Electrician Visit', 'Switch, wiring and basic electrical repair visit.', '["Keep the main switch accessible","Share any known spark or outage details","Keep children away from the repair area"]', 60, 7, 299.00, 249.00, null, 1, 1, current_timestamp, current_timestamp),
(2, 1, 1, 1, null, 'Plumber Visit', 'Leak, tap and bathroom fitting inspection.', '["Clear access to the leak or fixture","Keep water supply valve reachable","Share photos if leakage is intermittent"]', 60, 7, 349.00, 299.00, null, 1, 1, current_timestamp, current_timestamp),
(3, 2, 2, 2, null, 'Home Deep Cleaning', 'Full home deep cleaning with trained staff.', '["Move fragile valuables before arrival","Keep running water available","Allow access to all rooms selected for cleaning"]', 180, 3, 1499.00, 1299.00, null, 1, 1, current_timestamp, current_timestamp),
(4, 2, 3, 2, null, 'Men Haircut At Home', 'Professional haircut service at home.', '["Keep a chair near good lighting","Keep a towel ready","Share preferred haircut style before service"]', 45, 0, 249.00, 199.00, null, 1, 0, current_timestamp, current_timestamp)
on duplicate key update
zone_id = values(zone_id),
category_id = values(category_id),
provider_id = values(provider_id),
name = values(name),
description = values(description),
checklist_json = values(checklist_json),
duration_minutes = values(duration_minutes),
warranty_days = values(warranty_days),
price = values(price),
discount_price = values(discount_price),
status = values(status),
is_featured = values(is_featured),
updated_at = current_timestamp;

insert into service_addons
(id, service_id, name, description, price, status, sort_order, created_at, updated_at) values
(1, 1, 'Extra switch replacement', 'One basic switch replacement labour.', 99.00, 1, 1, current_timestamp, current_timestamp),
(2, 2, 'Drain cleaning add-on', 'Basic drain cleaning add-on.', 149.00, 1, 1, current_timestamp, current_timestamp),
(3, 3, 'Kitchen degreasing', 'Extra kitchen degreasing service.', 299.00, 1, 1, current_timestamp, current_timestamp),
(4, 4, 'Beard trim', 'Add beard trim to haircut.', 99.00, 1, 1, current_timestamp, current_timestamp)
on duplicate key update
service_id = values(service_id),
name = values(name),
description = values(description),
price = values(price),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into service_slots
(id, service_id, provider_id, day_of_week, start_time, end_time, capacity, status, created_at, updated_at) values
(1, 1, 1, 1, '10:00', '12:00', 3, 1, current_timestamp, current_timestamp),
(2, 1, 1, 2, '15:00', '17:00', 3, 1, current_timestamp, current_timestamp),
(3, 2, 1, 3, '10:00', '12:00', 3, 1, current_timestamp, current_timestamp),
(4, 3, 2, 5, '09:00', '12:00', 2, 1, current_timestamp, current_timestamp),
(5, 4, 2, 6, '16:00', '17:00', 4, 1, current_timestamp, current_timestamp)
on duplicate key update
service_id = values(service_id),
provider_id = values(provider_id),
day_of_week = values(day_of_week),
start_time = values(start_time),
end_time = values(end_time),
capacity = values(capacity),
status = values(status),
updated_at = current_timestamp;

insert into service_bookings
(id, booking_number, zone_id, service_id, provider_id, slot_id, guest_id, customer_name, customer_phone, customer_email, address, preferred_date, preferred_time, addons_json, addon_total, amount, payment_method, payment_status, booking_status, admin_note, note, created_at, updated_at) values
(1, 'CSV-DEMO-1001', 1, 1, 1, 1, 'demo-customer', 'Demo Customer', '9999999999', 'customer@example.com', 'Hazratganj, Lucknow, Uttar Pradesh 226001', date_add(current_date, interval 1 day), '10:00 - 12:00', '[{"id":1,"name":"Extra switch replacement","price":99}]', 99.00, 348.00, 'cash_on_service', 'unpaid', 'pending', 'Demo pending booking', 'Please call before visit', current_timestamp, current_timestamp),
(2, 'CSV-DEMO-1002', 2, 3, 2, 4, 'demo-customer', 'Demo Customer', '9999999999', 'customer@example.com', 'Gomti Nagar, Lucknow, Uttar Pradesh 226010', date_add(current_date, interval 2 day), '09:00 - 12:00', '[]', 0.00, 1299.00, 'cash_on_service', 'unpaid', 'accepted', 'Demo accepted booking', null, current_timestamp, current_timestamp)
on duplicate key update
zone_id = values(zone_id),
service_id = values(service_id),
provider_id = values(provider_id),
slot_id = values(slot_id),
customer_name = values(customer_name),
customer_phone = values(customer_phone),
address = values(address),
preferred_date = values(preferred_date),
preferred_time = values(preferred_time),
addons_json = values(addons_json),
addon_total = values(addon_total),
amount = values(amount),
payment_method = values(payment_method),
payment_status = values(payment_status),
booking_status = values(booking_status),
admin_note = values(admin_note),
note = values(note),
updated_at = current_timestamp;

insert into settings (key_name, value, updated_at) values
('app_name', 'City Solutions', current_timestamp),
('currency', 'INR', current_timestamp),
('currency_symbol', '₹', current_timestamp),
('minimum_order_amount', '0', current_timestamp),
('cod_enabled', '1', current_timestamp),
('online_payment_enabled', '1', current_timestamp),
('manual_payment_enabled', '1', current_timestamp),
('mart.app_name', 'City Mart', current_timestamp),
('ecommerce.app_name', 'City E-Commerce', current_timestamp),
('medical.app_name', 'City Medical', current_timestamp),
('services.app_name', 'City Services', current_timestamp),
('hotels.app_name', 'City Hotels', current_timestamp),
('floating_ad_enabled', '1', current_timestamp),
('floating_ad_id', 'demo-ad-1', current_timestamp),
('floating_ad_title', 'Demo Offer', current_timestamp),
('floating_ad_message', 'This floating ad is controlled from Admin Settings.', current_timestamp),
('floating_ad_image_url', '', current_timestamp),
('floating_ad_video_url', '', current_timestamp),
('floating_ad_cta_text', 'Explore', current_timestamp),
('floating_ad_link_url', '', current_timestamp)
on duplicate key update
value = values(value),
updated_at = current_timestamp;

set foreign_key_checks = 1;
