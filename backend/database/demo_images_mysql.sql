set foreign_key_checks = 0;

update categories set image = '/uploads/demo-assets/grocery_basket.png', updated_at = current_timestamp where id = 1 and module_key = 'mart';
update categories set image = '/uploads/demo-assets/mart_milk.png', updated_at = current_timestamp where id = 2 and module_key = 'mart';
update categories set image = '/uploads/demo-assets/mart_rice.png', updated_at = current_timestamp where id = 3 and module_key = 'mart';
update categories set image = '/uploads/demo-assets/mart_chips.png', updated_at = current_timestamp where id = 4 and module_key = 'mart';
update categories set image = '/uploads/demo-assets/ecom_mobile_phone.png', updated_at = current_timestamp where id = 101 and module_key = 'ecommerce';
update categories set image = '/uploads/demo-assets/promo_ecommerce_banner.png', updated_at = current_timestamp where id = 102 and module_key = 'ecommerce';
update categories set image = '/uploads/demo-assets/service_appliance_repair.png', updated_at = current_timestamp where id = 103 and module_key = 'ecommerce';
update categories set image = '/uploads/demo-assets/medical_medicine_box.png', updated_at = current_timestamp where id = 501 and module_key = 'medical';
update categories set image = '/uploads/demo-assets/medical_delivery.png', updated_at = current_timestamp where id = 502 and module_key = 'medical';
update categories set image = '/uploads/demo-assets/medical_sanitizer.png', updated_at = current_timestamp where id = 503 and module_key = 'medical';

update brands set image = '/uploads/demo-assets/grocery_basket.png', updated_at = current_timestamp where id = 1 and module_key = 'mart';
update brands set image = '/uploads/demo-assets/mart_milk.png', updated_at = current_timestamp where id = 2 and module_key = 'mart';
update brands set image = '/uploads/demo-assets/mart_chips.png', updated_at = current_timestamp where id = 3 and module_key = 'mart';
update brands set image = '/uploads/demo-assets/ecom_earbuds.png', updated_at = current_timestamp where id = 101 and module_key = 'ecommerce';
update brands set image = '/uploads/demo-assets/promo_ecommerce_banner.png', updated_at = current_timestamp where id = 102 and module_key = 'ecommerce';
update brands set image = '/uploads/demo-assets/ecom_smart_watch.png', updated_at = current_timestamp where id = 103 and module_key = 'ecommerce';
update brands set image = '/uploads/demo-assets/medical_pharmacy_counter.png', updated_at = current_timestamp where id = 501 and module_key = 'medical';
update brands set image = '/uploads/demo-assets/medical_medicine_box.png', updated_at = current_timestamp where id = 502 and module_key = 'medical';
update brands set image = '/uploads/demo-assets/medical_sanitizer.png', updated_at = current_timestamp where id = 503 and module_key = 'medical';

update banners set image = '/uploads/demo-assets/promo_grocery_banner.png', updated_at = current_timestamp where id = 1 and module_key = 'mart';
update banners set image = '/uploads/demo-assets/promo_grocery_discount_banner.png', updated_at = current_timestamp where id = 2 and module_key = 'mart';
update banners set image = '/uploads/demo-assets/promo_ecommerce_banner.png', updated_at = current_timestamp where id = 101 and module_key = 'ecommerce';
update banners set image = '/uploads/demo-assets/ecom_product_background.png', updated_at = current_timestamp where id = 102 and module_key = 'ecommerce';
update banners set image = '/uploads/demo-assets/promo_medical_banner.png', updated_at = current_timestamp where id = 501 and module_key = 'medical';
update banners set image = '/uploads/demo-assets/promo_medical_consultant_banner.png', updated_at = current_timestamp where id = 502 and module_key = 'medical';

