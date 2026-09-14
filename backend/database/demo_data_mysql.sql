create table if not exists brands (
    id bigint unsigned primary key auto_increment,
    name varchar(190) not null,
    slug varchar(220) not null,
    image varchar(255) null,
    status tinyint(1) not null default 1,
    sort_order int not null default 0,
    created_at timestamp null,
    updated_at timestamp null
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists vendors (
    id bigint unsigned primary key auto_increment,
    shop_name varchar(190) not null,
    owner_name varchar(190) not null,
    phone varchar(60) not null unique,
    email varchar(190) null,
    password varchar(255) not null,
    auth_token varchar(128) null,
    address text null,
    city varchar(120) null,
    status varchar(40) not null default 'pending',
    admin_note text null,
    created_at timestamp null,
    updated_at timestamp null
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists delivery_men (
    id bigint unsigned primary key auto_increment,
    name varchar(190) not null,
    phone varchar(60) not null,
    email varchar(190) null,
    vehicle_type varchar(80) null,
    vehicle_number varchar(80) null,
    status tinyint(1) not null default 1,
    created_at timestamp null,
    updated_at timestamp null
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists coupons (
    id bigint unsigned primary key auto_increment,
    code varchar(80) not null unique,
    title varchar(190) not null,
    discount_type varchar(20) not null default 'flat',
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
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists order_status_history (
    id bigint unsigned primary key auto_increment,
    order_id bigint unsigned not null,
    order_item_id bigint unsigned null,
    status varchar(60) not null,
    actor_type varchar(40) not null,
    actor_name varchar(190) null,
    note text null,
    created_at timestamp null,
    index order_status_history_order_id_index (order_id)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists product_images (
    id bigint unsigned primary key auto_increment,
    product_id bigint unsigned not null,
    image varchar(255) not null,
    sort_order int not null default 0,
    created_at timestamp null,
    index product_images_product_id_index (product_id)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists product_variants (
    id bigint unsigned primary key auto_increment,
    product_id bigint unsigned not null,
    name varchar(190) not null,
    unit varchar(60) not null default 'piece',
    price decimal(12,2) not null default 0,
    discount_price decimal(12,2) null,
    stock int not null default 0,
    sku varchar(120) null,
    status tinyint(1) not null default 1,
    created_at timestamp null,
    updated_at timestamp null,
    index product_variants_product_id_index (product_id)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists notifications (
    id bigint unsigned primary key auto_increment,
    recipient_type varchar(40) not null,
    recipient_id bigint unsigned null,
    guest_id varchar(120) null,
    title varchar(190) not null,
    message text null,
    order_id bigint unsigned null,
    read_at timestamp null,
    created_at timestamp null,
    index notifications_recipient_index (recipient_type, recipient_id),
    index notifications_guest_id_index (guest_id)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists wishlists (
    id bigint unsigned primary key auto_increment,
    guest_id varchar(120) not null,
    product_id bigint unsigned not null,
    created_at timestamp null,
    unique key wishlists_guest_product_unique (guest_id, product_id),
    index wishlists_guest_id_index (guest_id)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists product_reviews (
    id bigint unsigned primary key auto_increment,
    product_id bigint unsigned not null,
    vendor_id bigint unsigned null,
    guest_id varchar(120) not null,
    customer_name varchar(190) null,
    rating tinyint unsigned not null default 5,
    comment text null,
    reply text null,
    status tinyint(1) not null default 1,
    created_at timestamp null,
    updated_at timestamp null,
    index product_reviews_product_id_index (product_id),
    index product_reviews_vendor_id_index (vendor_id)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists refund_requests (
    id bigint unsigned primary key auto_increment,
    order_id bigint unsigned not null,
    order_item_id bigint unsigned null,
    vendor_id bigint unsigned null,
    guest_id varchar(120) null,
    customer_name varchar(190) null,
    customer_phone varchar(60) null,
    amount decimal(12,2) not null default 0,
    reason varchar(255) not null,
    note text null,
    admin_note text null,
    status varchar(40) not null default 'pending',
    created_at timestamp null,
    updated_at timestamp null,
    index refund_requests_order_id_index (order_id),
    index refund_requests_vendor_id_index (vendor_id)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

set @schema_name = database();
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'vendor_id') = 0, 'alter table products add column vendor_id bigint unsigned null after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'brand_id') = 0, 'alter table products add column brand_id bigint unsigned null after vendor_id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'orders' and column_name = 'vendor_id') = 0, 'alter table orders add column vendor_id bigint unsigned null after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'orders' and column_name = 'delivery_man_id') = 0, 'alter table orders add column delivery_man_id bigint unsigned null after vendor_id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'orders' and column_name = 'coupon_code') = 0, 'alter table orders add column coupon_code varchar(80) null after order_amount', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'orders' and column_name = 'coupon_discount') = 0, 'alter table orders add column coupon_discount decimal(12,2) not null default 0 after coupon_code', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'orders' and column_name = 'delivery_assigned_at') = 0, 'alter table orders add column delivery_assigned_at timestamp null after order_status', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'order_items' and column_name = 'vendor_id') = 0, 'alter table order_items add column vendor_id bigint unsigned null after order_id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'order_items' and column_name = 'status') = 0, 'alter table order_items add column status varchar(60) not null default ''pending'' after total', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'carts' and column_name = 'variant_id') = 0, 'alter table carts add column variant_id bigint unsigned null after product_id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'order_items' and column_name = 'variant_id') = 0, 'alter table order_items add column variant_id bigint unsigned null after product_id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'order_items' and column_name = 'variant_name') = 0, 'alter table order_items add column variant_name varchar(190) null after variant_id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'tax_percent') = 0, 'alter table products add column tax_percent decimal(8,2) not null default 0 after thumbnail', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'barcode') = 0, 'alter table products add column barcode varchar(255) null after tax_percent', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'seo_title') = 0, 'alter table products add column seo_title varchar(255) null after barcode', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'seo_description') = 0, 'alter table products add column seo_description text null after seo_title', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'attributes_json') = 0, 'alter table products add column attributes_json text null after seo_description', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'colors_json') = 0, 'alter table products add column colors_json text null after attributes_json', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'is_digital') = 0, 'alter table products add column is_digital tinyint(1) not null default 0 after colors_json', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'digital_file_url') = 0, 'alter table products add column digital_file_url text null after is_digital', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'is_flash_deal') = 0, 'alter table products add column is_flash_deal tinyint(1) not null default 0 after digital_file_url', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'flash_deal_ends_at') = 0, 'alter table products add column flash_deal_ends_at datetime null after is_flash_deal', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'is_clearance') = 0, 'alter table products add column is_clearance tinyint(1) not null default 0 after flash_deal_ends_at', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;

