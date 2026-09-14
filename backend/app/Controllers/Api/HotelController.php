<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\HotelSchema;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\PaymentMethodCatalog;
use App\Support\Request;
use App\Support\Response;
use App\Support\Settings;
use App\Support\ZoneSchema;

final class HotelController
{
    public function config(): void
    {
        Response::json($this->configData());
    }

    public function home(): void
    {
        HotelSchema::ensure();
        ZoneSchema::ensure();
        Response::json([
            'config' => $this->configData(),
            'categories' => $this->categoriesData(),
            'featured_hotels' => $this->hotelsData('hotels.is_featured = 1', [], 10),
            'popular_hotels' => $this->hotelsData('1 = 1', [], 12),
        ]);
    }

    private function configData(): array
    {
        return [
            'app_name' => Settings::moduleGet('hotel', 'app_name', 'City Hotels'),
            'currency_symbol' => Settings::moduleGet('hotel', 'currency_symbol', '₹'),
            'maintenance_mode' => Settings::moduleBool('hotel', 'maintenance_mode'),
            'maintenance_message' => Settings::moduleGet('hotel', 'maintenance_message'),
            'latest_app_version' => Settings::moduleGet('hotel', 'latest_app_version'),
            'force_update_version' => Settings::moduleGet('hotel', 'force_update_version'),
            'zones' => ZoneSchema::active(),
            'booking_payments' => $this->paymentMethods(),
            'cancellation_policy' => [
                'free_hours_before_check_in' => (int) Settings::get('hotel_cancellation_free_hours', '24'),
                'late_refund_percent' => (float) Settings::get('hotel_late_cancellation_refund_percent', '50'),
            ],
            'cms_pages' => [
                [
                    'slug' => 'terms-conditions',
                    'title' => 'Terms & Conditions',
                    'content' => Settings::moduleGet('hotel', 'terms_conditions'),
                ],
                [
                    'slug' => 'privacy-policy',
                    'title' => 'Privacy Policy',
                    'content' => Settings::moduleGet('hotel', 'privacy_policy'),
                ],
                [
                    'slug' => 'refund-policy',
                    'title' => 'Refund Policy',
                    'content' => Settings::moduleGet('hotel', 'refund_policy'),
                ],
            ],
        ];
    }

    public function categories(): void
    {
        HotelSchema::ensure();
        Response::json(['data' => $this->categoriesData()]);
    }

    public function hotels(): void
    {
        HotelSchema::ensure();
        ZoneSchema::ensure();
        $where = '1 = 1';
        $params = [];
        $query = trim((string) ($_GET['query'] ?? ''));
        if ($query !== '') {
            $where .= ' and (hotels.name like :query or hotels.city like :query or hotels.area like :query)';
            $params['query'] = '%' . $query . '%';
        }
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where .= ' and hotels.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        $zoneId = ZoneSchema::requestZoneId();
        if ($zoneId > 0) {
            $where .= ' and (hotels.zone_id is null or hotels.zone_id = :zone_id)';
            $params['zone_id'] = $zoneId;
        }
        Response::json(['data' => $this->hotelsData($where, $params, 60)]);
    }

    public function show(int $id): void
    {
        HotelSchema::ensure();
        $db = Database::connection();
        $stmt = $db->prepare(
            'select hotels.*, hotel_categories.name as category_name
             from hotels
             left join hotel_categories on hotel_categories.id = hotels.category_id
             where hotels.id = :id and hotels.status = 1
             limit 1'
        );
        $stmt->execute(['id' => $id]);
        $hotel = $stmt->fetch();
        if (!$hotel) {
            Response::json(['message' => 'Hotel not found'], 404);
            return;
        }
        Response::json([
            'data' => $this->formatHotel($hotel),
            'rooms' => $this->roomsData($id),
            'reviews' => $this->reviewsData($id),
        ]);
    }

    public function rooms(int $hotelId): void
    {
        HotelSchema::ensure();
        Response::json(['data' => $this->roomsData($hotelId)]);
    }