update products set thumbnail = '/uploads/demo-assets/mart_fresh_apple.png', updated_at = current_timestamp where id = 1 and module_key = 'mart';
update products set thumbnail = '/uploads/demo-assets/mart_banana.png', updated_at = current_timestamp where id = 2 and module_key = 'mart';
update products set thumbnail = '/uploads/demo-assets/mart_milk.png', updated_at = current_timestamp where id = 3 and module_key = 'mart';
update products set thumbnail = '/uploads/demo-assets/mart_chips.png', updated_at = current_timestamp where id = 4 and module_key = 'mart';
update products set thumbnail = '/uploads/demo-assets/ecom_earbuds.png', updated_at = current_timestamp where id = 101 and module_key = 'ecommerce';
update products set thumbnail = '/uploads/demo-assets/ecom_mobile_phone.png', updated_at = current_timestamp where id = 102 and module_key = 'ecommerce';
update products set thumbnail = '/uploads/demo-assets/promo_ecommerce_banner.png', updated_at = current_timestamp where id = 103 and module_key = 'ecommerce';
update products set thumbnail = '/uploads/demo-assets/service_appliance_repair.png', updated_at = current_timestamp where id = 104 and module_key = 'ecommerce';
update products set thumbnail = '/uploads/demo-assets/medical_medicine_box.png', updated_at = current_timestamp where id = 501 and module_key = 'medical';
update products set thumbnail = '/uploads/demo-assets/medical_delivery.png', updated_at = current_timestamp where id = 502 and module_key = 'medical';
update products set thumbnail = '/uploads/demo-assets/medical_sanitizer.png', updated_at = current_timestamp where id = 503 and module_key = 'medical';

