<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\HotelSchema;
use App\Support\NotificationLog;
use App\Support\PaymentGatewayClient;
use App\Support\Request;
use App\Support\Security;
use App\Support\Settings;
use App\Support\Upload;
use App\Support\View;
use App\Support\WalletService;
use App\Support\ZoneSchema;

final class HotelController
{
    public function index(): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        ZoneSchema::ensure();
        $db = Database::connection();
        $hotels = $db->prepare(
            'select hotels.*, hotel_categories.name as category_name, hotel_owners.name as owner_name, zones.name as zone_name
             from hotels
             left join hotel_categories on hotel_categories.id = hotels.category_id
             left join hotel_owners on hotel_owners.id = hotels.owner_id
             left join zones on zones.id = hotels.zone_id
             where 1 = 1' . Auth::zoneWhere('hotels') . '
             order by hotels.id desc'
        );
        $hotels->execute(Auth::zoneParams());
        $rooms = $db->prepare(
            'select hotel_rooms.*, hotels.name as hotel_name
             from hotel_rooms
             join hotels on hotels.id = hotel_rooms.hotel_id
             where 1 = 1' . Auth::zoneWhere('hotels') . '
             order by hotel_rooms.id desc'
        );
        $rooms->execute(Auth::zoneParams());
        $bookings = $db->prepare(
            'select hotel_bookings.*, hotels.name as hotel_name, hotel_rooms.name as room_name
             from hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             join hotel_rooms on hotel_rooms.id = hotel_bookings.room_id
             where 1 = 1' . Auth::zoneWhere('hotel_bookings') . '
             order by hotel_bookings.id desc
             limit 80'
        );
        $bookings->execute(Auth::zoneParams());
        $blackouts = $db->prepare(
            'select hotel_blackouts.*, hotels.name as hotel_name, hotel_rooms.name as room_name
             from hotel_blackouts
             left join hotels on hotels.id = hotel_blackouts.hotel_id
             left join hotel_rooms on hotel_rooms.id = hotel_blackouts.room_id
             where 1 = 1' . Auth::zoneWhere('hotels') . '
             order by hotel_blackouts.id desc'
        );
        $blackouts->execute(Auth::zoneParams());
        View::render('admin/hotels', [
            'title' => 'Hotels',
            'categories' => $db->query('select * from hotel_categories order by sort_order asc, id desc')->fetchAll(),
            'owners' => $db->query('select * from hotel_owners order by id desc')->fetchAll(),
            'zones' => $this->zones(),
            'hotels' => $hotels->fetchAll(),
            'rooms' => $rooms->fetchAll(),
            'bookings' => $bookings->fetchAll(),
            'blackouts' => $blackouts->fetchAll(),
        ]);
    }

    public function storeCategory(): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        ZoneSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into hotel_categories (name, description, status, sort_order, created_at, updated_at)
             values (:name, :description, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'name' => trim((string) Request::input('name')),
            'description' => trim((string) Request::input('description')),
            'status' => (int) Request::input('status', 1),
            'sort_order' => (int) Request::input('sort_order', 0),
        ]);
        $this->back('/admin/hotels#categories');
    }

    public function updateCategory(int $id): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $stmt = Database::connection()->prepare(
            'update hotel_categories set name = :name, description = :description, status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => trim((string) Request::input('name')),
            'description' => trim((string) Request::input('description')),
            'status' => (int) Request::input('status', 1),
            'sort_order' => (int) Request::input('sort_order', 0),
        ]);
        $this->back('/admin/hotels#categories');
    }

    public function storeOwner(): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $password = trim((string) Request::input('password'));
        $stmt = Database::connection()->prepare(
            'insert into hotel_owners (name, phone, email, password, status, created_at, updated_at)
             values (:name, :phone, :email, :password, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'name' => trim((string) Request::input('name')),
            'phone' => trim((string) Request::input('phone')),
            'email' => trim((string) Request::input('email')),
            'password' => $password === '' ? null : password_hash($password, PASSWORD_DEFAULT),
            'status' => (int) Request::input('status', 1),
        ]);
        $this->back('/admin/hotels#owners');
    }

    public function updateOwner(int $id): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $fields = 'name = :name, phone = :phone, email = :email, status = :status, updated_at = CURRENT_TIMESTAMP';
        $params = [
            'id' => $id,
            'name' => trim((string) Request::input('name')),
            'phone' => trim((string) Request::input('phone')),
            'email' => trim((string) Request::input('email')),
            'status' => (int) Request::input('status', 1),
        ];
        $password = trim((string) Request::input('password'));
        if ($password !== '') {
            $fields .= ', password = :password';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $stmt = Database::connection()->prepare('update hotel_owners set ' . $fields . ' where id = :id');
        $stmt->execute($params);
        $this->back('/admin/hotels#owners');
    }

    public function storeHotel(): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $thumbnail = Upload::image('thumbnail', 'hotels');
        $gallery = Upload::images('gallery', 'hotels');
        $stmt = Database::connection()->prepare(
            'insert into hotels
             (zone_id, owner_id, category_id, name, city, area, address, description, star_rating, amenities_json, thumbnail, gallery_json, status, is_featured, created_at, updated_at)
             values
             (:zone_id, :owner_id, :category_id, :name, :city, :area, :address, :description, :star_rating, :amenities_json, :thumbnail, :gallery_json, :status, :is_featured, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->hotelParams($thumbnail, $gallery));
        $this->back('/admin/hotels#hotels');
    }

    public function updateHotel(int $id): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        ZoneSchema::ensure();
        $current = $this->find('hotels', $id);
        if (!$current) {
            $this->back('/admin/hotels#hotels');
        }
        $thumbnail = Upload::image('thumbnail', 'hotels') ?: ($current['thumbnail'] ?? null);
        $gallery = Upload::images('gallery', 'hotels');
        if ($gallery === []) {
            $gallery = json_decode((string) ($current['gallery_json'] ?? '[]'), true) ?: [];
        }
        $params = $this->hotelParams($thumbnail, $gallery);
        $params['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update hotels set zone_id = :zone_id, owner_id = :owner_id, category_id = :category_id, name = :name, city = :city, area = :area,
                address = :address, description = :description, star_rating = :star_rating, amenities_json = :amenities_json,
                thumbnail = :thumbnail, gallery_json = :gallery_json, status = :status, is_featured = :is_featured,
                updated_at = CURRENT_TIMESTAMP
             where id = :id' . Auth::zoneWhere('hotels')
        );
        $stmt->execute(Auth::zoneParams($params));
        $this->back('/admin/hotels#hotels');
    }

    public function storeRoom(): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $thumbnail = Upload::image('thumbnail', 'hotel-rooms');
        $stmt = Database::connection()->prepare(
            'insert into hotel_rooms
             (hotel_id, name, description, capacity_adults, capacity_children, total_rooms, price_per_night, discount_price, tax_percent, amenities_json, thumbnail, status, created_at, updated_at)
             values
             (:hotel_id, :name, :description, :capacity_adults, :capacity_children, :total_rooms, :price_per_night, :discount_price, :tax_percent, :amenities_json, :thumbnail, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->roomParams($thumbnail));
        $this->back('/admin/hotels#rooms');
    }

    public function updateRoom(int $id): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $current = $this->find('hotel_rooms', $id);
        if (!$current) {
            $this->back('/admin/hotels#rooms');
        }
        $thumbnail = Upload::image('thumbnail', 'hotel-rooms') ?: ($current['thumbnail'] ?? null);
        $params = $this->roomParams($thumbnail);
        $params['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update hotel_rooms set hotel_id = :hotel_id, name = :name, description = :description,
                capacity_adults = :capacity_adults, capacity_children = :capacity_children, total_rooms = :total_rooms,
                price_per_night = :price_per_night, discount_price = :discount_price, tax_percent = :tax_percent,
                amenities_json = :amenities_json, thumbnail = :thumbnail, status = :status, updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute($params);
        $this->back('/admin/hotels#rooms');
    }

    public function bookingStatus(int $id): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $status = trim((string) Request::input('booking_status', 'pending'));
        $paymentStatus = trim((string) Request::input('payment_status', 'unpaid'));
        $completedSql = $status === 'completed' ? ', completed_at = CURRENT_TIMESTAMP' : '';
        $cancelledSql = in_array($status, ['cancelled', 'rejected'], true) ? ', cancelled_at = CURRENT_TIMESTAMP' : '';
        $db = Database::connection();
        $lookup = $db->prepare('select * from hotel_bookings where id = :id' . Auth::zoneWhere('hotel_bookings') . ' limit 1');
        $lookup->execute(Auth::zoneParams(['id' => $id]));
        $booking = $lookup->fetch();
        if (!$booking) {
            $this->back('/admin/hotels#bookings');
        }
        $stmt = $db->prepare(
            'update hotel_bookings set booking_status = :booking_status, payment_status = :payment_status, admin_note = :admin_note' . $completedSql . $cancelledSql . ', updated_at = CURRENT_TIMESTAMP where id = :id' . Auth::zoneWhere('hotel_bookings')
        );
        $stmt->execute(Auth::zoneParams([
            'id' => $id,
            'booking_status' => $status,
            'payment_status' => $paymentStatus,
            'admin_note' => trim((string) Request::input('admin_note')),
        ]));
        $db->prepare('update hotel_payment_transactions set status = :status, updated_at = CURRENT_TIMESTAMP where booking_id = :booking_id')
            ->execute(['booking_id' => $id, 'status' => $paymentStatus === 'paid' ? 'paid' : ($paymentStatus === 'refunded' ? 'refunded' : 'pending')]);
        $refundAlreadyHandled = !in_array((string) ($booking['refund_status'] ?? 'none'), ['', 'none'], true);
        if (
            in_array($status, ['cancelled', 'rejected'], true)
            && !$refundAlreadyHandled
            && in_array((string) ($booking['payment_status'] ?? ''), ['paid', 'pending_verification'], true)
        ) {
            $refundAmount = $this->hotelRefundAmount($booking);
            $gatewayResult = null;
            $payment = $db->prepare('select * from hotel_payment_transactions where booking_id = :booking_id order by id desc limit 1');
            $payment->execute(['booking_id' => $id]);
            $paymentRow = $payment->fetch() ?: [];
            if (($booking['payment_method'] ?? '') === 'online_payment') {
                $gatewayResult = PaymentGatewayClient::refund('hotel', [
                    'booking_id' => $id,
                    'refund_amount' => $refundAmount,
                    'cancellation_reason' => $booking['cancellation_reason'] ?? '',
                ], $paymentRow + $booking);
            }
            $refundStatus = $refundAmount > 0
                ? ((($gatewayResult['ok'] ?? false) || ($booking['payment_method'] ?? '') !== 'online_payment') ? 'refunded' : 'refund_pending')
                : 'not_eligible';
            $refundNote = $refundAmount > 0
                ? 'Policy refund calculated on cancellation. ' . ($gatewayResult === null ? '' : 'Gateway: ' . ($gatewayResult['message'] ?? 'processed'))
                : 'No refund due by cancellation policy.';
            $db->prepare(
                'update hotel_bookings
                 set refund_status = :refund_status, refund_amount = :refund_amount, refund_note = :refund_note,
                     payment_status = :payment_status, updated_at = CURRENT_TIMESTAMP
                 where id = :id' . Auth::zoneWhere('hotel_bookings')
            )->execute(Auth::zoneParams([
                'id' => $id,
                'refund_status' => $refundStatus,
                'refund_amount' => $refundAmount,
                'refund_note' => trim($refundNote),
                'payment_status' => $refundStatus === 'refunded' ? 'refunded' : $paymentStatus,
            ]));
            if ($refundStatus === 'refunded' && !empty($booking['guest_id'])) {
                WalletService::credit(
                    'customer',
                    (string) $booking['guest_id'],
                    $refundAmount,
                    'hotel_refund_credit',
                    'hotel-booking:' . $id,
                    'Hotel refund for ' . $booking['booking_number']
                );
            }
            $db->prepare(
                'update hotel_payment_transactions
                 set status = :status, note = :note, gateway_response = :gateway_response,
                     reconciled_at = CURRENT_TIMESTAMP, reconciled_by = :reconciled_by,
                     updated_at = CURRENT_TIMESTAMP
                 where booking_id = :booking_id'
            )
                ->execute([
                    'booking_id' => $id,
                    'status' => $refundStatus === 'refunded' ? 'refunded' : 'pending',
                    'note' => trim($refundNote),
                    'gateway_response' => $gatewayResult === null ? ($paymentRow['gateway_response'] ?? null) : json_encode($gatewayResult, JSON_UNESCAPED_SLASHES),
                    'reconciled_by' => $_SESSION['admin_name'] ?? 'Admin',
                ]);
        }
        NotificationLog::record(
            'customer',
            null,
            (string) ($booking['guest_id'] ?? ''),
            'Hotel booking updated',
            'Your booking ' . $booking['booking_number'] . ' is now ' . $status . '.',
            $id,
            'hotel'
        );
        $this->back('/admin/hotels#bookings');
    }

    public function storeBlackout(): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $startsAt = trim((string) Request::input('starts_at'));
        $endsAt = trim((string) Request::input('ends_at', $startsAt));
        if ($startsAt === '') {
            $this->back('/admin/hotels#blackouts');
        }
        if ($endsAt === '' || strtotime($endsAt) < strtotime($startsAt)) {
            $endsAt = $startsAt;
        }
        $stmt = Database::connection()->prepare(
            'insert into hotel_blackouts (hotel_id, room_id, title, starts_at, ends_at, repeat_type, status, created_at, updated_at)
             values (:hotel_id, :room_id, :title, :starts_at, :ends_at, :repeat_type, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'hotel_id' => $this->hotelId() ?: null,
            'room_id' => (int) Request::input('room_id', 0) ?: null,
            'title' => trim((string) Request::input('title', 'Blocked dates')),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'repeat_type' => in_array(Request::input('repeat_type', 'none'), ['none', 'yearly'], true) ? Request::input('repeat_type', 'none') : 'none',
            'status' => (int) Request::input('status', 1),
        ]);
        $this->back('/admin/hotels#blackouts');
    }

    public function updateBlackout(int $id): void
    {
        Auth::requireAdmin();
        HotelSchema::ensure();
        $startsAt = trim((string) Request::input('starts_at'));
        $endsAt = trim((string) Request::input('ends_at', $startsAt));
        if ($endsAt === '' || strtotime($endsAt) < strtotime($startsAt)) {
            $endsAt = $startsAt;
        }
        if (!$this->blackoutAccessible($id)) {
            $this->back('/admin/hotels#blackouts');
        }
        $stmt = Database::connection()->prepare(
            'update hotel_blackouts set hotel_id = :hotel_id, room_id = :room_id, title = :title, starts_at = :starts_at, ends_at = :ends_at, repeat_type = :repeat_type, status = :status, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'hotel_id' => $this->hotelId() ?: null,
            'room_id' => (int) Request::input('room_id', 0) ?: null,
            'title' => trim((string) Request::input('title', 'Blocked dates')),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'repeat_type' => in_array(Request::input('repeat_type', 'none'), ['none', 'yearly'], true) ? Request::input('repeat_type', 'none') : 'none',
            'status' => (int) Request::input('status', 1),
        ]);
        $this->back('/admin/hotels#blackouts');
    }

    private function hotelParams(?string $thumbnail, array $gallery): array
    {
        return [
            'owner_id' => (int) Request::input('owner_id', 0) ?: null,
            'zone_id' => $this->zoneId(),
            'category_id' => (int) Request::input('category_id', 0) ?: null,
            'name' => trim((string) Request::input('name')),
            'city' => trim((string) Request::input('city')),
            'area' => trim((string) Request::input('area')),
            'address' => trim((string) Request::input('address')),
            'description' => trim((string) Request::input('description')),
            'star_rating' => (float) Request::input('star_rating', 0),
            'amenities_json' => json_encode($this->lines('amenities')),
            'thumbnail' => $thumbnail,
            'gallery_json' => json_encode($gallery),
            'status' => (int) Request::input('status', 1),
            'is_featured' => (int) Request::input('is_featured', 0),
        ];
    }

    private function roomParams(?string $thumbnail): array
    {
        return [
            'hotel_id' => $this->hotelId(),
            'name' => trim((string) Request::input('name')),
            'description' => trim((string) Request::input('description')),
            'capacity_adults' => max(1, (int) Request::input('capacity_adults', 2)),
            'capacity_children' => max(0, (int) Request::input('capacity_children', 0)),
            'total_rooms' => max(1, (int) Request::input('total_rooms', 1)),
            'price_per_night' => (float) Request::input('price_per_night', 0),
            'discount_price' => trim((string) Request::input('discount_price')) === '' ? null : (float) Request::input('discount_price'),
            'tax_percent' => (float) Request::input('tax_percent', 0),
            'amenities_json' => json_encode($this->lines('room_amenities')),
            'thumbnail' => $thumbnail,
            'status' => (int) Request::input('status', 1),
        ];
    }

    private function hotelRefundAmount(array $booking): float
    {
        $total = (float) ($booking['grand_total'] ?? 0);
        if ($total <= 0) {
            return 0.0;
        }
        $checkIn = strtotime((string) ($booking['check_in'] ?? ''));
        if ($checkIn === false) {
            return 0.0;
        }
        $hoursBeforeCheckIn = ($checkIn - time()) / 3600;
        $freeHours = Settings::float('hotel_cancellation_free_hours', 24);
        if ($hoursBeforeCheckIn >= $freeHours) {
            return round($total, 2);
        }

        $latePercent = Settings::float('hotel_late_cancellation_refund_percent', 50);
        return round(($total * max(0, min(100, $latePercent))) / 100, 2);
    }

    private function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) Request::input($key, '')) ?: [])));
    }

    private function find(string $table, int $id): array
    {
        $zoneSql = '';
        if ($table === 'hotels') {
            $zoneSql = Auth::zoneWhere('hotels');
        }
        if ($table === 'hotel_rooms') {
            $table = 'hotel_rooms join hotels on hotels.id = hotel_rooms.hotel_id';
            $zoneSql = Auth::zoneWhere('hotels');
        }
        $stmt = Database::connection()->prepare('select * from ' . $table . ' where ' . ($table === 'hotels' ? 'id' : 'hotel_rooms.id') . ' = :id' . $zoneSql . ' limit 1');
        $stmt->execute(Auth::zoneParams(['id' => $id]));
        return $stmt->fetch() ?: [];
    }

    private function zoneId(): ?int
    {
        if (Auth::isZoneScoped()) {
            return Auth::zoneId();
        }
        $id = (int) Request::input('zone_id', 0);
        return $id > 0 ? $id : null;
    }

    private function hotelId(): int
    {
        $id = (int) Request::input('hotel_id', 0);
        if (!Auth::isZoneScoped()) {
            return $id;
        }
        $stmt = Database::connection()->prepare('select id from hotels where id = :id' . Auth::zoneWhere('hotels') . ' limit 1');
        $stmt->execute(Auth::zoneParams(['id' => $id]));
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function zones(): array
    {
        if (!Auth::isZoneScoped()) {
            return ZoneSchema::active();
        }
        $stmt = Database::connection()->prepare('select * from zones where id = :id and status = 1 limit 1');
        $stmt->execute(['id' => Auth::zoneId()]);
        $zone = $stmt->fetch();
        return $zone ? [$zone] : [];
    }

    private function blackoutAccessible(int $id): bool
    {
        if (!Auth::isZoneScoped()) {
            return true;
        }
        $stmt = Database::connection()->prepare(
            'select hotel_blackouts.id
             from hotel_blackouts
             left join hotels on hotels.id = hotel_blackouts.hotel_id
             where hotel_blackouts.id = :id' . Auth::zoneWhere('hotels') . '
             limit 1'
        );
        $stmt->execute(Auth::zoneParams(['id' => $id]));
        return (bool) $stmt->fetchColumn();
    }

    private function back(string $fallback): void
    {
        header('Location: ' . Security::safeRedirect((string) ($_SERVER['HTTP_REFERER'] ?? ''), $fallback));
        exit;
    }
}
