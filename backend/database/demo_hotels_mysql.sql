insert into hotel_categories
(id, name, description, status, sort_order, created_at, updated_at) values
(1, 'Business Hotels', 'Work-friendly stays with fast connectivity.', 1, 1, current_timestamp, current_timestamp),
(2, 'Family Hotels', 'Comfortable stays for families and groups.', 1, 2, current_timestamp, current_timestamp),
(3, 'Premium Stays', 'Upscale hotels with richer amenities.', 1, 3, current_timestamp, current_timestamp)
on duplicate key update name = values(name), description = values(description), status = values(status), sort_order = values(sort_order), updated_at = current_timestamp;

insert into hotel_owners
(id, name, phone, email, password, status, created_at, updated_at) values
(1, 'Hotel Demo Owner', '9876543210', 'hotelowner@example.com', '$2y$12$WS3N9i0a4yZ.7qzIVb/LbubrM2A8APIi7H/tcFRzUrGW5.Bj24WrW', 1, current_timestamp, current_timestamp)
on duplicate key update name = values(name), phone = values(phone), email = values(email), password = values(password), status = values(status), updated_at = current_timestamp;

insert into hotels
(id, owner_id, category_id, name, city, area, address, description, star_rating, rating, review_count, amenities_json, thumbnail, gallery_json, status, is_featured, created_at, updated_at) values
(1, 1, 1, 'City Central Hotel', 'Lucknow', 'Hazratganj', 'Hazratganj, Lucknow', 'A central business hotel near shopping streets and offices.', 4.0, 4.5, 2, '["WiFi","Breakfast","Parking","Conference Room"]', null, '[]', 1, 1, current_timestamp, current_timestamp),
(2, 1, 2, 'Gomti Family Inn', 'Lucknow', 'Gomti Nagar', 'Gomti Nagar, Lucknow', 'Family friendly hotel with spacious rooms and easy city access.', 3.5, 4.2, 1, '["WiFi","Restaurant","Lift","Room Service"]', null, '[]', 1, 1, current_timestamp, current_timestamp),
(3, null, 3, 'Royal Heritage Suites', 'Lucknow', 'Charbagh', 'Charbagh, Lucknow', 'Premium suite property for comfortable city stays.', 5.0, 4.8, 3, '["WiFi","Pool","Gym","Breakfast","Airport Pickup"]', null, '[]', 1, 0, current_timestamp, current_timestamp)
on duplicate key update owner_id = values(owner_id), category_id = values(category_id), name = values(name), city = values(city), area = values(area), address = values(address), description = values(description), star_rating = values(star_rating), rating = values(rating), review_count = values(review_count), amenities_json = values(amenities_json), status = values(status), is_featured = values(is_featured), updated_at = current_timestamp;

insert into hotel_rooms
(id, hotel_id, name, description, capacity_adults, capacity_children, total_rooms, price_per_night, discount_price, tax_percent, amenities_json, thumbnail, status, created_at, updated_at) values
(1, 1, 'Deluxe King Room', 'King bed room with desk and city view.', 2, 1, 8, 3200.00, 2899.00, 12.00, '["King Bed","Air Conditioning","Work Desk","Tea Kit"]', null, 1, current_timestamp, current_timestamp),
(2, 1, 'Executive Twin Room', 'Twin room for business travellers.', 2, 0, 6, 3600.00, null, 12.00, '["Twin Beds","Air Conditioning","Work Desk"]', null, 1, current_timestamp, current_timestamp),
(3, 2, 'Family Room', 'Large family room with extra bedding.', 3, 2, 5, 2800.00, 2499.00, 12.00, '["Queen Bed","Extra Mattress","TV","Room Service"]', null, 1, current_timestamp, current_timestamp),
(4, 3, 'Premium Suite', 'Spacious suite with lounge area.', 2, 1, 4, 6200.00, 5799.00, 18.00, '["Suite","Lounge","Mini Bar","Bathtub"]', null, 1, current_timestamp, current_timestamp)
on duplicate key update hotel_id = values(hotel_id), name = values(name), description = values(description), capacity_adults = values(capacity_adults), capacity_children = values(capacity_children), total_rooms = values(total_rooms), price_per_night = values(price_per_night), discount_price = values(discount_price), tax_percent = values(tax_percent), amenities_json = values(amenities_json), status = values(status), updated_at = current_timestamp;

insert into hotel_reviews
(id, hotel_id, booking_id, guest_id, customer_name, rating, comment, status, created_at, updated_at) values
(1, 1, null, 'demo', 'Amit', 5, 'Clean room and quick check-in.', 1, current_timestamp, current_timestamp),
(2, 1, null, 'demo', 'Neha', 4, 'Good location and breakfast.', 1, current_timestamp, current_timestamp),
(3, 2, null, 'demo', 'Rahul', 4, 'Comfortable for family stay.', 1, current_timestamp, current_timestamp),
(4, 3, null, 'demo', 'Priya', 5, 'Premium rooms and polite staff.', 1, current_timestamp, current_timestamp)
on duplicate key update hotel_id = values(hotel_id), customer_name = values(customer_name), rating = values(rating), comment = values(comment), status = values(status), updated_at = current_timestamp;
