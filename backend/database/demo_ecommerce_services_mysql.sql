set @schema_name = database();

set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'categories' and column_name = 'module_key') = 0, 'alter table categories add column module_key varchar(40) not null default ''mart'' after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'categories' and column_name = 'shipping_cost') = 0, 'alter table categories add column shipping_cost decimal(12,2) not null default 0 after image', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'brands' and column_name = 'module_key') = 0, 'alter table brands add column module_key varchar(40) not null default ''mart'' after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'banners' and column_name = 'module_key') = 0, 'alter table banners add column module_key varchar(40) not null default ''mart'' after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'coupons' and column_name = 'module_key') = 0, 'alter table coupons add column module_key varchar(40) not null default ''mart'' after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'module_key') = 0, 'alter table products add column module_key varchar(40) not null default ''mart'' after id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'vendor_id') = 0, 'alter table products add column vendor_id bigint unsigned null after module_key', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'brand_id') = 0, 'alter table products add column brand_id bigint unsigned null after vendor_id', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'tax_percent') = 0, 'alter table products add column tax_percent decimal(8,2) not null default 0 after thumbnail', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'shipping_cost') = 0, 'alter table products add column shipping_cost decimal(12,2) not null default 0 after tax_percent', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'products' and column_name = 'barcode') = 0, 'alter table products add column barcode varchar(255) null after shipping_cost', 'select 1');
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
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'vendors' and column_name = 'admin_note') = 0, 'alter table vendors add column admin_note text null after status', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'services' and column_name = 'warranty_days') = 0, 'alter table services add column warranty_days int not null default 0 after duration_minutes', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'services' and column_name = 'checklist_json') = 0, 'alter table services add column checklist_json text null after description', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_bookings' and column_name = 'warranty_days') = 0, 'alter table service_bookings add column warranty_days int not null default 0 after completed_at', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'service_bookings' and column_name = 'warranty_until') = 0, 'alter table service_bookings add column warranty_until date null after warranty_days', 'select 1');
prepare stmt from @sql; execute stmt; deallocate prepare stmt;

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

insert into categories (id, module_key, name, slug, image, shipping_cost, status, sort_order, created_at, updated_at) values
(101, 'ecommerce', 'Mobiles & Accessories', 'mobiles-accessories', null, 49.00, 1, 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 'Fashion', 'fashion', null, 39.00, 1, 2, current_timestamp, current_timestamp),
(103, 'ecommerce', 'Home Appliances', 'home-appliances', null, 79.00, 1, 3, current_timestamp, current_timestamp),
(104, 'ecommerce', 'Digital Products', 'digital-products', null, 0.00, 1, 4, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), name = values(name), slug = values(slug), shipping_cost = values(shipping_cost), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into brands (id, module_key, name, slug, image, status, sort_order, created_at, updated_at) values
(101, 'ecommerce', 'UrbanTech', 'urbantech', null, 1, 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 'DailyWear', 'dailywear', null, 1, 2, current_timestamp, current_timestamp),
(103, 'ecommerce', 'HomePro', 'homepro', null, 1, 3, current_timestamp, current_timestamp),
(104, 'ecommerce', 'LearnBox', 'learnbox', null, 1, 4, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), name = values(name), slug = values(slug), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into vendors (id, shop_name, owner_name, phone, email, password, address, city, status, admin_note, created_at, updated_at) values
(101, 'UrbanTech Store', 'Amit Verma', '9000000101', 'urbantech@example.com', '$2y$12$dbdbykLbLBHoQKEJlqFvnuNr0/mIHLvhbqVHHvPVWx1CkGO6vf5hK', 'Hazratganj, Lucknow', 'Lucknow', 'approved', 'Demo e-commerce vendor', current_timestamp, current_timestamp),
(102, 'City Lifestyle Hub', 'Neha Singh', '9000000102', 'lifestyle@example.com', '$2y$12$dbdbykLbLBHoQKEJlqFvnuNr0/mIHLvhbqVHHvPVWx1CkGO6vf5hK', 'Gomti Nagar, Lucknow', 'Lucknow', 'approved', 'Demo e-commerce vendor', current_timestamp, current_timestamp)
on duplicate key update shop_name = values(shop_name), owner_name = values(owner_name), email = values(email), address = values(address), city = values(city), status = values(status), updated_at = current_timestamp;

