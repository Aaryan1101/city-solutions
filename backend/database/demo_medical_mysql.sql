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

insert into categories
(id, module_key, name, slug, image, shipping_cost, status, sort_order, created_at, updated_at) values
(501, 'medical', 'Pain Relief', 'pain-relief', null, 20.00, 1, 1, current_timestamp, current_timestamp),
(502, 'medical', 'Cold & Cough', 'cold-cough', null, 20.00, 1, 2, current_timestamp, current_timestamp),
(503, 'medical', 'Vitamins', 'vitamins', null, 20.00, 1, 3, current_timestamp, current_timestamp),
(504, 'medical', 'Diabetes Care', 'diabetes-care', null, 20.00, 1, 4, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), name = values(name), slug = values(slug), shipping_cost = values(shipping_cost), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into brands
(id, module_key, name, slug, image, status, sort_order, created_at, updated_at) values
(501, 'medical', 'HealthPlus', 'healthplus', null, 1, 1, current_timestamp, current_timestamp),
(502, 'medical', 'CureWell', 'curewell', null, 1, 2, current_timestamp, current_timestamp),
(503, 'medical', 'DailyCare', 'dailycare', null, 1, 3, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), name = values(name), slug = values(slug), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into banners
(id, module_key, title, image, link_type, link_value, status, sort_order, created_at, updated_at) values
(501, 'medical', 'Medicine delivery at home', null, 'none', null, 1, 1, current_timestamp, current_timestamp),
(502, 'medical', 'Health essentials', null, 'none', null, 1, 2, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), title = values(title), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into products
(id, module_key, vendor_id, brand_id, category_id, name, slug, description, unit, price, discount_price, stock, sku, thumbnail, tax_percent, shipping_cost, barcode, seo_title, seo_description, attributes_json, colors_json, is_digital, digital_file_url, is_flash_deal, flash_deal_ends_at, is_clearance, status, is_featured, created_at, updated_at) values
(501, 'medical', null, 501, 501, 'Paracetamol 500mg', 'paracetamol-500mg', 'Common fever and pain relief tablet. Use as directed by a physician.', '10 tablets', 35.00, 30.00, 100, 'MED-PARA-500', null, 5.00, 20.00, '890200000001', 'Paracetamol 500mg', 'Fever and pain relief tablets.', '["Prescription: Not required","Pack: 10 tablets"]', '[]', 0, null, 1, '2030-12-31 23:59:59', 0, 1, 1, current_timestamp, current_timestamp),
(502, 'medical', null, 502, 502, 'Cough Syrup', 'cough-syrup', 'Cough relief syrup for dry and wet cough symptoms.', '100 ml', 120.00, 99.00, 60, 'MED-COUGH-100', null, 12.00, 20.00, '890200000002', 'Cough Syrup 100ml', 'Cough relief syrup.', '["Volume: 100 ml","Use: Cough care"]', '[]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(503, 'medical', null, 503, 503, 'Vitamin C Tablets', 'vitamin-c-tablets', 'Daily vitamin C supplement for immunity support.', '30 tablets', 180.00, 149.00, 45, 'MED-VITC-30', null, 12.00, 20.00, '890200000003', 'Vitamin C Tablets', 'Daily immunity supplement.', '["Pack: 30 tablets","Type: Supplement"]', '[]', 0, null, 1, '2030-12-31 23:59:59', 1, 1, 1, current_timestamp, current_timestamp),
(504, 'medical', null, 501, 504, 'Glucometer Strips', 'glucometer-strips', 'Blood glucose test strips for home monitoring.', '50 strips', 699.00, 649.00, 25, 'MED-GLUCO-50', null, 12.00, 20.00, '890200000004', 'Glucometer Strips', 'Blood sugar testing strips.', '["Pack: 50 strips","Use: Diabetes care"]', '[]', 0, null, 0, null, 0, 1, 0, current_timestamp, current_timestamp),
(505, 'medical', null, 502, 501, 'Pain Relief Balm', 'pain-relief-balm', 'Topical balm for muscle pain and stiffness.', '25 g', 85.00, 75.00, 70, 'MED-BALM-25', null, 12.00, 20.00, '890200000005', 'Pain Relief Balm', 'Topical pain relief balm.', '["Pack: 25 g","Use: External only"]', '[]', 0, null, 0, null, 1, 1, 0, current_timestamp, current_timestamp),
(506, 'medical', null, 502, 502, 'Azithromycin 500mg', 'azithromycin-500mg', 'Prescription antibiotic tablet. Dispense only against a valid prescription.', '3 tablets', 160.00, 145.00, 35, 'MED-AZI-500', null, 12.00, 20.00, '890200000006', 'Azithromycin 500mg', 'Prescription antibiotic medicine.', '["Prescription: Required","Pack: 3 tablets","Category: Antibiotic"]', '[]', 0, null, 0, null, 0, 1, 0, current_timestamp, current_timestamp)
on duplicate key update module_key = values(module_key), brand_id = values(brand_id), category_id = values(category_id), name = values(name), slug = values(slug), description = values(description), unit = values(unit), price = values(price), discount_price = values(discount_price), stock = values(stock), sku = values(sku), tax_percent = values(tax_percent), shipping_cost = values(shipping_cost), barcode = values(barcode), seo_title = values(seo_title), seo_description = values(seo_description), attributes_json = values(attributes_json), colors_json = values(colors_json), is_flash_deal = values(is_flash_deal), flash_deal_ends_at = values(flash_deal_ends_at), is_clearance = values(is_clearance), status = values(status), is_featured = values(is_featured), updated_at = current_timestamp;

update products set medicine_type = 'otc', schedule_tag = null, max_qty_per_order = 6, max_qty_per_month = null, requires_pharmacist_review = 0, requires_age_confirmation = 0 where id in (501, 502, 503, 504, 505) and module_key = 'medical';
update products set medicine_type = 'prescription_required', schedule_tag = 'Antibiotic / prescription only', max_qty_per_order = 1, max_qty_per_month = 2, requires_pharmacist_review = 1, requires_age_confirmation = 1 where id = 506 and module_key = 'medical';
