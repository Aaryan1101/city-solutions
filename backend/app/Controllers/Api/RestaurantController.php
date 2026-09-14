<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\PaymentMethodCatalog;
use App\Support\Request;
use App\Support\Response;
use App\Support\RestaurantSchema;
use App\Support\Settings;
use App\Support\ZoneSchema;

final class RestaurantController
{
    public function config(): void
    {
        RestaurantSchema::ensure();
        ZoneSchema::ensure();
        Response::json($this->configData());
    }

    public function home(): void
    {
        RestaurantSchema::ensure();
        ZoneSchema::ensure();
        Response::json([
            'config' => $this->configData(),
            'categories' => $this->categoriesData(),
            'food_categories' => $this->foodCategoriesData(),
            'featured_restaurants' => $this->restaurantsData('restaurants.is_featured = 1', [], 10),
            'popular_restaurants' => $this->restaurantsData('1 = 1', [], 20),
            'book_tonight' => $this->restaurantsData('1 = 1', [], 10),
            'featured_food_items' => $this->foodItemsData('restaurant_food_items.is_featured = 1', [], 12),
            'popular_food_items' => $this->foodItemsData('1 = 1', [], 20),
        ]);
    }

    public function foodItems(): void
    {
        RestaurantSchema::ensure();
        ZoneSchema::ensure();
        $where = '1 = 1';
        $params = [];
        $query = trim((string) ($_GET['query'] ?? ''));
        if ($query !== '') {
            $where .= ' and (restaurant_food_items.name like :query or restaurant_food_items.description like :query or restaurants.name like :query)';
            $params['query'] = '%' . $query . '%';
        }
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where .= ' and restaurant_food_items.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);
        if ($restaurantId > 0) {
            $where .= ' and restaurant_food_items.restaurant_id = :restaurant_id';
            $params['restaurant_id'] = $restaurantId;
        }
        Response::json(['data' => $this->foodItemsData($where, $params, 80)]);
    }

    public function restaurants(): void
    {
        RestaurantSchema::ensure();
        ZoneSchema::ensure();
        $where = '1 = 1';
        $params = [];
        $query = trim((string) ($_GET['query'] ?? ''));
        if ($query !== '') {
            $where .= ' and (restaurants.name like :query or restaurants.cuisine like :query or restaurants.area like :query)';
            $params['query'] = '%' . $query . '%';
        }
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where .= ' and restaurants.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        $zoneId = ZoneSchema::requestZoneId();
        if ($zoneId > 0) {
            $where .= ' and (restaurants.zone_id is null or restaurants.zone_id = :zone_id)';
            $params['zone_id'] = $zoneId;
        }
        Response::json(['data' => $this->restaurantsData($where, $params, 80)]);
    }