insert into products
(id, module_key, vendor_id, brand_id, category_id, name, slug, description, unit, price, discount_price, stock, sku, thumbnail, tax_percent, shipping_cost, barcode, seo_title, seo_description, attributes_json, colors_json, is_digital, digital_file_url, is_flash_deal, flash_deal_ends_at, is_clearance, status, is_featured, created_at, updated_at) values
(101, 'ecommerce', 101, 101, 101, 'Wireless Earbuds', 'wireless-earbuds', 'Bluetooth earbuds with charging case and clear calling.', 'piece', 1499.00, 1299.00, 18, 'ECM-EAR-101', null, 18.00, 49.00, '890200000101', 'Wireless Earbuds', 'Compact earbuds with long battery backup.', '["Connectivity: Bluetooth","Battery: 24 hours"]', '["Black","White"]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 101, 101, 101, 'Fast Charging Cable', 'fast-charging-cable', 'Durable type-C cable for fast charging and data transfer.', 'piece', 299.00, 249.00, 75, 'ECM-CBL-102', null, 18.00, 29.00, '890200000102', 'Fast Charging Cable', 'Durable fast charging type-C cable.', '["Length: 1 metre","Connector: Type-C"]', '["White"]', 0, null, 0, null, 1, 1, 1, current_timestamp, current_timestamp),
(103, 'ecommerce', 102, 102, 102, 'Cotton T-Shirt', 'cotton-t-shirt', 'Soft cotton round-neck t-shirt for daily wear.', 'piece', 499.00, 399.00, 40, 'ECM-TSH-103', null, 5.00, 39.00, '890200000103', 'Cotton T-Shirt', 'Comfortable daily wear t-shirt.', '["Size: M,L,XL","Fabric: Cotton"]', '["Blue","Grey"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(104, 'ecommerce', 102, 103, 103, 'Electric Kettle', 'electric-kettle', '1.8 litre stainless steel electric kettle for quick boiling.', 'piece', 1199.00, 999.00, 16, 'ECM-KTL-104', null, 18.00, 79.00, '890200000104', 'Electric Kettle 1.8L', 'Fast boiling stainless steel electric kettle.', '["Capacity: 1.8 litre","Material: Stainless steel"]', '["Silver"]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(105, 'ecommerce', null, 104, 104, 'Excel Basics Course', 'excel-basics-course', 'Downloadable beginner spreadsheet course PDF and practice file.', 'download', 299.00, 199.00, 999, 'ECM-DIG-105', null, 0.00, 0.00, 'DIGI-EXCEL-105', 'Excel Basics Course', 'Digital spreadsheet course for beginners.', '["Format: PDF","Access: Download"]', '[]', 1, 'https://example.com/downloads/excel-basics.pdf', 0, null, 0, 1, 0, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), vendor_id = values(vendor_id), brand_id = values(brand_id), category_id = values(category_id), name = values(name), slug = values(slug), description = values(description), unit = values(unit), price = values(price), discount_price = values(discount_price), stock = values(stock), sku = values(sku), tax_percent = values(tax_percent), shipping_cost = values(shipping_cost), barcode = values(barcode), attributes_json = values(attributes_json), colors_json = values(colors_json), is_digital = values(is_digital), digital_file_url = values(digital_file_url), is_flash_deal = values(is_flash_deal), flash_deal_ends_at = values(flash_deal_ends_at), is_clearance = values(is_clearance), status = values(status), is_featured = values(is_featured), updated_at = current_timestamp;

insert into banners (id, module_key, title, image, link_type, link_value, status, sort_order, created_at, updated_at) values
(101, 'ecommerce', 'E-Commerce Launch Sale', null, 'category', '101', 1, 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 'Digital Deals', null, 'category', '104', 1, 2, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), title = values(title), link_type = values(link_type), link_value = values(link_value), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into coupons (id, module_key, code, title, discount_type, discount_value, minimum_order_amount, maximum_discount, usage_limit, starts_at, expires_at, status, created_at, updated_at) values
(101, 'ecommerce', 'ECOM10', 'E-Commerce Demo Offer', 'percent', 10.00, 299.00, 150.00, 100, current_date, '2030-12-31', 1, current_timestamp, current_timestamp),
(102, 'ecommerce', 'DIGITAL50', 'Digital Product Offer', 'flat', 50.00, 199.00, 50.00, 100, current_date, '2030-12-31', 1, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), title = values(title), discount_type = values(discount_type), discount_value = values(discount_value), minimum_order_amount = values(minimum_order_amount), maximum_discount = values(maximum_discount), usage_limit = values(usage_limit), starts_at = values(starts_at), expires_at = values(expires_at), status = values(status), updated_at = current_timestamp;

insert into service_categories (id, name, description, icon, status, sort_order, created_at, updated_at) values
(101, 'Home Repair', 'Electrician, plumber and appliance repair services.', 'repair', 1, 1, current_timestamp, current_timestamp),
(102, 'Cleaning', 'Home cleaning and deep cleaning services.', 'cleaning', 1, 2, current_timestamp, current_timestamp),
(103, 'Beauty & Wellness', 'At-home salon and grooming services.', 'salon', 1, 3, current_timestamp, current_timestamp)
on duplicate key update name = values(name), description = values(description), icon = values(icon), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into service_providers (id, name, phone, email, area, password, commission_percent, status, created_at, updated_at) values
(101, 'Ravi Home Services', '9000000201', 'ravi.services@example.com', 'Lucknow Central', '$2y$12$dbdbykLbLBHoQKEJlqFvnuNr0/mIHLvhbqVHHvPVWx1CkGO6vf5hK', 12.00, 1, current_timestamp, current_timestamp),
(102, 'Sparkle Care Team', '9000000202', 'sparkle@example.com', 'Gomti Nagar', '$2y$12$dbdbykLbLBHoQKEJlqFvnuNr0/mIHLvhbqVHHvPVWx1CkGO6vf5hK', 15.00, 1, current_timestamp, current_timestamp)
on duplicate key update name = values(name), phone = values(phone), email = values(email), area = values(area), commission_percent = values(commission_percent), status = values(status), updated_at = current_timestamp;

insert into services (id, category_id, provider_id, vendor_id, name, description, checklist_json, duration_minutes, warranty_days, price, discount_price, image, status, is_featured, created_at, updated_at) values
(101, 101, 101, null, 'Electrician Visit', 'Switch, wiring and basic electrical repair visit.', '["Keep the main switch accessible","Share any known spark or outage details","Keep children away from the repair area"]', 60, 7, 299.00, 249.00, null, 1, 1, current_timestamp, current_timestamp),
(102, 101, 101, null, 'Plumber Visit', 'Leak, tap and bathroom fitting inspection.', '["Clear access to the leak or fixture","Keep water supply valve reachable","Share photos if leakage is intermittent"]', 60, 7, 349.00, 299.00, null, 1, 1, current_timestamp, current_timestamp),
(103, 102, 102, null, 'Home Deep Cleaning', 'Full home deep cleaning with trained staff.', '["Move fragile valuables before arrival","Keep running water available","Allow access to all rooms selected for cleaning"]', 180, 3, 1499.00, 1299.00, null, 1, 1, current_timestamp, current_timestamp),
(104, 103, 102, null, 'Men Haircut At Home', 'Professional haircut service at home.', '["Keep a chair near good lighting","Keep a towel ready","Share preferred haircut style before service"]', 45, 0, 249.00, 199.00, null, 1, 0, current_timestamp, current_timestamp)
on duplicate key update category_id = values(category_id), provider_id = values(provider_id), name = values(name), description = values(description), checklist_json = values(checklist_json), duration_minutes = values(duration_minutes), warranty_days = values(warranty_days), price = values(price), discount_price = values(discount_price), status = values(status), is_featured = values(is_featured), updated_at = current_timestamp;

insert into service_addons (id, service_id, name, description, price, status, sort_order, created_at, updated_at) values
(101, 101, 'Extra switch replacement', 'One basic switch replacement labour.', 99.00, 1, 1, current_timestamp, current_timestamp),
(102, 102, 'Drain cleaning add-on', 'Basic drain cleaning add-on.', 149.00, 1, 1, current_timestamp, current_timestamp),
(103, 103, 'Kitchen degreasing', 'Extra kitchen degreasing service.', 299.00, 1, 1, current_timestamp, current_timestamp),
(104, 104, 'Beard trim', 'Add beard trim to haircut.', 99.00, 1, 1, current_timestamp, current_timestamp)
on duplicate key update service_id = values(service_id), name = values(name), description = values(description), price = values(price), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into service_slots (id, service_id, provider_id, day_of_week, start_time, end_time, capacity, status, created_at, updated_at) values
(101, 101, 101, 1, '10:00', '12:00', 3, 1, current_timestamp, current_timestamp),
(102, 101, 101, 2, '15:00', '17:00', 3, 1, current_timestamp, current_timestamp),
(103, 102, 101, 3, '10:00', '12:00', 3, 1, current_timestamp, current_timestamp),
(104, 102, 101, 4, '15:00', '17:00', 3, 1, current_timestamp, current_timestamp),
(105, 103, 102, 5, '09:00', '12:00', 2, 1, current_timestamp, current_timestamp),
(106, 103, 102, 6, '13:00', '16:00', 2, 1, current_timestamp, current_timestamp),
(107, 104, 102, 1, '11:00', '12:00', 4, 1, current_timestamp, current_timestamp),
(108, 104, 102, 6, '16:00', '17:00', 4, 1, current_timestamp, current_timestamp)
on duplicate key update service_id = values(service_id), provider_id = values(provider_id), day_of_week = values(day_of_week), start_time = values(start_time), end_time = values(end_time), capacity = values(capacity), status = values(status), updated_at = current_timestamp;

insert into service_bookings (id, booking_number, service_id, provider_id, slot_id, guest_id, customer_name, customer_phone, customer_email, address, preferred_date, preferred_time, addons_json, addon_total, amount, payment_method, payment_status, booking_status, admin_note, note, created_at, updated_at) values
(101, 'CSV-DEMO-101', 101, 101, 101, 'demo-customer', 'Demo Customer', '9999999999', 'customer@example.com', 'Lucknow, India', date_add(current_date, interval 1 day), '10:00 - 12:00', '[{"id":101,"name":"Extra switch replacement","price":99}]', 99.00, 348.00, 'cash_on_service', 'unpaid', 'pending', 'Demo pending booking', 'Please call before visit', current_timestamp, current_timestamp),
(102, 'CSV-DEMO-102', 103, 102, 105, 'demo-customer', 'Demo Customer', '9999999999', 'customer@example.com', 'Gomti Nagar, Lucknow', date_add(current_date, interval 2 day), '09:00 - 12:00', '[]', 0.00, 1299.00, 'cash_on_service', 'unpaid', 'accepted', 'Demo accepted booking', null, current_timestamp, current_timestamp)
on duplicate key update service_id = values(service_id), provider_id = values(provider_id), slot_id = values(slot_id), customer_name = values(customer_name), customer_phone = values(customer_phone), address = values(address), preferred_date = values(preferred_date), preferred_time = values(preferred_time), addons_json = values(addons_json), addon_total = values(addon_total), amount = values(amount), payment_method = values(payment_method), payment_status = values(payment_status), booking_status = values(booking_status), admin_note = values(admin_note), note = values(note), updated_at = current_timestamp;
