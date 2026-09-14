<?php

declare(strict_types=1);

namespace App\Controllers\HotelOwner;

use App\Support\Database;
use App\Support\HotelOwnerAuth;
use App\Support\HotelSchema;
use App\Support\Response;
use App\Support\Settings;
use App\Support\View;
use App\Support\WalletService;
use App\Support\WalletSchema;

final class DashboardController
{
    public function index(): void
    {
        HotelOwnerAuth::requireOwner();
        HotelSchema::ensure();
        WalletSchema::ensure();
        $db = Database::connection();
        $ownerId = HotelOwnerAuth::id();
        $ownerKey = 'hotel-owner-' . $ownerId;

        $ownerStmt = $db->prepare('select * from hotel_owners where id = :id limit 1');
        $ownerStmt->execute(['id' => $ownerId]);
        $owner = $ownerStmt->fetch();

        $statsStmt = $db->prepare(
            'select count(hotel_bookings.id) as total_bookings,
                    coalesce(sum(hotel_bookings.grand_total), 0) as total_amount,
                    sum(case when hotel_bookings.booking_status in (\'pending\', \'confirmed\', \'checked_in\') then 1 else 0 end) as active_bookings,
                    sum(case when hotel_bookings.booking_status = \'completed\' then 1 else 0 end) as completed_bookings
             from hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             where hotels.owner_id = :owner_id'
        );
        $statsStmt->execute(['owner_id' => $ownerId]);

        $hotelsStmt = $db->prepare('select * from hotels where owner_id = :owner_id order by id desc');
        $hotelsStmt->execute(['owner_id' => $ownerId]);

        $roomsStmt = $db->prepare(
            'select hotel_rooms.*, hotels.name as hotel_name
             from hotel_rooms
             join hotels on hotels.id = hotel_rooms.hotel_id
             where hotels.owner_id = :owner_id
             order by hotel_rooms.id desc'
        );
        $roomsStmt->execute(['owner_id' => $ownerId]);

        $bookingsStmt = $db->prepare(
            'select hotel_bookings.*, hotels.name as hotel_name, hotel_rooms.name as room_name
             from hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             join hotel_rooms on hotel_rooms.id = hotel_bookings.room_id
             where hotels.owner_id = :owner_id
             order by hotel_bookings.id desc
             limit 100'
        );
        $bookingsStmt->execute(['owner_id' => $ownerId]);

        $blackoutsStmt = $db->prepare(
            'select hotel_blackouts.*, hotels.name as hotel_name, hotel_rooms.name as room_name
             from hotel_blackouts
             join hotels on hotels.id = hotel_blackouts.hotel_id
             left join hotel_rooms on hotel_rooms.id = hotel_blackouts.room_id
             where hotels.owner_id = :owner_id
             order by hotel_blackouts.id desc'
        );
        $blackoutsStmt->execute(['owner_id' => $ownerId]);

        $reportStmt = $db->prepare(
            'select booking_status, count(*) as total_bookings, coalesce(sum(grand_total), 0) as total_amount
             from hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             where hotels.owner_id = :owner_id
             group by booking_status
             order by total_bookings desc'
        );
        $reportStmt->execute(['owner_id' => $ownerId]);
        $wallet = WalletService::summary('hotel_owner', $ownerKey);
        $withdrawals = $db->prepare(
            'select * from withdrawal_requests
             where owner_type = :owner_type and owner_key = :owner_key
             order by id desc'
        );
        $withdrawals->execute([
            'owner_type' => 'hotel_owner',
            'owner_key' => $ownerKey,
        ]);
        $payoutSummaryStmt = $db->prepare(
            'select
                coalesce(sum(case when booking_status = \'completed\' then grand_total else 0 end), 0) as completed_gross,
                count(case when booking_status = \'completed\' then 1 end) as completed_count
             from hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             where hotels.owner_id = :owner_id'
        );
        $payoutSummaryStmt->execute(['owner_id' => $ownerId]);

        View::render('hotel_owner/dashboard', [
            'title' => 'Hotel Owner Dashboard',
            'owner' => $owner,
            'stats' => $statsStmt->fetch(),
            'hotels' => $hotelsStmt->fetchAll(),
            'rooms' => $roomsStmt->fetchAll(),
            'bookings' => $bookingsStmt->fetchAll(),
            'blackouts' => $blackoutsStmt->fetchAll(),
            'reports' => $reportStmt->fetchAll(),
            'walletAccount' => $wallet['account'],
            'walletLedger' => $wallet['ledger'],
            'withdrawals' => $withdrawals->fetchAll(),
            'payoutSummary' => $payoutSummaryStmt->fetch(),
            'commissionPercent' => Settings::float('hotel_owner_commission_percent', 10),
        ]);
    }

    public function profile(): void
    {
        HotelOwnerAuth::requireOwner();
        HotelSchema::ensure();
        $password = trim((string) ($_POST['password'] ?? ''));
        $passwordSql = $password === '' ? '' : ', password = :password';
        $params = [
            'id' => HotelOwnerAuth::id(),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'email' => trim((string) ($_POST['email'] ?? '')) ?: null,
        ];
        if ($password !== '') {
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        Database::connection()->prepare(
            'update hotel_owners set name = :name, phone = :phone, email = :email' . $passwordSql . ', updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute($params);
        $_SESSION['hotel_owner_name'] = $params['name'];
        Response::redirect('/hotel-owner');
    }

    public function updateRoom(int $id): void
    {
        HotelOwnerAuth::requireOwner();
        HotelSchema::ensure();
        Database::connection()->prepare(
            'update hotel_rooms
             join hotels on hotels.id = hotel_rooms.hotel_id
             set hotel_rooms.total_rooms = :total_rooms,
                 hotel_rooms.price_per_night = :price_per_night,
                 hotel_rooms.discount_price = :discount_price,
                 hotel_rooms.tax_percent = :tax_percent,
                 hotel_rooms.status = :status,
                 hotel_rooms.updated_at = CURRENT_TIMESTAMP
             where hotel_rooms.id = :id and hotels.owner_id = :owner_id'
        )->execute([
            'id' => $id,
            'owner_id' => HotelOwnerAuth::id(),
            'total_rooms' => max(0, (int) ($_POST['total_rooms'] ?? 0)),
            'price_per_night' => max(0, (float) ($_POST['price_per_night'] ?? 0)),
            'discount_price' => trim((string) ($_POST['discount_price'] ?? '')) === '' ? null : max(0, (float) $_POST['discount_price']),
            'tax_percent' => min(100, max(0, (float) ($_POST['tax_percent'] ?? 0))),
            'status' => isset($_POST['status']) ? 1 : 0,
        ]);
        Response::redirect('/hotel-owner#rooms');
    }

    public function status(int $id): void
    {
        HotelOwnerAuth::requireOwner();
        HotelSchema::ensure();
        $allowed = ['confirmed', 'checked_in', 'completed', 'cancellation_requested'];
        $status = trim((string) ($_POST['booking_status'] ?? 'confirmed'));
        if (!in_array($status, $allowed, true)) {
            $status = 'confirmed';
        }
        $db = Database::connection();
        $lookup = $db->prepare(
            'select hotel_bookings.*, hotels.owner_id
             from hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             where hotel_bookings.id = :id and hotels.owner_id = :owner_id
             limit 1'
        );
        $lookup->execute(['id' => $id, 'owner_id' => HotelOwnerAuth::id()]);
        $booking = $lookup->fetch();
        if (!$booking) {
            Response::redirect('/hotel-owner');
            return;
        }

        $completedSql = $status === 'completed' ? ', hotel_bookings.completed_at = CURRENT_TIMESTAMP' : '';
        $db->prepare(
            'update hotel_bookings
             join hotels on hotels.id = hotel_bookings.hotel_id
             set hotel_bookings.booking_status = :status' . $completedSql . ', hotel_bookings.updated_at = CURRENT_TIMESTAMP
             where hotel_bookings.id = :id and hotels.owner_id = :owner_id'
        )->execute([
            'id' => $id,
            'owner_id' => HotelOwnerAuth::id(),
            'status' => $status,
        ]);
        if ($status === 'completed') {
            $this->creditCompletedBooking($booking);
        }
        Response::redirect('/hotel-owner');
    }

    public function withdrawal(): void
    {
        HotelOwnerAuth::requireOwner();
        WalletSchema::ensure();
        $ownerKey = 'hotel-owner-' . HotelOwnerAuth::id();
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        if ($amount <= 0 || !WalletService::canDebit('hotel_owner', $ownerKey, $amount)) {
            Response::redirect('/hotel-owner#wallet');
            return;
        }
        Database::connection()->prepare(
            'insert into withdrawal_requests
             (vendor_id, owner_type, owner_key, amount, bank_details, note, status, created_at, updated_at)
             values (0, :owner_type, :owner_key, :amount, :bank_details, :note, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        )->execute([
            'owner_type' => 'hotel_owner',
            'owner_key' => $ownerKey,
            'amount' => $amount,
            'bank_details' => trim((string) ($_POST['bank_details'] ?? '')) ?: null,
            'note' => trim((string) ($_POST['note'] ?? '')) ?: null,
        ]);
        Response::redirect('/hotel-owner#wallet');
    }

    public function storeBlackout(): void
    {
        HotelOwnerAuth::requireOwner();
        HotelSchema::ensure();
        $hotelId = (int) ($_POST['hotel_id'] ?? 0);
        $roomId = (int) ($_POST['room_id'] ?? 0);
        if (!$this->ownsHotel($hotelId) || ($roomId > 0 && !$this->ownsRoom($roomId, $hotelId))) {
            Response::redirect('/hotel-owner#blackouts');
            return;
        }
        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? $startsAt));
        if ($startsAt === '') {
            Response::redirect('/hotel-owner#blackouts');
            return;
        }
        if ($endsAt === '' || strtotime($endsAt) < strtotime($startsAt)) {
            $endsAt = $startsAt;
        }
        Database::connection()->prepare(
            'insert into hotel_blackouts (hotel_id, room_id, title, starts_at, ends_at, repeat_type, status, created_at, updated_at)
             values (:hotel_id, :room_id, :title, :starts_at, :ends_at, :repeat_type, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        )->execute([
            'hotel_id' => $hotelId,
            'room_id' => $roomId > 0 ? $roomId : null,
            'title' => trim((string) ($_POST['title'] ?? 'Blocked dates')),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'repeat_type' => in_array($_POST['repeat_type'] ?? 'none', ['none', 'yearly'], true) ? $_POST['repeat_type'] : 'none',
            'status' => isset($_POST['status']) ? 1 : 0,
        ]);
        Response::redirect('/hotel-owner#blackouts');
    }

    public function updateBlackout(int $id): void
    {
        HotelOwnerAuth::requireOwner();
        HotelSchema::ensure();
        $hotelId = (int) ($_POST['hotel_id'] ?? 0);
        $roomId = (int) ($_POST['room_id'] ?? 0);
        if (!$this->ownsHotel($hotelId) || ($roomId > 0 && !$this->ownsRoom($roomId, $hotelId))) {
            Response::redirect('/hotel-owner#blackouts');
            return;
        }
        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? $startsAt));
        if ($startsAt === '') {
            Response::redirect('/hotel-owner#blackouts');
            return;
        }
        if ($endsAt === '' || strtotime($endsAt) < strtotime($startsAt)) {
            $endsAt = $startsAt;
        }
        Database::connection()->prepare(
            'update hotel_blackouts
             join hotels on hotels.id = hotel_blackouts.hotel_id
             set hotel_blackouts.hotel_id = :hotel_id,
                 hotel_blackouts.room_id = :room_id,
                 hotel_blackouts.title = :title,
                 hotel_blackouts.starts_at = :starts_at,
                 hotel_blackouts.ends_at = :ends_at,
                 hotel_blackouts.repeat_type = :repeat_type,
                 hotel_blackouts.status = :status,
                 hotel_blackouts.updated_at = CURRENT_TIMESTAMP
             where hotel_blackouts.id = :id and hotels.owner_id = :owner_id'
        )->execute([
            'id' => $id,
            'owner_id' => HotelOwnerAuth::id(),
            'hotel_id' => $hotelId,
            'room_id' => $roomId > 0 ? $roomId : null,
            'title' => trim((string) ($_POST['title'] ?? 'Blocked dates')),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'repeat_type' => in_array($_POST['repeat_type'] ?? 'none', ['none', 'yearly'], true) ? $_POST['repeat_type'] : 'none',
            'status' => isset($_POST['status']) ? 1 : 0,
        ]);
        Response::redirect('/hotel-owner#blackouts');
    }

    private function ownsHotel(int $hotelId): bool
    {
        $stmt = Database::connection()->prepare('select id from hotels where id = :id and owner_id = :owner_id limit 1');
        $stmt->execute(['id' => $hotelId, 'owner_id' => HotelOwnerAuth::id()]);
        return (bool) $stmt->fetch();
    }

    private function ownsRoom(int $roomId, int $hotelId): bool
    {
        $stmt = Database::connection()->prepare(
            'select hotel_rooms.id
             from hotel_rooms
             join hotels on hotels.id = hotel_rooms.hotel_id
             where hotel_rooms.id = :id and hotel_rooms.hotel_id = :hotel_id and hotels.owner_id = :owner_id
             limit 1'
        );
        $stmt->execute(['id' => $roomId, 'hotel_id' => $hotelId, 'owner_id' => HotelOwnerAuth::id()]);
        return (bool) $stmt->fetch();
    }

    private function creditCompletedBooking(array $booking): void
    {
        $ownerId = (int) ($booking['owner_id'] ?? HotelOwnerAuth::id());
        if ($ownerId <= 0 || !in_array((string) ($booking['payment_status'] ?? ''), ['paid', 'unpaid'], true)) {
            return;
        }
        $gross = (float) ($booking['grand_total'] ?? 0);
        if ($gross <= 0) {
            return;
        }
        $commissionPercent = Settings::float('hotel_owner_commission_percent', 10);
        $commission = round(($gross * max(0, min(100, $commissionPercent))) / 100, 2);
        $net = max(0, round($gross - $commission, 2));
        WalletService::credit(
            'hotel_owner',
            'hotel-owner-' . $ownerId,
            $net,
            'hotel_booking_settlement',
            'hotel-booking:' . (int) $booking['id'],
            'Settlement for hotel booking ' . ($booking['booking_number'] ?? ('#' . (int) $booking['id']))
        );
    }
}