    public function show(int $id): void
    {
        RestaurantSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select restaurants.*, restaurant_categories.name as category_name
             from restaurants
             left join restaurant_categories on restaurant_categories.id = restaurants.category_id
             where restaurants.id = :id and restaurants.status = 1
             limit 1'
        );
        $stmt->execute(['id' => $id]);
        $restaurant = $stmt->fetch();
        if (!$restaurant) {
            Response::json(['message' => 'Restaurant not found'], 404);
            return;
        }
        Response::json([
            'data' => $this->formatRestaurant($restaurant),
            'tables' => $this->tablesData($id),
            'reviews' => $this->reviewsData($id),
            'food_items' => $this->foodItemsData('restaurant_food_items.restaurant_id = :restaurant_id', ['restaurant_id' => $id], 80),
        ]);
    }

    public function foodOrders(): void
    {
        RestaurantSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select restaurant_food_orders.*, restaurants.name as restaurant_name
             from restaurant_food_orders
             join restaurants on restaurants.id = restaurant_food_orders.restaurant_id
             where restaurant_food_orders.guest_id = :guest_id
             order by restaurant_food_orders.id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function placeFoodOrder(): void
    {
        RestaurantSchema::ensure();
        ZoneSchema::ensure();
        NotificationSchema::ensure();
        $body = Request::json();
        foreach (['guest_id', 'restaurant_id', 'customer_name', 'customer_phone', 'delivery_address', 'items'] as $field) {
            if ($field === 'items') {
                if (empty($body['items']) || !is_array($body['items'])) {
                    Response::json(['message' => 'Cart is empty'], 422);
                    return;
                }
                continue;
            }
            if (trim((string) ($body[$field] ?? '')) === '') {
                Response::json(['message' => 'Missing required field: ' . $field], 422);
                return;
            }
        }
        $restaurantId = (int) $body['restaurant_id'];
        $zoneId = max(0, (int) ($body['zone_id'] ?? $_GET['zone_id'] ?? 0));
        $restaurant = $this->restaurantRow($restaurantId);
        if (!$restaurant || (int) ($restaurant['delivery_enabled'] ?? 1) !== 1) {
            Response::json(['message' => 'Restaurant delivery is not available'], 404);
            return;
        }
        if ($zoneId > 0 && !empty($restaurant['zone_id']) && (int) $restaurant['zone_id'] !== $zoneId) {
            Response::json(['message' => 'Restaurant is not available in selected zone'], 422);
            return;
        }

        $ids = [];
        $requested = [];
        foreach ($body['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int) ($item['food_item_id'] ?? $item['id'] ?? 0);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            if ($id <= 0) {
                continue;
            }
            $ids[] = $id;
            $requested[$id] = ($requested[$id] ?? 0) + $quantity;
        }
        $ids = array_values(array_unique($ids));
        if (!$ids) {
            Response::json(['message' => 'Cart is empty'], 422);
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $itemsStmt = Database::connection()->prepare(
            'select * from restaurant_food_items where id in (' . $placeholders . ') and restaurant_id = ? and status = 1'
        );
        $itemsStmt->execute([...$ids, $restaurantId]);
        $rows = $itemsStmt->fetchAll();
        if (count($rows) !== count($ids)) {
            Response::json(['message' => 'Some food items are not available'], 422);
            return;
        }

        $subtotal = 0.0;
        foreach ($rows as $row) {
            $quantity = $requested[(int) $row['id']] ?? 0;
            if ($quantity <= 0 || (int) ($row['stock'] ?? 0) < $quantity) {
                Response::json(['message' => ($row['name'] ?? 'Food item') . ' is out of stock'], 409);
                return;
            }
            $price = $row['discount_price'] !== null && (float) $row['discount_price'] > 0
                ? (float) $row['discount_price']
                : (float) $row['price'];
            $subtotal += $price * $quantity;
        }
        $deliveryFee = max(0, (float) ($restaurant['delivery_fee'] ?? 0));
        $total = $subtotal + $deliveryFee;
        $paymentMethod = trim((string) ($body['payment_method'] ?? 'cash_on_delivery'));
        if (!in_array($paymentMethod, ['cash_on_delivery', 'wallet'], true)) {
            Response::json(['message' => 'Selected payment method is not available for food delivery'], 422);
            return;
        }

        $db = Database::connection();
        $orderNumber = 'CRF' . date('ymdHis') . random_int(100, 999);
        try {
            $db->beginTransaction();
            $order = $db->prepare(
                'insert into restaurant_food_orders
                 (order_number, guest_id, restaurant_id, zone_id, customer_name, customer_phone, customer_email, delivery_address, subtotal, delivery_fee, total, payment_method, payment_status, order_status, note, created_at, updated_at)
                 values
                 (:order_number, :guest_id, :restaurant_id, :zone_id, :customer_name, :customer_phone, :customer_email, :delivery_address, :subtotal, :delivery_fee, :total, :payment_method, :payment_status, \'pending\', :note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $guestId = trim((string) $body['guest_id']);
            $customerName = trim((string) $body['customer_name']);
            $order->execute([
                'order_number' => $orderNumber,
                'guest_id' => $guestId,
                'restaurant_id' => $restaurantId,
                'zone_id' => $zoneId > 0 ? $zoneId : null,
                'customer_name' => $customerName,
                'customer_phone' => trim((string) $body['customer_phone']),
                'customer_email' => trim((string) ($body['customer_email'] ?? '')) ?: null,
                'delivery_address' => trim((string) $body['delivery_address']),
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'wallet' ? 'pending_verification' : 'unpaid',
                'note' => trim((string) ($body['note'] ?? '')) ?: null,
            ]);
            $orderId = (int) $db->lastInsertId();
            $itemInsert = $db->prepare(
                'insert into restaurant_food_order_items
                 (order_id, food_item_id, restaurant_id, item_name, unit_price, quantity, line_total, created_at)
                 values (:order_id, :food_item_id, :restaurant_id, :item_name, :unit_price, :quantity, :line_total, CURRENT_TIMESTAMP)'
            );
            $stockUpdate = $db->prepare('update restaurant_food_items set stock = stock - :quantity, updated_at = CURRENT_TIMESTAMP where id = :id and stock >= :quantity');
            foreach ($rows as $row) {
                $quantity = $requested[(int) $row['id']];
                $price = $row['discount_price'] !== null && (float) $row['discount_price'] > 0
                    ? (float) $row['discount_price']
                    : (float) $row['price'];
                $lineTotal = $price * $quantity;
                $stockUpdate->execute(['id' => (int) $row['id'], 'quantity' => $quantity]);
                if ($stockUpdate->rowCount() < 1) {
                    throw new \RuntimeException('Stock update failed');
                }
                $itemInsert->execute([
                    'order_id' => $orderId,
                    'food_item_id' => (int) $row['id'],
                    'restaurant_id' => $restaurantId,
                    'item_name' => (string) $row['name'],
                    'unit_price' => $price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);
            }
            NotificationLog::record('admin', null, null, 'New restaurant food order', $orderNumber . ' placed by ' . $customerName, $orderId, 'restaurant');
            NotificationLog::record('customer', null, $guestId, 'Food order placed', 'Your restaurant food order ' . $orderNumber . ' was placed successfully.', $orderId, 'restaurant');
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::json(['message' => 'Food order could not be placed. Please try again.'], 500);
            return;
        }
        Response::json([
            'message' => 'Food order placed successfully',
            'data' => [
                'id' => $orderId,
                'order_number' => $orderNumber,
                'order_status' => 'pending',
                'total' => $total,
            ],
        ], 201);
    }

    public function slots(int $restaurantId): void
    {
        RestaurantSchema::ensure();
        $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
        $partySize = max(1, (int) ($_GET['party_size'] ?? 2));
        if (strtotime($date) === false) {
            Response::json(['message' => 'Invalid booking date'], 422);
            return;
        }
        Response::json(['data' => $this->availableSlots($restaurantId, $date, $partySize)]);
    }

    public function bookings(): void
    {
        RestaurantSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select restaurant_bookings.*, restaurants.name as restaurant_name, restaurants.address as restaurant_address
             from restaurant_bookings
             join restaurants on restaurants.id = restaurant_bookings.restaurant_id
             where restaurant_bookings.guest_id = :guest_id
             order by restaurant_bookings.id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function waitlist(): void
    {
        RestaurantSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select restaurant_waitlists.*, restaurants.name as restaurant_name, restaurants.address as restaurant_address
             from restaurant_waitlists
             join restaurants on restaurants.id = restaurant_waitlists.restaurant_id
             where restaurant_waitlists.guest_id = :guest_id
             order by restaurant_waitlists.id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function booking(int $id): void
    {
        RestaurantSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        $stmt = Database::connection()->prepare(
            'select restaurant_bookings.*, restaurants.name as restaurant_name, restaurants.address as restaurant_address
             from restaurant_bookings
             join restaurants on restaurants.id = restaurant_bookings.restaurant_id
             where restaurant_bookings.id = :id and restaurant_bookings.guest_id = :guest_id
             limit 1'
        );
        $stmt->execute(['id' => $id, 'guest_id' => $guestId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            Response::json(['message' => 'Booking not found'], 404);
            return;
        }
        Response::json(['data' => $booking]);
    }

    public function placeBooking(): void
    {
        RestaurantSchema::ensure();
        ZoneSchema::ensure();
        NotificationSchema::ensure();
        $body = Request::json();
        foreach (['guest_id', 'restaurant_id', 'customer_name', 'customer_phone', 'booking_date', 'booking_time'] as $field) {
            if (trim((string) ($body[$field] ?? '')) === '') {
                Response::json(['message' => 'Missing required field: ' . $field], 422);
                return;
            }
        }
        $restaurantId = (int) $body['restaurant_id'];
        $zoneId = max(0, (int) ($body['zone_id'] ?? $_GET['zone_id'] ?? 0));
        $restaurant = $this->restaurantRow($restaurantId);
        if (!$restaurant) {
            Response::json(['message' => 'Restaurant is not available'], 404);
            return;
        }
        if ($zoneId > 0 && !empty($restaurant['zone_id']) && (int) $restaurant['zone_id'] !== $zoneId) {
            Response::json(['message' => 'Restaurant is not available in selected zone'], 422);
            return;
        }
        $date = date('Y-m-d', strtotime((string) $body['booking_date']));
        $time = trim((string) $body['booking_time']);
        $partySize = max(1, (int) ($body['party_size'] ?? 2));
        $paymentMethod = trim((string) ($body['payment_method'] ?? 'pay_at_restaurant'));
        $allowed = array_map(static fn (array $method): string => $method['id'], $this->paymentMethods());
        if (!in_array($paymentMethod, $allowed, true)) {
            Response::json(['message' => 'Selected payment method is not available'], 422);
            return;
        }
        $paymentReference = trim((string) ($body['payment_reference'] ?? ''));
        if (PaymentMethodCatalog::requiresReference($paymentMethod) && $paymentReference === '') {
            Response::json(['message' => 'Payment reference is required for this method'], 422);
            return;
        }
        $paymentStatus = $paymentMethod === 'pay_at_restaurant' ? 'unpaid' : PaymentMethodCatalog::paymentStatus($paymentMethod);
        $amount = max(0, (float) ($body['amount'] ?? 0));
        $bookingNumber = 'CRT' . date('ymdHis') . random_int(100, 999);
        $db = Database::connection();
        try {
            $db->beginTransaction();
            $table = $this->availableTable($restaurantId, $date, $time, $partySize, true);
            if (!$table) {
                $db->rollBack();
                Response::json(['message' => 'Selected slot is no longer available'], 409);
                return;
            }
            $stmt = $db->prepare(
                'insert into restaurant_bookings
                 (booking_number, guest_id, restaurant_id, table_id, zone_id, customer_name, customer_phone, customer_email, booking_date, booking_time, party_size, special_request, booking_status, payment_method, payment_status, amount, payment_reference, payment_note, created_at, updated_at)
                 values
                 (:booking_number, :guest_id, :restaurant_id, :table_id, :zone_id, :customer_name, :customer_phone, :customer_email, :booking_date, :booking_time, :party_size, :special_request, \'pending\', :payment_method, :payment_status, :amount, :payment_reference, :payment_note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $guestId = trim((string) $body['guest_id']);
            $customerName = trim((string) $body['customer_name']);
            $customerPhone = trim((string) $body['customer_phone']);
            $stmt->execute([
                'booking_number' => $bookingNumber,
                'guest_id' => $guestId,
                'restaurant_id' => $restaurantId,
                'table_id' => (int) $table['id'],
                'zone_id' => $zoneId > 0 ? $zoneId : null,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => trim((string) ($body['customer_email'] ?? '')) ?: null,
                'booking_date' => $date,
                'booking_time' => $time,
                'party_size' => $partySize,
                'special_request' => trim((string) ($body['special_request'] ?? '')) ?: null,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'amount' => $amount,
                'payment_reference' => $paymentReference === '' ? null : $paymentReference,
                'payment_note' => trim((string) ($body['payment_note'] ?? '')) ?: null,
            ]);
            $bookingId = (int) $db->lastInsertId();
            $this->recordPayment(
                $bookingId,
                $paymentMethod,
                $amount,
                $paymentReference,
                trim((string) ($body['payment_note'] ?? ''))
            );
            NotificationLog::record('admin', null, null, 'New restaurant booking', $bookingNumber . ' placed by ' . $customerName, $bookingId, 'restaurant');
            NotificationLog::record('customer', null, $guestId, 'Restaurant booking placed', 'Your restaurant booking ' . $bookingNumber . ' was placed successfully.', $bookingId, 'restaurant');
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::json(['message' => 'Restaurant booking could not be placed. Please try again.'], 500);
            return;
        }
        Response::json([
            'message' => 'Restaurant booking placed successfully',
            'data' => [
                'id' => $bookingId,
                'booking_number' => $bookingNumber,
                'booking_status' => 'pending',
            ],
        ], 201);
    }

    public function joinWaitlist(): void
    {
        RestaurantSchema::ensure();
        ZoneSchema::ensure();
        NotificationSchema::ensure();
        $body = Request::json();
        foreach (['guest_id', 'restaurant_id', 'customer_name', 'customer_phone', 'booking_date', 'booking_time'] as $field) {
            if (trim((string) ($body[$field] ?? '')) === '') {
                Response::json(['message' => 'Missing required field: ' . $field], 422);
                return;
            }
        }

        $restaurantId = (int) $body['restaurant_id'];
        $zoneId = max(0, (int) ($body['zone_id'] ?? $_GET['zone_id'] ?? 0));
        $restaurant = $this->restaurantRow($restaurantId);
        if (!$restaurant) {
            Response::json(['message' => 'Restaurant is not available'], 404);
            return;
        }
        if ($zoneId > 0 && !empty($restaurant['zone_id']) && (int) $restaurant['zone_id'] !== $zoneId) {
            Response::json(['message' => 'Restaurant is not available in selected zone'], 422);
            return;
        }

        $dateTime = strtotime((string) $body['booking_date']);
        if ($dateTime === false) {
            Response::json(['message' => 'Invalid booking date'], 422);
            return;
        }
        $date = date('Y-m-d', $dateTime);
        $time = trim((string) $body['booking_time']);
        $partySize = max(1, (int) ($body['party_size'] ?? 2));
        $guestId = trim((string) $body['guest_id']);
        $customerName = trim((string) $body['customer_name']);
        $customerPhone = trim((string) $body['customer_phone']);

        $existing = Database::connection()->prepare(
            'select id from restaurant_waitlists
             where guest_id = :guest_id
                and restaurant_id = :restaurant_id
                and booking_date = :booking_date
                and booking_time = :booking_time
                and party_size = :party_size
                and status in (\'pending\', \'contacted\')
             limit 1'
        );
        $existing->execute([
            'guest_id' => $guestId,
            'restaurant_id' => $restaurantId,
            'booking_date' => $date,
            'booking_time' => $time,
            'party_size' => $partySize,
        ]);
        if ($existing->fetch()) {
            Response::json(['message' => 'You are already on the waitlist for this slot.']);
            return;
        }

        $stmt = Database::connection()->prepare(
            'insert into restaurant_waitlists
             (guest_id, restaurant_id, zone_id, customer_name, customer_phone, customer_email, booking_date, booking_time, party_size, special_request, status, created_at, updated_at)
             values
             (:guest_id, :restaurant_id, :zone_id, :customer_name, :customer_phone, :customer_email, :booking_date, :booking_time, :party_size, :special_request, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'guest_id' => $guestId,
            'restaurant_id' => $restaurantId,
            'zone_id' => $zoneId > 0 ? $zoneId : null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => trim((string) ($body['customer_email'] ?? '')) ?: null,
            'booking_date' => $date,
            'booking_time' => $time,
            'party_size' => $partySize,
            'special_request' => trim((string) ($body['special_request'] ?? '')) ?: null,
        ]);
        $waitlistId = (int) Database::connection()->lastInsertId();
        NotificationLog::record('admin', null, null, 'Restaurant waitlist request', $customerName . ' requested a table at ' . ($restaurant['name'] ?? 'restaurant'), $waitlistId, 'restaurant');
        NotificationLog::record('customer', null, $guestId, 'Waitlist request received', 'We will contact you if a table opens for ' . ($restaurant['name'] ?? 'the restaurant') . '.', $waitlistId, 'restaurant');
        Response::json([
            'message' => 'Waitlist request sent. We will contact you if a table opens.',
            'data' => ['id' => $waitlistId, 'status' => 'pending'],
        ], 201);
    }

    public function cancelBooking(int $id): void
    {
        RestaurantSchema::ensure();
        $body = Request::json();
        $guestId = trim((string) ($body['guest_id'] ?? $_GET['guest_id'] ?? ''));
        $stmt = Database::connection()->prepare('select * from restaurant_bookings where id = :id and guest_id = :guest_id limit 1');
        $stmt->execute(['id' => $id, 'guest_id' => $guestId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            Response::json(['message' => 'Booking not found'], 404);
            return;
        }
        if (in_array($booking['booking_status'], ['completed', 'cancelled', 'cancel_requested', 'rejected', 'no_show'], true)) {
            Response::json(['message' => 'Booking cannot be cancelled now'], 422);
            return;
        }
        $update = Database::connection()->prepare(
            'update restaurant_bookings set booking_status = \'cancel_requested\', cancellation_reason = :reason, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $update->execute(['id' => $id, 'reason' => trim((string) ($body['reason'] ?? ''))]);
        NotificationLog::record('admin', null, null, 'Restaurant cancellation requested', 'Cancellation requested for ' . $booking['booking_number'], $id, 'restaurant');
        Response::json(['message' => 'Cancellation request sent to admin']);
    }

    public function review(int $restaurantId): void
    {
        RestaurantSchema::ensure();
        $body = Request::json();
        $rating = min(5, max(1, (int) ($body['rating'] ?? 5)));
        $stmt = Database::connection()->prepare(
            'insert into restaurant_reviews (restaurant_id, booking_id, guest_id, customer_name, rating, comment, status, created_at, updated_at)
             values (:restaurant_id, :booking_id, :guest_id, :customer_name, :rating, :comment, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'restaurant_id' => $restaurantId,
            'booking_id' => (int) ($body['booking_id'] ?? 0) ?: null,
            'guest_id' => trim((string) ($body['guest_id'] ?? '')) ?: null,
            'customer_name' => trim((string) ($body['customer_name'] ?? 'Customer')),
            'rating' => $rating,
            'comment' => trim((string) ($body['comment'] ?? '')),
        ]);
        $this->refreshRating($restaurantId);
        Response::json(['message' => 'Review submitted']);
    }

    private function configData(): array
    {
        return [
            'app_name' => Settings::moduleGet('restaurant', 'app_name', 'City Restaurants'),
            'currency_symbol' => Settings::moduleGet('restaurant', 'currency_symbol', '₹'),
            'maintenance_mode' => Settings::moduleBool('restaurant', 'maintenance_mode'),
            'maintenance_message' => Settings::moduleGet('restaurant', 'maintenance_message'),
            'latest_app_version' => Settings::moduleGet('restaurant', 'latest_app_version'),
            'force_update_version' => Settings::moduleGet('restaurant', 'force_update_version'),
            'zones' => ZoneSchema::active(),
            'booking_payments' => $this->paymentMethods(),
            'cms_pages' => [
                ['slug' => 'terms-conditions', 'title' => 'Terms & Conditions', 'content' => Settings::moduleGet('restaurant', 'terms_conditions')],
                ['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'content' => Settings::moduleGet('restaurant', 'privacy_policy')],
                ['slug' => 'cancellation-policy', 'title' => 'Cancellation Policy', 'content' => Settings::moduleGet('restaurant', 'cancellation_policy')],
            ],
        ];
    }

    private function categoriesData(): array
    {
        return Database::connection()->query('select * from restaurant_categories where status = 1 order by sort_order asc, id desc')->fetchAll();
    }

    private function foodCategoriesData(): array
    {
        return Database::connection()->query('select * from restaurant_food_categories where status = 1 order by sort_order asc, id asc')->fetchAll();
    }

    private function restaurantsData(string $where, array $params, int $limit): array
    {
        $stmt = Database::connection()->prepare(
            'select restaurants.*, restaurant_categories.name as category_name
             from restaurants
             left join restaurant_categories on restaurant_categories.id = restaurants.category_id
             where restaurants.status = 1 and ' . $where . ZoneSchema::inlineSql('restaurants') . '
             order by restaurants.is_featured desc, restaurants.rating desc, restaurants.id desc
             limit ' . $limit
        );
        $stmt->execute($params);
        return array_map([$this, 'formatRestaurant'], $stmt->fetchAll());
    }

    private function foodItemsData(string $where, array $params, int $limit): array
    {
        $stmt = Database::connection()->prepare(
            'select restaurant_food_items.*, restaurants.name as restaurant_name,
                    restaurants.delivery_time_minutes, restaurant_food_categories.name as category_name
             from restaurant_food_items
             join restaurants on restaurants.id = restaurant_food_items.restaurant_id
             left join restaurant_food_categories on restaurant_food_categories.id = restaurant_food_items.category_id
             where restaurant_food_items.status = 1
                and restaurants.status = 1
                and restaurants.delivery_enabled = 1
                and ' . $where . ZoneSchema::inlineSql('restaurants') . '
             order by restaurant_food_items.is_featured desc, restaurant_food_items.id desc
             limit ' . $limit
        );
        $stmt->execute($params);
        return array_map([$this, 'formatFoodItem'], $stmt->fetchAll());
    }

    private function restaurantRow(int $id): ?array
    {
        $stmt = Database::connection()->prepare('select * from restaurants where id = :id and status = 1 limit 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function tablesData(int $restaurantId): array
    {
        $stmt = Database::connection()->prepare('select * from restaurant_tables where restaurant_id = :restaurant_id and status = 1 order by capacity asc, sort_order asc, id asc');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        return $stmt->fetchAll();
    }

    private function reviewsData(int $restaurantId): array
    {
        $stmt = Database::connection()->prepare('select * from restaurant_reviews where restaurant_id = :restaurant_id and status = 1 order by id desc limit 20');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        return $stmt->fetchAll();
    }

    private function availableSlots(int $restaurantId, string $date, int $partySize): array
    {
        if ($this->isBlackout($restaurantId, $date)) {
            return [];
        }
        $weekday = (int) date('w', strtotime($date));
        $stmt = Database::connection()->prepare(
            'select * from restaurant_shifts where restaurant_id = :restaurant_id and day_of_week = :day_of_week and status = 1 order by start_time asc'
        );
        $stmt->execute(['restaurant_id' => $restaurantId, 'day_of_week' => $weekday]);
        $slots = [];
        foreach ($stmt->fetchAll() as $shift) {
            $interval = max(15, (int) $shift['slot_interval_minutes']);
            $cursor = strtotime($date . ' ' . $shift['start_time']);
            $end = strtotime($date . ' ' . $shift['end_time']);
            while ($cursor !== false && $end !== false && $cursor <= $end) {
                $time = date('H:i', $cursor);
                $table = $this->availableTable($restaurantId, $date, $time, $partySize);
                if ($table) {
                    $slots[] = [
                        'time' => $time,
                        'table_id' => (int) $table['id'],
                        'capacity' => (int) $table['capacity'],
                    ];
                }
                $cursor = strtotime('+' . $interval . ' minutes', $cursor);
            }
        }
        return $slots;
    }

    private function availableTable(int $restaurantId, string $date, string $time, int $partySize, bool $lock = false): ?array
    {
        $driver = Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $lockSql = $lock && $driver === 'mysql' ? ' for update' : '';
        $stmt = Database::connection()->prepare(
            'select * from restaurant_tables
             where restaurant_id = :restaurant_id and capacity >= :party_size and status = 1
             order by capacity asc, sort_order asc, id asc' . $lockSql
        );
        $stmt->execute(['restaurant_id' => $restaurantId, 'party_size' => $partySize]);
        foreach ($stmt->fetchAll() as $table) {
            $bookedStmt = Database::connection()->prepare(
                'select count(*) from restaurant_bookings
                 where table_id = :table_id
                    and booking_date = :booking_date
                    and booking_time = :booking_time
                    and booking_status in (\'pending\', \'confirmed\', \'seated\')'
            );
            $bookedStmt->execute(['table_id' => (int) $table['id'], 'booking_date' => $date, 'booking_time' => $time]);
            if ((int) $bookedStmt->fetchColumn() < (int) $table['table_count']) {
                return $table;
            }
        }
        return null;
    }

    private function isBlackout(int $restaurantId, string $date): bool
    {
        $stmt = Database::connection()->prepare(
            'select count(*) from restaurant_blackouts
             where status = 1
                and (restaurant_id is null or restaurant_id = :restaurant_id)
                and blackout_date <= :date
                and coalesce(end_date, blackout_date) >= :date'
        );
        $stmt->execute(['restaurant_id' => $restaurantId, 'date' => $date]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function formatRestaurant(array $restaurant): array
    {
        $restaurant['id'] = (int) $restaurant['id'];
        $restaurant['category_id'] = (int) ($restaurant['category_id'] ?? 0);
        $restaurant['zone_id'] = $restaurant['zone_id'] === null ? null : (int) $restaurant['zone_id'];
        $restaurant['average_cost'] = (float) $restaurant['average_cost'];
        $restaurant['rating'] = (float) $restaurant['rating'];
        $restaurant['review_count'] = (int) $restaurant['review_count'];
        $restaurant['delivery_enabled'] = (int) ($restaurant['delivery_enabled'] ?? 1);
        $restaurant['delivery_time_minutes'] = (int) ($restaurant['delivery_time_minutes'] ?? 30);
        $restaurant['delivery_fee'] = (float) ($restaurant['delivery_fee'] ?? 0);
        $restaurant['gallery'] = $this->decodeList($restaurant['gallery_json'] ?? null);
        return $restaurant;
    }

    private function formatFoodItem(array $item): array
    {
        $item['id'] = (int) $item['id'];
        $item['restaurant_id'] = (int) $item['restaurant_id'];
        $item['category_id'] = (int) ($item['category_id'] ?? 0);
        $item['price'] = (float) ($item['price'] ?? 0);
        $item['discount_price'] = $item['discount_price'] === null ? null : (float) $item['discount_price'];
        $item['selling_price'] = $item['discount_price'] !== null && (float) $item['discount_price'] > 0
            ? (float) $item['discount_price']
            : (float) $item['price'];
        $item['prep_time_minutes'] = (int) ($item['prep_time_minutes'] ?? 25);
        $item['is_veg'] = (int) ($item['is_veg'] ?? 0);
        $item['stock'] = (int) ($item['stock'] ?? 0);
        $item['is_featured'] = (int) ($item['is_featured'] ?? 0);
        return $item;
    }

    private function decodeList(?string $value): array
    {
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function refreshRating(int $restaurantId): void
    {
        $stmt = Database::connection()->prepare('select avg(rating) as rating, count(*) as total from restaurant_reviews where restaurant_id = :restaurant_id and status = 1');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        $row = $stmt->fetch();
        $update = Database::connection()->prepare('update restaurants set rating = :rating, review_count = :review_count where id = :id');
        $update->execute(['id' => $restaurantId, 'rating' => round((float) ($row['rating'] ?? 0), 2), 'review_count' => (int) ($row['total'] ?? 0)]);
    }

    private function paymentMethods(): array
    {
        $methods = [[
            'id' => 'pay_at_restaurant',
            'title' => 'Pay At Restaurant',
            'description' => 'Pay after dining at the restaurant.',
            'requires_reference' => false,
        ]];
        foreach (PaymentMethodCatalog::enabled('restaurant') as $method) {
            if ($method['id'] !== 'cash_on_delivery') {
                $methods[] = $method;
            }
        }
        return $methods;
    }

    private function recordPayment(int $bookingId, string $method, float $amount, string $reference, string $note): void
    {
        $status = $method === 'pay_at_restaurant' ? 'pending' : 'pending_verification';
        Database::connection()->prepare(
            'insert into restaurant_payment_transactions
             (booking_id, payment_method, amount, reference, note, status, created_at, updated_at)
             values (:booking_id, :payment_method, :amount, :reference, :note, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        )->execute([
            'booking_id' => $bookingId,
            'payment_method' => $method,
            'amount' => $amount,
            'reference' => $reference === '' ? null : $reference,
            'note' => $note === '' ? null : $note,
            'status' => $status,
        ]);
    }
}