    public function bookings(): void
    {
        HotelSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select hotel_bookings.*, hotels.name as hotel_name, hotel_rooms.name as room_name
             from hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             join hotel_rooms on hotel_rooms.id = hotel_bookings.room_id
             where hotel_bookings.guest_id = :guest_id
             order by hotel_bookings.id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function booking(int $id): void
    {
        HotelSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        $stmt = Database::connection()->prepare(
            'select hotel_bookings.*, hotels.name as hotel_name, hotels.address as hotel_address, hotel_rooms.name as room_name
             from hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             join hotel_rooms on hotel_rooms.id = hotel_bookings.room_id
             where hotel_bookings.id = :id and hotel_bookings.guest_id = :guest_id
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
        HotelSchema::ensure();
        ZoneSchema::ensure();
        NotificationSchema::ensure();
        $body = Request::json();
        foreach (['guest_id', 'room_id', 'customer_name', 'customer_phone', 'check_in', 'check_out'] as $field) {
            if (trim((string) ($body[$field] ?? '')) === '') {
                Response::json(['message' => 'Missing required field: ' . $field], 422);
                return;
            }
        }
        $roomId = (int) $body['room_id'];
        $zoneId = max(0, (int) ($body['zone_id'] ?? $_GET['zone_id'] ?? 0));
        $roomStmt = Database::connection()->prepare(
            'select hotel_rooms.*, hotels.name as hotel_name, hotels.status as hotel_status, hotels.zone_id
             from hotel_rooms
             join hotels on hotels.id = hotel_rooms.hotel_id
             where hotel_rooms.id = :id and hotel_rooms.status = 1
             limit 1'
        );
        $roomStmt->execute(['id' => $roomId]);
        $room = $roomStmt->fetch();
        if (!$room || (int) $room['hotel_status'] !== 1) {
            Response::json(['message' => 'Room is not available'], 404);
            return;
        }
        if ($zoneId > 0 && !empty($room['zone_id']) && (int) $room['zone_id'] !== $zoneId) {
            Response::json(['message' => 'Hotel is not serviceable in selected zone'], 422);
            return;
        }
        $checkIn = strtotime((string) $body['check_in']);
        $checkOut = strtotime((string) $body['check_out']);
        if ($checkIn === false || $checkOut === false || $checkOut <= $checkIn) {
            Response::json(['message' => 'Invalid stay dates'], 422);
            return;
        }
        $nights = max(1, (int) (($checkOut - $checkIn) / 86400));
        $rooms = max(1, (int) ($body['rooms'] ?? 1));
        if ($this->isBlackout((int) $room['hotel_id'], $roomId, date('Y-m-d', $checkIn), date('Y-m-d', $checkOut))) {
            Response::json(['message' => 'Selected dates are blocked for this hotel or room'], 422);
            return;
        }
        if (!$this->hasAvailability($roomId, date('Y-m-d', $checkIn), date('Y-m-d', $checkOut), $rooms)) {
            Response::json(['message' => 'Selected rooms are not available for these dates'], 422);
            return;
        }
        $rate = (float) ($room['discount_price'] ?? $room['price_per_night']);
        $roomTotal = $rate * $nights * $rooms;
        $taxTotal = round($roomTotal * ((float) $room['tax_percent'] / 100), 2);
        $grandTotal = $roomTotal + $taxTotal;
        $paymentMethod = trim((string) ($body['payment_method'] ?? 'pay_at_hotel'));
        $allowed = array_map(static fn (array $method): string => $method['id'], $this->paymentMethods());
        if (!in_array($paymentMethod, $allowed, true)) {
            Response::json(['message' => 'Selected payment method is not available'], 422);
            return;
        }
        $paymentReference = trim((string) ($body['payment_reference'] ?? ''));
        $paymentNote = trim((string) ($body['payment_note'] ?? ''));
        if (PaymentMethodCatalog::requiresReference($paymentMethod) && $paymentReference === '') {
            Response::json(['message' => 'Payment reference is required for this method'], 422);
            return;
        }
        $paymentStatus = $paymentMethod === 'pay_at_hotel' ? 'unpaid' : PaymentMethodCatalog::paymentStatus($paymentMethod);
        $bookingNumber = 'CHT' . date('ymdHis') . random_int(100, 999);
        $db = Database::connection();
        try {
            $db->beginTransaction();
            $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $lockStmt = $db->prepare('select id from hotel_rooms where id = :id limit 1' . ($driver === 'mysql' ? ' for update' : ''));
            $lockStmt->execute(['id' => $roomId]);
            if (!$this->hasAvailability($roomId, date('Y-m-d', $checkIn), date('Y-m-d', $checkOut), $rooms)) {
                $db->rollBack();
                Response::json(['message' => 'Selected rooms are not available for these dates'], 409);
                return;
            }
            $stmt = $db->prepare(
            'insert into hotel_bookings
             (booking_number, zone_id, hotel_id, room_id, guest_id, customer_name, customer_phone, customer_email, check_in, check_out, nights, rooms, adults, children, room_total, tax_total, grand_total, payment_method, payment_reference, payment_note, payment_status, booking_status, created_at, updated_at)
             values
             (:booking_number, :zone_id, :hotel_id, :room_id, :guest_id, :customer_name, :customer_phone, :customer_email, :check_in, :check_out, :nights, :rooms, :adults, :children, :room_total, :tax_total, :grand_total, :payment_method, :payment_reference, :payment_note, :payment_status, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $guestId = trim((string) $body['guest_id']);
            $customerName = trim((string) $body['customer_name']);
            $customerPhone = trim((string) $body['customer_phone']);
            $stmt->execute([
            'booking_number' => $bookingNumber,
            'zone_id' => $zoneId > 0 ? $zoneId : null,
            'hotel_id' => (int) $room['hotel_id'],
            'room_id' => $roomId,
            'guest_id' => $guestId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => trim((string) ($body['customer_email'] ?? '')) ?: null,
            'check_in' => date('Y-m-d', $checkIn),
            'check_out' => date('Y-m-d', $checkOut),
            'nights' => $nights,
            'rooms' => $rooms,
            'adults' => max(1, (int) ($body['adults'] ?? 1)),
            'children' => max(0, (int) ($body['children'] ?? 0)),
            'room_total' => $roomTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference === '' ? null : $paymentReference,
            'payment_note' => $paymentNote === '' ? null : $paymentNote,
            'payment_status' => $paymentStatus,
            ]);
            $bookingId = (int) $db->lastInsertId();
            $paymentStmt = $db->prepare(
            'insert into hotel_payment_transactions
             (booking_id, guest_id, customer_name, customer_phone, payment_method, amount, reference, note, status, created_at, updated_at)
             values (:booking_id, :guest_id, :customer_name, :customer_phone, :payment_method, :amount, :reference, :note, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $paymentStmt->execute([
            'booking_id' => $bookingId,
            'guest_id' => $guestId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'payment_method' => $paymentMethod,
            'amount' => $grandTotal,
            'reference' => $paymentReference === '' ? null : $paymentReference,
            'note' => $paymentNote === '' ? null : $paymentNote,
            'status' => $paymentStatus === 'paid' ? 'paid' : 'pending',
            ]);
            NotificationLog::record('admin', null, null, 'New hotel booking', $bookingNumber . ' placed by ' . $customerName, $bookingId, 'hotel');
            NotificationLog::record('customer', null, $guestId, 'Hotel booking placed', 'Your hotel booking ' . $bookingNumber . ' was placed successfully.', $bookingId, 'hotel');
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::json(['message' => 'Hotel booking could not be placed. Please try again.'], 500);
            return;
        }
        Response::json([
            'message' => 'Hotel booking placed successfully',
            'data' => [
                'id' => $bookingId,
                'booking_number' => $bookingNumber,
                'grand_total' => $grandTotal,
            ],
        ], 201);
    }

    public function cancelBooking(int $id): void
    {
        HotelSchema::ensure();
        $body = Request::json();
        $guestId = trim((string) ($body['guest_id'] ?? $_GET['guest_id'] ?? ''));
        $stmt = Database::connection()->prepare('select * from hotel_bookings where id = :id and guest_id = :guest_id limit 1');
        $stmt->execute(['id' => $id, 'guest_id' => $guestId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            Response::json(['message' => 'Booking not found'], 404);
            return;
        }
        if (in_array($booking['booking_status'], ['completed', 'cancelled', 'cancellation_requested'], true)) {
            Response::json(['message' => 'Booking cannot be cancelled now'], 422);
            return;
        }
        $update = Database::connection()->prepare(
            'update hotel_bookings set booking_status = \'cancellation_requested\', cancellation_reason = :reason, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $update->execute(['id' => $id, 'reason' => trim((string) ($body['reason'] ?? ''))]);
        NotificationLog::record('admin', null, null, 'Hotel cancellation requested', 'Cancellation requested for ' . $booking['booking_number'], $id, 'hotel');
        Response::json(['message' => 'Cancellation request sent to admin']);
    }

    public function review(int $hotelId): void
    {
        HotelSchema::ensure();
        $body = Request::json();
        $rating = min(5, max(1, (int) ($body['rating'] ?? 5)));
        $stmt = Database::connection()->prepare(
            'insert into hotel_reviews (hotel_id, booking_id, guest_id, customer_name, rating, comment, status, created_at, updated_at)
             values (:hotel_id, :booking_id, :guest_id, :customer_name, :rating, :comment, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'hotel_id' => $hotelId,
            'booking_id' => (int) ($body['booking_id'] ?? 0) ?: null,
            'guest_id' => trim((string) ($body['guest_id'] ?? '')) ?: null,
            'customer_name' => trim((string) ($body['customer_name'] ?? 'Customer')),
            'rating' => $rating,
            'comment' => trim((string) ($body['comment'] ?? '')),
        ]);
        $this->refreshRating($hotelId);
        Response::json(['message' => 'Review submitted']);
    }

    private function categoriesData(): array
    {
        return Database::connection()->query('select * from hotel_categories where status = 1 order by sort_order asc, id desc')->fetchAll();
    }

    private function hotelsData(string $where, array $params, int $limit): array
    {
        $stmt = Database::connection()->prepare(
            'select hotels.*, hotel_categories.name as category_name,
                    min(coalesce(hotel_rooms.discount_price, hotel_rooms.price_per_night)) as starting_price
             from hotels
             left join hotel_categories on hotel_categories.id = hotels.category_id
             left join hotel_rooms on hotel_rooms.hotel_id = hotels.id and hotel_rooms.status = 1
             where hotels.status = 1 and ' . $where . ZoneSchema::inlineSql('hotels') . '
             group by hotels.id, hotels.owner_id, hotels.category_id, hotels.name, hotels.city, hotels.area, hotels.address,
                      hotels.description, hotels.star_rating, hotels.rating, hotels.review_count, hotels.amenities_json,
                      hotels.thumbnail, hotels.gallery_json, hotels.status, hotels.is_featured, hotels.created_at,
                      hotels.updated_at, hotel_categories.name
             order by hotels.is_featured desc, hotels.rating desc, hotels.id desc
             limit ' . $limit
        );
        $stmt->execute($params);
        return array_map([$this, 'formatHotel'], $stmt->fetchAll());
    }

    private function roomsData(int $hotelId): array
    {
        $stmt = Database::connection()->prepare('select * from hotel_rooms where hotel_id = :hotel_id and status = 1 order by price_per_night asc, id desc');
        $stmt->execute(['hotel_id' => $hotelId]);
        return array_map([$this, 'formatRoom'], $stmt->fetchAll());
    }

    private function reviewsData(int $hotelId): array
    {
        $stmt = Database::connection()->prepare('select * from hotel_reviews where hotel_id = :hotel_id and status = 1 order by id desc limit 20');
        $stmt->execute(['hotel_id' => $hotelId]);
        return $stmt->fetchAll();
    }

    private function formatHotel(array $hotel): array
    {
        $hotel['id'] = (int) $hotel['id'];
        $hotel['category_id'] = (int) ($hotel['category_id'] ?? 0);
        $hotel['star_rating'] = (float) $hotel['star_rating'];
        $hotel['rating'] = (float) $hotel['rating'];
        $hotel['review_count'] = (int) $hotel['review_count'];
        $hotel['starting_price'] = (float) ($hotel['starting_price'] ?? 0);
        $hotel['amenities'] = $this->decodeList($hotel['amenities_json'] ?? null);
        $hotel['gallery'] = $this->decodeList($hotel['gallery_json'] ?? null);
        return $hotel;
    }

    private function formatRoom(array $room): array
    {
        $room['id'] = (int) $room['id'];
        $room['hotel_id'] = (int) $room['hotel_id'];
        $room['capacity_adults'] = (int) $room['capacity_adults'];
        $room['capacity_children'] = (int) $room['capacity_children'];
        $room['total_rooms'] = (int) $room['total_rooms'];
        $room['price_per_night'] = (float) $room['price_per_night'];
        $room['discount_price'] = $room['discount_price'] === null ? null : (float) $room['discount_price'];
        $room['tax_percent'] = (float) $room['tax_percent'];
        $room['amenities'] = $this->decodeList($room['amenities_json'] ?? null);
        return $room;
    }

    private function decodeList(?string $value): array
    {
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function hasAvailability(int $roomId, string $checkIn, string $checkOut, int $rooms): bool
    {
        $db = Database::connection();
        $roomStmt = $db->prepare('select total_rooms from hotel_rooms where id = :id limit 1');
        $roomStmt->execute(['id' => $roomId]);
        $totalRooms = (int) ($roomStmt->fetch()['total_rooms'] ?? 0);
        $stmt = $db->prepare(
            'select coalesce(sum(rooms), 0) as booked_rooms
             from hotel_bookings
             where room_id = :room_id
                and booking_status not in (\'cancelled\', \'rejected\')
                and check_in < :check_out
                and check_out > :check_in'
        );
        $stmt->execute(['room_id' => $roomId, 'check_in' => $checkIn, 'check_out' => $checkOut]);
        $booked = (int) ($stmt->fetch()['booked_rooms'] ?? 0);
        return ($booked + $rooms) <= $totalRooms;
    }

    private function isBlackout(int $hotelId, int $roomId, string $checkIn, string $checkOut): bool
    {
        $stmt = Database::connection()->prepare(
            'select * from hotel_blackouts
             where status = 1
                and (hotel_id is null or hotel_id = :hotel_id)
                and (room_id is null or room_id = :room_id)'
        );
        $stmt->execute(['hotel_id' => $hotelId, 'room_id' => $roomId]);
        $start = strtotime($checkIn);
        $end = strtotime($checkOut);
        foreach ($stmt->fetchAll() as $row) {
            $blackoutStart = strtotime((string) $row['starts_at']);
            $blackoutEnd = strtotime((string) $row['ends_at'] . ' +1 day');
            if (($row['repeat_type'] ?? 'none') === 'yearly') {
                $blackoutStart = strtotime(date('Y', $start) . '-' . date('m-d', strtotime((string) $row['starts_at'])));
                $blackoutEnd = strtotime(date('Y', $start) . '-' . date('m-d', strtotime((string) $row['ends_at'])) . ' +1 day');
            }
            if ($blackoutStart !== false && $blackoutEnd !== false && $start < $blackoutEnd && $end > $blackoutStart) {
                return true;
            }
        }
        return false;
    }

    private function refreshRating(int $hotelId): void
    {
        $stmt = Database::connection()->prepare('select avg(rating) as rating, count(*) as total from hotel_reviews where hotel_id = :hotel_id and status = 1');
        $stmt->execute(['hotel_id' => $hotelId]);
        $row = $stmt->fetch();
        $update = Database::connection()->prepare('update hotels set rating = :rating, review_count = :review_count where id = :id');
        $update->execute(['id' => $hotelId, 'rating' => round((float) ($row['rating'] ?? 0), 2), 'review_count' => (int) ($row['total'] ?? 0)]);
    }

    private function paymentMethods(): array
    {
        $methods = [[
            'id' => 'pay_at_hotel',
            'title' => 'Pay At Hotel',
            'description' => 'Pay during check-in at the hotel.',
            'requires_reference' => false,
        ]];
        foreach (PaymentMethodCatalog::enabled('hotel') as $method) {
            if ($method['id'] !== 'cash_on_delivery') {
                $methods[] = $method;
            }
        }
        return $methods;
    }
}