insert into categories (id, name, slug, image, status, sort_order, created_at, updated_at) values
(1, 'Fruits & Vegetables', 'fruits-vegetables', null, 1, 1, current_timestamp, current_timestamp),
(2, 'Dairy & Bakery', 'dairy-bakery', null, 1, 2, current_timestamp, current_timestamp),
(3, 'Grocery Staples', 'grocery-staples', null, 1, 3, current_timestamp, current_timestamp),
(4, 'Snacks & Beverages', 'snacks-beverages', null, 1, 4, current_timestamp, current_timestamp),
(5, 'Household Care', 'household-care', null, 1, 5, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
slug = values(slug),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into brands (id, name, slug, image, status, sort_order, created_at, updated_at) values
(1, 'FreshKart', 'freshkart', null, 1, 1, current_timestamp, current_timestamp),
(2, 'Daily Dairy', 'daily-dairy', null, 1, 2, current_timestamp, current_timestamp),
(3, 'Grain House', 'grain-house', null, 1, 3, current_timestamp, current_timestamp),
(4, 'SnackJoy', 'snackjoy', null, 1, 4, current_timestamp, current_timestamp),
(5, 'HomeCare Plus', 'homecare-plus', null, 1, 5, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
slug = values(slug),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into vendors
(id, shop_name, owner_name, phone, email, password, address, city, status, admin_note, created_at, updated_at) values
(1, 'Lucknow Fresh Store', 'Amit Verma', '9000000001', 'freshstore@example.com', '$2y$12$sHgFAKVyfuxPc2alTSRHjuSRK9.xAVAGk.Rg.D7QZBPVXVjmXcTFu', 'Gomti Nagar Market', 'Lucknow', 'approved', 'Demo approved vendor.', current_timestamp, current_timestamp),
(2, 'Daily Dairy Hub', 'Neha Singh', '9000000002', 'dairyhub@example.com', '$2y$12$sHgFAKVyfuxPc2alTSRHjuSRK9.xAVAGk.Rg.D7QZBPVXVjmXcTFu', 'Aliganj Main Road', 'Lucknow', 'approved', 'Demo approved vendor.', current_timestamp, current_timestamp),
(3, 'Evening Snacks Point', 'Rahul Mishra', '9000000003', 'snackspoint@example.com', '$2y$12$sHgFAKVyfuxPc2alTSRHjuSRK9.xAVAGk.Rg.D7QZBPVXVjmXcTFu', 'Hazratganj', 'Lucknow', 'approved', 'Demo approved vendor.', current_timestamp, current_timestamp)
on duplicate key update
shop_name = values(shop_name),
owner_name = values(owner_name),
email = values(email),
address = values(address),
city = values(city),
status = values(status),
admin_note = values(admin_note),
updated_at = current_timestamp;

insert into delivery_men
(id, name, phone, email, vehicle_type, vehicle_number, status, created_at, updated_at) values
(1, 'Ravi Delivery', '9111111111', 'ravi.delivery@example.com', 'Bike', 'UP32 AB 1234', 1, current_timestamp, current_timestamp),
(2, 'Sana Express', '9222222222', 'sana.express@example.com', 'Scooter', 'UP32 CD 5678', 1, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
phone = values(phone),
email = values(email),
vehicle_type = values(vehicle_type),
vehicle_number = values(vehicle_number),
status = values(status),
updated_at = current_timestamp;

insert into products
(id, vendor_id, brand_id, category_id, name, slug, description, unit, price, discount_price, stock, sku, thumbnail, tax_percent, barcode, seo_title, seo_description, attributes_json, colors_json, is_digital, digital_file_url, is_flash_deal, flash_deal_ends_at, is_clearance, status, is_featured, created_at, updated_at) values
(1, 1, 1, 1, 'Fresh Apple', 'fresh-apple', 'Crisp seasonal apples for daily fruit bowls and snacks.', '1 kg', 180.00, 160.00, 25, 'CSM-APL-001', null, 5.00, '890100000001', 'Fresh Apple 1 kg', 'Seasonal apples with crisp texture and sweet taste.', '["Origin: Himachal","Storage: Cool place"]', '["Red","Green"]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(2, 1, 1, 1, 'Banana Robusta', 'banana-robusta', 'Naturally sweet bananas, ideal for breakfast and smoothies.', '1 dozen', 72.00, 65.00, 40, 'CSM-BAN-001', null, 5.00, '890100000002', 'Banana Robusta', 'Breakfast-friendly robusta bananas.', '["Ripeness: Medium","Use: Smoothies"]', '["Yellow"]', 0, null, 1, '2030-12-31 23:59:59', 1, 1, 1, current_timestamp, current_timestamp),
(3, 2, 2, 2, 'Full Cream Milk', 'full-cream-milk', 'Fresh full cream milk pack for tea, coffee, and cooking.', '1 litre', 68.00, null, 60, 'CSM-MLK-001', null, 5.00, '890100000003', 'Full Cream Milk 1 litre', 'Fresh dairy milk for daily use.', '["Type: Full Cream","Pack: 1 litre"]', '["White"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(4, 2, 2, 2, 'Brown Bread', 'brown-bread', 'Soft brown bread loaf for sandwiches and toast.', '400 g', 55.00, 49.00, 30, 'CSM-BRD-001', null, 5.00, '890100000004', 'Brown Bread 400 g', 'Soft bread loaf for breakfast.', '["Slices: Standard","Type: Bakery"]', '["Brown"]', 0, null, 0, null, 1, 1, 0, current_timestamp, current_timestamp),
(5, null, 3, 3, 'Basmati Rice', 'basmati-rice', 'Long grain basmati rice with rich aroma.', '5 kg', 620.00, 590.00, 18, 'CSM-RCE-001', null, 5.00, '890100000005', 'Basmati Rice 5 kg', 'Aromatic rice for daily and festive meals.', '["Grain: Long","Pack: 5 kg"]', '["Cream"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(6, null, 3, 3, 'Toor Dal', 'toor-dal', 'Premium quality toor dal for everyday meals.', '1 kg', 165.00, 149.00, 22, 'CSM-DAL-001', null, 5.00, '890100000006', 'Toor Dal 1 kg', 'Protein rich toor dal.', '["Type: Pulses","Pack: 1 kg"]', '["Yellow"]', 0, null, 0, null, 1, 1, 0, current_timestamp, current_timestamp),
(7, 3, 4, 4, 'Potato Chips', 'potato-chips', 'Classic salted potato chips for quick snacking.', '150 g', 50.00, 45.00, 80, 'CSM-CHP-001', null, 12.00, '890100000007', 'Potato Chips 150 g', 'Salted chips for evening snacks.', '["Flavor: Salted","Pack: 150 g"]', '["Gold"]', 0, null, 0, null, 1, 1, 1, current_timestamp, current_timestamp),
(8, 3, 4, 4, 'Cold Drink', 'cold-drink', 'Refreshing chilled beverage bottle.', '750 ml', 45.00, null, 75, 'CSM-DRK-001', null, 12.00, '890100000008', 'Cold Drink 750 ml', 'Refreshing beverage bottle.', '["Volume: 750 ml","Serve: Chilled"]', '["Blue"]', 0, null, 0, null, 0, 1, 0, current_timestamp, current_timestamp),
(9, null, 5, 5, 'Dishwash Liquid', 'dishwash-liquid', 'Powerful dishwash liquid for clean utensils.', '500 ml', 120.00, 99.00, 35, 'CSM-DWL-001', null, 18.00, '890100000009', 'Dishwash Liquid 500 ml', 'Strong cleaning liquid for kitchen utensils.', '["Use: Kitchen","Pack: 500 ml"]', '["Green"]', 0, null, 0, null, 1, 1, 0, current_timestamp, current_timestamp),
(10, null, 5, 5, 'Laundry Detergent', 'laundry-detergent', 'Fresh fragrance detergent for daily laundry.', '1 kg', 210.00, 185.00, 28, 'CSM-LND-001', null, 18.00, '890100000010', 'Laundry Detergent 1 kg', 'Detergent powder for regular washing.', '["Use: Laundry","Pack: 1 kg"]', '["Blue"]', 0, null, 0, null, 1, 1, 0, current_timestamp, current_timestamp)
on duplicate key update
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
status = values(status),
is_featured = values(is_featured),
updated_at = current_timestamp;

insert into coupons
(id, code, title, discount_type, discount_value, minimum_order_amount, maximum_discount, usage_limit, used_count, starts_at, expires_at, status, created_at, updated_at) values
(1, 'SAVE50', 'Demo flat saving', 'flat', 50.00, 199.00, null, 100, 0, null, null, 1, current_timestamp, current_timestamp),
(2, 'MART10', 'Demo 10 percent off', 'percent', 10.00, 299.00, 100.00, 100, 0, null, null, 1, current_timestamp, current_timestamp)
on duplicate key update
title = values(title),
discount_type = values(discount_type),
discount_value = values(discount_value),
minimum_order_amount = values(minimum_order_amount),
maximum_discount = values(maximum_discount),
usage_limit = values(usage_limit),
status = values(status),
updated_at = current_timestamp;

insert into product_variants
(id, product_id, name, unit, price, discount_price, stock, sku, status, created_at, updated_at) values
(1, 1, 'Small Pack', '500 g', 95.00, 85.00, 18, 'CSM-APL-500G', 1, current_timestamp, current_timestamp),
(2, 1, 'Family Pack', '2 kg', 340.00, 310.00, 10, 'CSM-APL-2KG', 1, current_timestamp, current_timestamp),
(3, 3, 'Single Pack', '500 ml', 36.00, null, 50, 'CSM-MLK-500ML', 1, current_timestamp, current_timestamp),
(4, 3, 'Daily Pack', '1 litre', 68.00, null, 60, 'CSM-MLK-1L', 1, current_timestamp, current_timestamp),
(5, 7, 'Classic Salted', '150 g', 50.00, 45.00, 80, 'CSM-CHP-SALT', 1, current_timestamp, current_timestamp),
(6, 7, 'Masala Crunch', '150 g', 55.00, 49.00, 70, 'CSM-CHP-MASALA', 1, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
unit = values(unit),
price = values(price),
discount_price = values(discount_price),
stock = values(stock),
sku = values(sku),
status = values(status),
updated_at = current_timestamp;

insert into banners (id, title, image, link_type, link_value, status, sort_order, created_at, updated_at) values
(1, 'Fresh groceries delivered fast', null, 'category', '1', 1, 1, current_timestamp, current_timestamp),
(2, 'Save more on daily essentials', null, 'category', '3', 1, 2, current_timestamp, current_timestamp),
(3, 'Snacks and beverages for every evening', null, 'category', '4', 1, 3, current_timestamp, current_timestamp)
on duplicate key update
title = values(title),
link_type = values(link_type),
link_value = values(link_value),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into orders
(id, vendor_id, delivery_man_id, order_number, guest_id, customer_name, customer_phone, customer_email, address, order_amount, coupon_code, coupon_discount, payment_method, payment_status, order_status, delivery_assigned_at, order_note, created_at, updated_at) values
(1, null, 1, 'CSM-DEMO-1001', 'demo-guest', 'Demo Customer', '9999999999', 'demo@example.com', 'Lucknow, India', 310.00, 'SAVE50', 50.00, 'cash_on_delivery', 'unpaid', 'pending', current_timestamp, 'Demo order for admin panel testing.', current_timestamp, current_timestamp)
on duplicate key update
delivery_man_id = values(delivery_man_id),
customer_name = values(customer_name),
customer_phone = values(customer_phone),
address = values(address),
order_amount = values(order_amount),
coupon_code = values(coupon_code),
coupon_discount = values(coupon_discount),
order_status = values(order_status),
delivery_assigned_at = values(delivery_assigned_at),
updated_at = current_timestamp;

insert into order_items
(id, order_id, vendor_id, product_id, variant_id, variant_name, product_name, quantity, price, total, status, created_at, updated_at) values
(1, 1, 1, 1, 1, 'Small Pack', 'Fresh Apple', 1, 85.00, 85.00, 'pending', current_timestamp, current_timestamp),
(2, 1, 2, 3, 4, 'Daily Pack', 'Full Cream Milk', 2, 68.00, 136.00, 'confirmed', current_timestamp, current_timestamp),
(3, 1, 3, 7, 5, 'Classic Salted', 'Potato Chips', 2, 45.00, 90.00, 'processing', current_timestamp, current_timestamp),
(4, 1, 2, 4, null, null, 'Brown Bread', 1, 49.00, 49.00, 'pending', current_timestamp, current_timestamp)
on duplicate key update
vendor_id = values(vendor_id),
variant_id = values(variant_id),
variant_name = values(variant_name),
quantity = values(quantity),
price = values(price),
total = values(total),
status = values(status),
updated_at = current_timestamp;

insert into order_status_history
(id, order_id, order_item_id, status, actor_type, actor_name, note, created_at) values
(1, 1, null, 'pending', 'customer', 'Demo Customer', 'Order placed', current_timestamp),
(2, 1, 1, 'pending', 'customer', 'Demo Customer', 'Fresh Apple placed', current_timestamp),
(3, 1, 2, 'confirmed', 'vendor', 'Daily Dairy Hub', 'Full Cream Milk confirmed', current_timestamp),
(4, 1, 3, 'processing', 'vendor', 'Evening Snacks Point', 'Potato Chips processing', current_timestamp)
on duplicate key update
status = values(status),
actor_type = values(actor_type),
actor_name = values(actor_name),
note = values(note);

insert into notifications
(id, recipient_type, recipient_id, guest_id, title, message, order_id, read_at, created_at) values
(1, 'admin', null, null, 'New mart order', 'CSM-DEMO-1001 placed by Demo Customer', 1, null, current_timestamp),
(2, 'customer', null, 'demo-guest', 'Order placed', 'Your order CSM-DEMO-1001 was placed successfully.', 1, null, current_timestamp),
(3, 'vendor', 1, null, 'New order item', 'Fresh Apple was ordered in CSM-DEMO-1001', 1, null, current_timestamp)
on duplicate key update
title = values(title),
message = values(message),
order_id = values(order_id),
created_at = values(created_at);

insert into wishlists
(id, guest_id, product_id, created_at) values
(1, 'demo-guest', 1, current_timestamp),
(2, 'demo-guest', 3, current_timestamp),
(3, 'demo-guest', 7, current_timestamp)
on duplicate key update
product_id = values(product_id),
created_at = values(created_at);

insert into product_reviews
(id, product_id, vendor_id, guest_id, customer_name, rating, comment, reply, status, created_at, updated_at) values
(1, 1, 1, 'demo-guest', 'Demo Customer', 5, 'Fresh and nicely packed apples.', 'Thank you for your review.', 1, current_timestamp, current_timestamp),
(2, 3, 2, 'demo-guest', 'Demo Customer', 4, 'Milk quality was good and delivered cold.', null, 1, current_timestamp, current_timestamp),
(3, 7, 3, 'demo-guest', 'Demo Customer', 5, 'Crispy chips, good evening snack.', null, 1, current_timestamp, current_timestamp)
on duplicate key update
rating = values(rating),
comment = values(comment),
reply = values(reply),
status = values(status),
updated_at = current_timestamp;

insert into refund_requests
(id, order_id, order_item_id, vendor_id, guest_id, customer_name, customer_phone, amount, reason, note, admin_note, status, created_at, updated_at) values
(1, 1, 3, 3, 'demo-guest', 'Demo Customer', '9999999999', 90.00, 'Item issue', 'Demo refund request for testing.', null, 'pending', current_timestamp, current_timestamp)
on duplicate key update
amount = values(amount),
reason = values(reason),
note = values(note),
status = values(status),
updated_at = current_timestamp;

insert into payment_transactions
(id, order_id, guest_id, customer_name, customer_phone, payment_method, amount, reference, note, status, admin_note, reconciled_at, reconciled_by, created_at, updated_at) values
(1, 1, 'demo-guest', 'Demo Customer', '9999999999', 'cash_on_delivery', 310.00, null, 'Demo COD payment entry.', 'pending', null, null, null, current_timestamp, current_timestamp)
on duplicate key update
payment_method = values(payment_method),
amount = values(amount),
reference = values(reference),
note = values(note),
status = values(status),
admin_note = values(admin_note),
updated_at = current_timestamp;

insert into wallet_accounts
(id, owner_type, owner_key, balance, created_at, updated_at) values
(1, 'customer', 'customer-1', 90.00, current_timestamp, current_timestamp),
(2, 'vendor', '1', 250.00, current_timestamp, current_timestamp)
on duplicate key update
balance = values(balance),
updated_at = current_timestamp;

insert into wallet_ledgers
(id, wallet_account_id, owner_type, owner_key, direction, amount, entry_type, reference_key, description, created_at, updated_at) values
(1, 1, 'customer', 'customer-1', 'credit', 90.00, 'refund_credit', 'refund:1', 'Refund credited to wallet for CSM-DEMO-1001', current_timestamp, current_timestamp),
(2, 2, 'vendor', '1', 'credit', 250.00, 'order_settlement', 'payment:1:vendor:1', 'Settlement for order CSM-DEMO-1001', current_timestamp, current_timestamp)
on duplicate key update
amount = values(amount),
description = values(description),
updated_at = current_timestamp;

insert into withdrawal_requests
(id, vendor_id, amount, bank_details, note, status, admin_note, created_at, updated_at) values
(1, 1, 120.00, 'Demo Bank, A/C 1234567890, IFSC DEMO0001', 'Demo withdrawal request.', 'pending', null, current_timestamp, current_timestamp)
on duplicate key update
amount = values(amount),
bank_details = values(bank_details),
note = values(note),
status = values(status),
updated_at = current_timestamp;

insert into settings (key_name, value, updated_at) values
('app_name', 'City Solutions Mart', current_timestamp),
('currency', 'INR', current_timestamp),
('currency_symbol', '₹', current_timestamp),
('minimum_order_amount', '0', current_timestamp),
('delivery_charge', '0', current_timestamp),
('cod_enabled', '1', current_timestamp),
('online_payment_enabled', '1', current_timestamp),
('manual_payment_enabled', '1', current_timestamp),
('vendor_commission_percent', '10', current_timestamp)
on duplicate key update
value = values(value),
updated_at = current_timestamp;