insert into products
(id, module_key, zone_id, vendor_id, brand_id, category_id, name, slug, description, unit, price, discount_price, stock, sku, thumbnail, tax_percent, shipping_cost, barcode, seo_title, seo_description, attributes_json, colors_json, is_digital, digital_file_url, is_flash_deal, flash_deal_ends_at, is_clearance, status, is_featured, created_at, updated_at) values
(5, 'mart', 2, 2, 2, 2, 'Brown Bread', 'brown-bread', 'Soft sliced bread loaf for breakfast and sandwiches.', '400 g', 55.00, 49.00, 30, 'MART-BRD-005', '/uploads/demo-assets/mart_bread.png', 5.00, 20.00, '890100000005', 'Brown Bread 400 g', 'Soft sliced bread loaf.', '["Type: Bakery","Pack: 400 g"]', '["Brown"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(6, 'mart', 1, 1, 1, 3, 'Basmati Rice', 'basmati-rice', 'Long grain basmati rice for daily and festive meals.', '5 kg', 620.00, 590.00, 18, 'MART-RCE-006', '/uploads/demo-assets/mart_rice.png', 5.00, 30.00, '890100000006', 'Basmati Rice 5 kg', 'Aromatic long grain rice.', '["Grain: Long","Pack: 5 kg"]', '["White"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(7, 'mart', 1, 1, 1, 3, 'Toor Dal', 'toor-dal', 'Protein rich toor dal for everyday meals.', '1 kg', 165.00, 149.00, 22, 'MART-DAL-007', '/uploads/demo-assets/mart_toor_dal.png', 5.00, 30.00, '890100000007', 'Toor Dal 1 kg', 'Premium split pigeon pea.', '["Type: Pulses","Pack: 1 kg"]', '["Yellow"]', 0, null, 0, null, 1, 1, 0, current_timestamp, current_timestamp),
(8, 'mart', 2, 2, 3, 4, 'Cold Drink', 'cold-drink', 'Refreshing chilled beverage bottle.', '750 ml', 45.00, null, 75, 'MART-DRK-008', '/uploads/demo-assets/mart_cold_drink.png', 12.00, 20.00, '890100000008', 'Cold Drink 750 ml', 'Refreshing beverage bottle.', '["Volume: 750 ml","Serve: Chilled"]', '["Blue"]', 0, null, 0, null, 0, 1, 0, current_timestamp, current_timestamp),
(9, 'mart', 1, 1, 3, 3, 'Dishwash Liquid', 'dishwash-liquid', 'Powerful dishwash liquid for clean utensils.', '500 ml', 120.00, 99.00, 35, 'MART-DWL-009', '/uploads/demo-assets/mart_dishwash_liquid.png', 18.00, 30.00, '890100000009', 'Dishwash Liquid 500 ml', 'Daily utensil cleaning liquid.', '["Pack: 500 ml","Use: Kitchen"]', '["Yellow"]', 0, null, 0, null, 0, 1, 0, current_timestamp, current_timestamp),
(10, 'mart', 1, 1, 3, 3, 'Laundry Detergent', 'laundry-detergent', 'Fresh fragrance detergent for daily laundry.', '1 litre', 210.00, 185.00, 28, 'MART-LND-010', '/uploads/demo-assets/mart_laundry_detergent.png', 18.00, 30.00, '890100000010', 'Laundry Detergent 1 litre', 'Liquid detergent for daily laundry.', '["Pack: 1 litre","Use: Laundry"]', '["Blue"]', 0, null, 0, null, 0, 1, 0, current_timestamp, current_timestamp),
(105, 'ecommerce', 1, 101, 101, 101, 'Smart Watch', 'smart-watch', 'Daily fitness smart watch with clean display.', 'piece', 2499.00, 2199.00, 20, 'ECM-WCH-105', '/uploads/demo-assets/ecom_smart_watch.png', 18.00, 49.00, '890200000105', 'Smart Watch', 'Fitness watch with activity tracking.', '["Display: Touch","Battery: 7 days"]', '["Gold","Black"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(106, 'ecommerce', 1, 101, 101, 101, 'Mobile Phone', 'mobile-phone', 'Everyday smartphone for calls, apps and browsing.', 'piece', 9999.00, 9499.00, 12, 'ECM-MOB-106', '/uploads/demo-assets/ecom_mobile_phone_alt.png', 18.00, 49.00, '890200000106', 'Mobile Phone', 'Everyday smartphone.', '["Storage: 128 GB","Warranty: 1 year"]', '["Black","White"]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp),
(504, 'medical', 1, 501, 503, 503, 'Hand Sanitizer', 'hand-sanitizer', 'Hand sanitizer bottle for daily hygiene.', '500 ml', 99.00, 79.00, 80, 'MED-SAN-504', '/uploads/demo-assets/medical_sanitizer.png', 12.00, 20.00, '890300000504', 'Hand Sanitizer 500 ml', 'Daily hygiene sanitizer.', '["Volume: 500 ml","Use: Hygiene"]', '[]', 0, null, 0, null, 0, 1, 1, current_timestamp, current_timestamp)
on duplicate key update
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
thumbnail = values(thumbnail),
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

delete from product_images where product_id in (1,2,3,4,5,6,7,8,9,10,101,102,103,104,105,106,501,502,503,504);
insert into product_images (product_id, image, sort_order, created_at) values
(1, '/uploads/demo-assets/mart_fresh_apple.png', 1, current_timestamp),
(2, '/uploads/demo-assets/mart_banana.png', 1, current_timestamp),
(3, '/uploads/demo-assets/mart_milk.png', 1, current_timestamp),
(4, '/uploads/demo-assets/mart_chips.png', 1, current_timestamp),
(5, '/uploads/demo-assets/mart_bread.png', 1, current_timestamp),
(6, '/uploads/demo-assets/mart_rice.png', 1, current_timestamp),
(7, '/uploads/demo-assets/mart_toor_dal.png', 1, current_timestamp),
(8, '/uploads/demo-assets/mart_cold_drink.png', 1, current_timestamp),
(9, '/uploads/demo-assets/mart_dishwash_liquid.png', 1, current_timestamp),
(10, '/uploads/demo-assets/mart_laundry_detergent.png', 1, current_timestamp),
(101, '/uploads/demo-assets/ecom_earbuds.png', 1, current_timestamp),
(102, '/uploads/demo-assets/ecom_mobile_phone.png', 1, current_timestamp),
(103, '/uploads/demo-assets/promo_ecommerce_banner.png', 1, current_timestamp),
(104, '/uploads/demo-assets/service_appliance_repair.png', 1, current_timestamp),
(105, '/uploads/demo-assets/ecom_smart_watch.png', 1, current_timestamp),
(106, '/uploads/demo-assets/ecom_mobile_phone_alt.png', 1, current_timestamp),
(501, '/uploads/demo-assets/medical_medicine_box.png', 1, current_timestamp),
(502, '/uploads/demo-assets/medical_delivery.png', 1, current_timestamp),
(503, '/uploads/demo-assets/medical_sanitizer.png', 1, current_timestamp),
(504, '/uploads/demo-assets/medical_sanitizer.png', 1, current_timestamp);

update hotels
set thumbnail = '/uploads/demo-assets/hotel_exterior.png',
    gallery_json = '["/uploads/demo-assets/hotel_exterior.png","/uploads/demo-assets/hotel_lobby.png","/uploads/demo-assets/hotel_room.png"]',
    updated_at = current_timestamp
where id = 1;
update hotels
set thumbnail = '/uploads/demo-assets/hotel_lobby.png',
    gallery_json = '["/uploads/demo-assets/hotel_lobby.png","/uploads/demo-assets/hotel_deluxe_room.png","/uploads/demo-assets/hotel_room_service_tray.png"]',
    updated_at = current_timestamp
where id = 2;
update hotels
set thumbnail = '/uploads/demo-assets/hotel_room.png',
    gallery_json = '["/uploads/demo-assets/hotel_room.png","/uploads/demo-assets/hotel_pool.png","/uploads/demo-assets/hotel_room_service_tray.png"]',
    updated_at = current_timestamp
where id = 3;

update hotel_rooms set thumbnail = '/uploads/demo-assets/hotel_deluxe_room.png', updated_at = current_timestamp where id = 1;
update hotel_rooms set thumbnail = '/uploads/demo-assets/hotel_room.png', updated_at = current_timestamp where id = 2;
update hotel_rooms set thumbnail = '/uploads/demo-assets/hotel_room_service_tray.png', updated_at = current_timestamp where id = 3;

update services set image = '/uploads/demo-assets/service_electrician.png', updated_at = current_timestamp where id = 1;
update services set image = '/uploads/demo-assets/service_plumber.png', updated_at = current_timestamp where id = 2;
update services set image = '/uploads/demo-assets/service_cleaning.png', updated_at = current_timestamp where id = 3;
update services set image = '/uploads/demo-assets/service_salon.png', updated_at = current_timestamp where id = 4;

insert into service_categories
(id, name, description, icon, status, sort_order, created_at, updated_at) values
(4, 'Mechanic', 'Vehicle mechanic and inspection services.', 'mechanic', 1, 4, current_timestamp, current_timestamp),
(5, 'Appliance Repair', 'AC, fridge and appliance repair services.', 'appliance', 1, 5, current_timestamp, current_timestamp)
on duplicate key update
name = values(name),
description = values(description),
icon = values(icon),
status = values(status),
sort_order = values(sort_order),
updated_at = current_timestamp;

insert into services
(id, zone_id, category_id, provider_id, vendor_id, name, description, duration_minutes, price, discount_price, image, status, is_featured, created_at, updated_at) values
(5, 1, 4, 1, null, 'Mechanic Inspection', 'Basic mechanic inspection and tool-based diagnosis.', 90, 499.00, 399.00, '/uploads/demo-assets/service_mechanic_tools.png', 1, 0, current_timestamp, current_timestamp),
(6, 2, 5, 2, null, 'Appliance Repair Visit', 'Home appliance repair inspection visit.', 90, 599.00, 499.00, '/uploads/demo-assets/service_appliance_repair.png', 1, 0, current_timestamp, current_timestamp)
on duplicate key update
zone_id = values(zone_id),
category_id = values(category_id),
provider_id = values(provider_id),
name = values(name),
description = values(description),
duration_minutes = values(duration_minutes),
price = values(price),
discount_price = values(discount_price),
image = values(image),
status = values(status),
is_featured = values(is_featured),
updated_at = current_timestamp;

insert into settings (key_name, value, updated_at) values
('floating_ad_image_url', '/uploads/demo-assets/promo_grocery_discount_banner.png', current_timestamp),
('floating_ad_video_url', '', current_timestamp),
('floating_ad_link_url', '/mart', current_timestamp),
('floating_ad_title', 'Fresh grocery offers', current_timestamp),
('floating_ad_message', 'Demo ad image loaded from backend uploads.', current_timestamp)
on duplicate key update
value = values(value),
updated_at = current_timestamp;

set foreign_key_checks = 1;
