<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\PaymentMethodCatalog;
use App\Support\Request;
use App\Support\Response;
use App\Support\ServiceSchema;
use App\Support\Settings;
use App\Support\ZoneSchema;

final class ServiceController
{
    public function config(): void
    {
        ZoneSchema::ensure();
        Response::json([
            'app_name' => Settings::moduleGet('services', 'app_name', 'City Services'),
            'currency_symbol' => Settings::moduleGet('services', 'currency_symbol', '₹'),
            'maintenance_mode' => Settings::moduleBool('services', 'maintenance_mode'),
            'maintenance_message' => Settings::moduleGet('services', 'maintenance_message'),
            'latest_app_version' => Settings::moduleGet('services', 'latest_app_version'),
            'force_update_version' => Settings::moduleGet('services', 'force_update_version'),
            'booking_payments' => PaymentMethodCatalog::enabledForServices('services'),
            'zones' => ZoneSchema::active(),
            'cms_pages' => [
                ['slug' => 'terms-conditions', 'title' => 'Terms & Conditions', 'content' => Settings::moduleGet('services', 'terms_conditions')],
                ['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'content' => Settings::moduleGet('services', 'privacy_policy')],
                ['slug' => 'cancellation-policy', 'title' => 'Cancellation Policy', 'content' => Settings::moduleGet('services', 'cancellation_policy')],
                ['slug' => 'refund-policy', 'title' => 'Refund Policy', 'content' => Settings::moduleGet('services', 'refund_policy')],
            ],
        ]);
    }

    public function categories(): void
    {
        ServiceSchema::ensure();
        ZoneSchema::ensure();
        $rows = Database::connection()->query('select * from service_categories where status = 1 order by sort_order asc, id desc')->fetchAll();
        Response::json(['data' => $rows]);
    }

    public function services(): void
    {
        ServiceSchema::ensure();
        $where = 'services.status = 1';
        $params = [];
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where .= ' and services.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        $query = trim($_GET['query'] ?? '');
        if ($query !== '') {
            $where .= ' and services.name like :query';
            $params['query'] = '%' . $query . '%';
        }
        $stmt = Database::connection()->prepare(
            'select services.*, service_categories.name as category_name, service_providers.name as provider_name
             from services
             left join service_categories on service_categories.id = services.category_id
             left join service_providers on service_providers.id = services.provider_id
             where ' . $where . ZoneSchema::sql('services') . '
             order by services.is_featured desc, services.id desc'
        );
        $params = array_merge($params, ZoneSchema::params());
        $stmt->execute($params);
        Response::json(['data' => array_map([$this, 'formatService'], $stmt->fetchAll())]);
    }

    public function bookings(): void
    {
        ServiceSchema::ensure();
        $guestId = trim($_GET['guest_id'] ?? '');
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select service_bookings.*, services.name as service_name, service_providers.name as provider_name
             from service_bookings
             join services on services.id = service_bookings.service_id
             left join service_providers on service_providers.id = service_bookings.provider_id
             where service_bookings.guest_id = :guest_id
             order by service_bookings.id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function booking(int $id): void
    {
        ServiceSchema::ensure();
        $guestId = trim($_GET['guest_id'] ?? '');
        $stmt = Database::connection()->prepare(
            'select service_bookings.*, services.name as service_name, services.description as service_description,
                    service_providers.name as provider_name
             from service_bookings
             join services on services.id = service_bookings.service_id
             left join service_providers on service_providers.id = service_bookings.provider_id
             where service_bookings.id = :id and service_bookings.guest_id = :guest_id
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

    public function slots(int $serviceId): void
    {
        ServiceSchema::ensure();
        $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            Response::json(['message' => 'Invalid date'], 422);
            return;
        }
        $dayOfWeek = (int) date('w', $timestamp);
        if ($this->isBlackout($serviceId, 0, date('Y-m-d', $timestamp))) {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select service_slots.*, coalesce(service_providers.name, default_provider.name) as provider_name,
                    count(service_bookings.id) as booked_count
             from service_slots
             join services on services.id = service_slots.service_id
             left join service_providers on service_providers.id = service_slots.provider_id
             left join service_providers as default_provider on default_provider.id = services.provider_id
             left join service_bookings on service_bookings.slot_id = service_slots.id
                and service_bookings.preferred_date = :preferred_date
                and service_bookings.booking_status not in (\'cancelled\')
             where service_slots.service_id = :service_id
                and service_slots.day_of_week = :day_of_week
                and service_slots.status = 1
                and services.status = 1
             group by service_slots.id, service_slots.service_id, service_slots.provider_id, service_slots.day_of_week,
                      service_slots.start_time, service_slots.end_time, service_slots.capacity, service_slots.status,
                      service_slots.created_at, service_slots.updated_at, service_providers.name, default_provider.name
             order by service_slots.start_time asc'
        );
        $stmt->execute([
            'service_id' => $serviceId,
            'preferred_date' => date('Y-m-d', $timestamp),
            'day_of_week' => $dayOfWeek,
        ]);
        $slots = array_map(static function (array $slot): array {
            $slot['id'] = (int) $slot['id'];
            $slot['service_id'] = (int) $slot['service_id'];
            $slot['provider_id'] = (int) ($slot['provider_id'] ?? 0);
            $slot['capacity'] = (int) $slot['capacity'];
            $slot['booked_count'] = (int) $slot['booked_count'];
            $slot['available_count'] = max(0, $slot['capacity'] - $slot['booked_count']);
            $slot['is_available'] = $slot['available_count'] > 0;
            return $slot;
        }, $stmt->fetchAll());
        $dateString = date('Y-m-d', $timestamp);
        $slots = array_values(array_filter($slots, fn (array $slot): bool => !$this->isBlackout($serviceId, (int) ($slot['provider_id'] ?? 0), $dateString)));
        Response::json(['data' => $slots]);
    }

    public function cancelBooking(int $id): void
    {
        ServiceSchema::ensure();
        $body = Request::json();
        $guestId = trim((string) ($body['guest_id'] ?? $_GET['guest_id'] ?? ''));
        $db = Database::connection();
        $stmt = $db->prepare('select * from service_bookings where id = :id and guest_id = :guest_id limit 1');
        $stmt->execute(['id' => $id, 'guest_id' => $guestId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            Response::json(['message' => 'Booking not found'], 404);
            return;
        }
        if (in_array($booking['booking_status'], ['completed', 'cancelled', 'cancellation_requested'], true)) {
            Response::json(['message' => 'Cancellation request already submitted or booking cannot be cancelled'], 422);
            return;
        }
        $update = $db->prepare(
            'update service_bookings set booking_status = \'cancellation_requested\', note = :note, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $note = trim((string) ($body['note'] ?? ''));
        $update->execute([
            'id' => $id,
            'note' => $note === '' ? $booking['note'] : trim((string) ($booking['note'] ?? '') . "\nCancel reason: " . $note),
        ]);
        Response::json(['message' => 'Cancellation request sent to admin']);
    }

    public function placeBooking(): void
    {
        ServiceSchema::ensure();
        NotificationSchema::ensure();
        ZoneSchema::ensure();
        $body = Request::json();
        $serviceId = (int) ($body['service_id'] ?? 0);
        $zoneId = max(0, (int) ($body['zone_id'] ?? $_GET['zone_id'] ?? 0));
        $serviceStmt = Database::connection()->prepare('select * from services where id = :id and status = 1 limit 1');
        $serviceStmt->execute(['id' => $serviceId]);
        $service = $serviceStmt->fetch();
        if (!$service) {
            Response::json(['message' => 'Service not found'], 404);
            return;
        }
        if ($zoneId > 0 && !empty($service['zone_id']) && (int) $service['zone_id'] !== $zoneId) {
            Response::json(['message' => 'Selected service is not available in this zone'], 422);
            return;
        }
        foreach (['guest_id', 'customer_name', 'customer_phone', 'address'] as $field) {
            if (trim((string) ($body[$field] ?? '')) === '') {
                Response::json(['message' => 'Missing required field: ' . $field], 422);
                return;
            }
        }
        if ($this->checklist((string) ($service['checklist_json'] ?? '')) !== []
            && filter_var($body['checklist_accepted'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true
        ) {
            Response::json(['message' => 'Please accept the service preparation checklist'], 422);
            return;
        }
        $baseAmount = (float) ($service['discount_price'] ?? $service['price']);
        $addonSelection = $this->selectedAddons($serviceId, is_array($body['addon_ids'] ?? null) ? $body['addon_ids'] : []);
        $addonTotal = (float) $addonSelection['total'];
        $amount = $baseAmount + $addonTotal;
        $preferredDate = trim((string) ($body['preferred_date'] ?? '')) ?: null;
        $preferredTime = trim((string) ($body['preferred_time'] ?? '')) ?: null;
        $slotId = (int) ($body['slot_id'] ?? 0);
        $providerId = (int) ($service['provider_id'] ?? 0) ?: null;

        if ($slotId > 0) {
            if ($preferredDate === null) {
                Response::json(['message' => 'Preferred date is required for selected slot'], 422);
                return;
            }
            $slot = $this->validatedSlot($serviceId, $slotId, $preferredDate);
            if (!$slot) {
                Response::json(['message' => 'Selected slot is not available'], 422);
                return;
            }
            $providerId = (int) ($slot['provider_id'] ?? 0) ?: $providerId;
            $preferredTime = $slot['start_time'] . '-' . $slot['end_time'];
        }

        $paymentMethod = trim((string) ($body['payment_method'] ?? 'cash_on_service'));
        $allowedPayments = array_map(static fn (array $method): string => $method['id'], PaymentMethodCatalog::enabledForServices('services'));
        if (!in_array($paymentMethod, $allowedPayments, true)) {
            $paymentMethod = 'cash_on_service';
        }
        $paymentReference = trim((string) ($body['payment_reference'] ?? ''));
        $paymentNote = trim((string) ($body['payment_note'] ?? ''));
        if (PaymentMethodCatalog::requiresReference($paymentMethod) && $paymentReference === '') {
            Response::json(['message' => 'Payment reference is required'], 422);
            return;
        }
        $note = trim((string) ($body['note'] ?? ''));
        if ($paymentReference !== '' || $paymentNote !== '') {
            $note = trim($note . "\nPayment reference: " . $paymentReference . "\nPayment note: " . $paymentNote);
        }

        $bookingNumber = 'CSV' . date('ymdHis') . random_int(100, 999);
        $db = Database::connection();
        try {
            $db->beginTransaction();
            if ($slotId > 0) {
                $this->lockServiceSlot($slotId);
            }
            if ($slotId > 0 && !$this->validatedSlot($serviceId, $slotId, (string) $preferredDate)) {
                $db->rollBack();
                Response::json(['message' => 'Selected slot is no longer available'], 409);
                return;
            }

            $stmt = $db->prepare(
                'insert into service_bookings
                 (booking_number, zone_id, service_id, provider_id, slot_id, vendor_id, guest_id, customer_name, customer_phone, customer_email, address, preferred_date, preferred_time, addons_json, addon_total, amount, payment_method, payment_status, booking_status, warranty_days, note, created_at, updated_at)
                 values
                 (:booking_number, :zone_id, :service_id, :provider_id, :slot_id, :vendor_id, :guest_id, :customer_name, :customer_phone, :customer_email, :address, :preferred_date, :preferred_time, :addons_json, :addon_total, :amount, :payment_method, :payment_status, \'pending\', :warranty_days, :note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $guestId = trim((string) $body['guest_id']);
            $stmt->execute([
                'booking_number' => $bookingNumber,
                'zone_id' => $zoneId > 0 ? $zoneId : null,
                'service_id' => $serviceId,
                'provider_id' => $providerId,
                'slot_id' => $slotId > 0 ? $slotId : null,
                'vendor_id' => $service['vendor_id'] ?? null,
                'guest_id' => $guestId,
                'customer_name' => trim((string) $body['customer_name']),
                'customer_phone' => trim((string) $body['customer_phone']),
                'customer_email' => trim((string) ($body['customer_email'] ?? '')) ?: null,
                'address' => trim((string) $body['address']),
                'preferred_date' => $preferredDate,
                'preferred_time' => $preferredTime,
                'addons_json' => json_encode($addonSelection['items']),
                'addon_total' => $addonTotal,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'cash_on_service' ? 'unpaid' : 'pending_verification',
                'warranty_days' => max(0, (int) ($service['warranty_days'] ?? 0)),
                'note' => $note === '' ? null : $note,
            ]);
            $bookingId = (int) $db->lastInsertId();
            $this->recordPayment($bookingId, $paymentMethod, $amount, $paymentReference, $paymentNote);
            NotificationLog::record(
                'customer',
                null,
                $guestId,
                'Booking placed',
                'Your service booking ' . $bookingNumber . ' was placed.',
                $bookingId,
                'services'
            );
            if ($providerId !== null && $providerId > 0) {
                NotificationLog::record(
                    'provider',
                    (int) $providerId,
                    null,
                    'New service booking',
                    $bookingNumber . ' is assigned to you.',
                    $bookingId,
                    'services'
                );
            }
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::json(['message' => 'Service booking could not be placed. Please try again.'], 500);
            return;
        }

        Response::json(['message' => 'Booking placed', 'data' => ['id' => $bookingId, 'booking_number' => $bookingNumber, 'amount' => $amount]], 201);
    }

    private function formatService(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['category_id'] = (int) ($row['category_id'] ?? 0);
        $row['price'] = (float) $row['price'];
        $row['discount_price'] = $row['discount_price'] === null ? null : (float) $row['discount_price'];
        $row['duration_minutes'] = (int) $row['duration_minutes'];
        $row['warranty_days'] = (int) ($row['warranty_days'] ?? 0);
        $row['checklist'] = $this->checklist((string) ($row['checklist_json'] ?? ''));
        $row['is_featured'] = !empty($row['is_featured']);
        $stmt = Database::connection()->prepare(
            'select id, service_id, name, description, price
             from service_addons
             where service_id = :service_id and status = 1
             order by sort_order asc, id desc'
        );
        $stmt->execute(['service_id' => $row['id']]);
        $row['addons'] = array_map(static function (array $addon): array {
            $addon['id'] = (int) $addon['id'];
            $addon['service_id'] = (int) $addon['service_id'];
            $addon['price'] = (float) $addon['price'];
            return $addon;
        }, $stmt->fetchAll());
        return $row;
    }

    private function checklist(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $decoded
        ), static fn (string $item): bool => $item !== ''));
    }

    public function rescheduleBooking(int $id): void
    {
        ServiceSchema::ensure();
        NotificationSchema::ensure();
        $body = Request::json();
        $guestId = trim((string) ($body['guest_id'] ?? $_GET['guest_id'] ?? ''));
        $db = Database::connection();
        $stmt = $db->prepare('select * from service_bookings where id = :id and guest_id = :guest_id limit 1');
        $stmt->execute(['id' => $id, 'guest_id' => $guestId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            Response::json(['message' => 'Booking not found'], 404);
            return;
        }
        if (!in_array($booking['booking_status'], ['pending', 'accepted'], true)) {
            Response::json(['message' => 'This booking cannot be rescheduled now'], 422);
            return;
        }
        if ((int) ($booking['reschedule_count'] ?? 0) >= 2) {
            Response::json(['message' => 'Reschedule limit reached'], 422);
            return;
        }

        $preferredDate = trim((string) ($body['preferred_date'] ?? ''));
        $slotId = (int) ($body['slot_id'] ?? 0);
        $preferredTime = trim((string) ($body['preferred_time'] ?? ''));
        $providerId = (int) ($booking['provider_id'] ?? 0) ?: null;
        if ($preferredDate === '') {
            Response::json(['message' => 'Preferred date is required'], 422);
            return;
        }
        if ($slotId > 0) {
            $slot = $this->validatedSlot((int) $booking['service_id'], $slotId, $preferredDate, $id);
            if (!$slot) {
                Response::json(['message' => 'Selected slot is not available'], 422);
                return;
            }
            $providerId = (int) ($slot['provider_id'] ?? 0) ?: $providerId;
            $preferredTime = $slot['start_time'] . '-' . $slot['end_time'];
        }

        try {
            $db->beginTransaction();
            if ($slotId > 0) {
                $this->lockServiceSlot($slotId);
                $slot = $this->validatedSlot((int) $booking['service_id'], $slotId, $preferredDate, $id);
                if (!$slot) {
                    $db->rollBack();
                    Response::json(['message' => 'Selected slot is no longer available'], 409);
                    return;
                }
                $providerId = (int) ($slot['provider_id'] ?? 0) ?: $providerId;
                $preferredTime = $slot['start_time'] . '-' . $slot['end_time'];
            }

            $update = $db->prepare(
                'update service_bookings
                 set provider_id = :provider_id, slot_id = :slot_id, preferred_date = :preferred_date,
                     preferred_time = :preferred_time, reschedule_count = coalesce(reschedule_count, 0) + 1,
                     rescheduled_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                 where id = :id'
            );
            $update->execute([
                'id' => $id,
                'provider_id' => $providerId,
                'slot_id' => $slotId > 0 ? $slotId : null,
                'preferred_date' => $preferredDate,
                'preferred_time' => $preferredTime ?: null,
            ]);
            NotificationLog::record(
                'customer',
                null,
                $guestId,
                'Booking rescheduled',
                'Your booking ' . $booking['booking_number'] . ' was rescheduled.',
                $id,
                'services'
            );
            if ($providerId !== null && $providerId > 0) {
                NotificationLog::record(
                    'provider',
                    (int) $providerId,
                    null,
                    'Booking rescheduled',
                    $booking['booking_number'] . ' has a new schedule.',
                    $id,
                    'services'
                );
            }
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::json(['message' => 'Booking could not be rescheduled. Please try again.'], 500);
            return;
        }
        Response::json(['message' => 'Booking rescheduled']);
    }

    private function selectedAddons(int $serviceId, array $addonIds): array
    {
        $addonIds = array_values(array_unique(array_filter(array_map('intval', $addonIds), static fn (int $id): bool => $id > 0)));
        if ($addonIds === []) {
            return ['items' => [], 'total' => 0.0];
        }
        $placeholders = implode(',', array_fill(0, count($addonIds), '?'));
        $stmt = Database::connection()->prepare(
            'select id, name, price from service_addons
             where service_id = ? and status = 1 and id in (' . $placeholders . ')'
        );
        $stmt->execute(array_merge([$serviceId], $addonIds));
        $items = array_map(static function (array $addon): array {
            return [
                'id' => (int) $addon['id'],
                'name' => (string) $addon['name'],
                'price' => (float) $addon['price'],
            ];
        }, $stmt->fetchAll());
        $total = array_reduce($items, static fn (float $carry, array $addon): float => $carry + (float) $addon['price'], 0.0);
        return ['items' => $items, 'total' => $total];
    }

    private function validatedSlot(int $serviceId, int $slotId, string $preferredDate, int $excludeBookingId = 0): ?array
    {
        $timestamp = strtotime($preferredDate);
        if ($timestamp === false) {
            return null;
        }
        if ($this->isBlackout($serviceId, 0, date('Y-m-d', $timestamp))) {
            return null;
        }
        $dayOfWeek = (int) date('w', $timestamp);
        $db = Database::connection();
        $stmt = $db->prepare(
            'select service_slots.*, count(service_bookings.id) as booked_count
             from service_slots
             left join service_bookings on service_bookings.slot_id = service_slots.id
                and service_bookings.preferred_date = :preferred_date
                and service_bookings.booking_status not in (\'cancelled\')
                and (:exclude_booking_id_zero = 0 or service_bookings.id != :exclude_booking_id)
             where service_slots.id = :slot_id
                and service_slots.service_id = :service_id
                and service_slots.day_of_week = :day_of_week
                and service_slots.status = 1
             group by service_slots.id, service_slots.service_id, service_slots.provider_id, service_slots.day_of_week,
                      service_slots.start_time, service_slots.end_time, service_slots.capacity, service_slots.status,
                      service_slots.created_at, service_slots.updated_at
             limit 1'
        );
        $stmt->execute([
            'slot_id' => $slotId,
            'service_id' => $serviceId,
            'preferred_date' => date('Y-m-d', $timestamp),
            'day_of_week' => $dayOfWeek,
            'exclude_booking_id_zero' => $excludeBookingId,
            'exclude_booking_id' => $excludeBookingId,
        ]);
        $slot = $stmt->fetch();
        if (!$slot || (int) $slot['booked_count'] >= (int) $slot['capacity']) {
            return null;
        }
        if ($this->isBlackout($serviceId, (int) ($slot['provider_id'] ?? 0), date('Y-m-d', $timestamp), $slot['start_time'] ?? null, $slot['end_time'] ?? null)) {
            return null;
        }
        return $slot;
    }

    private function lockServiceSlot(int $slotId): void
    {
        if ($slotId <= 0) {
            return;
        }
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }
        $stmt = $db->prepare('select id from service_slots where id = :id limit 1 for update');
        $stmt->execute(['id' => $slotId]);
    }

    private function isBlackout(int $serviceId, int $providerId, string $date, ?string $slotStart = null, ?string $slotEnd = null): bool
    {
        $providerSql = $providerId > 0
            ? 'and (provider_id is null or provider_id = :provider_id)'
            : 'and provider_id is null';
        $params = [
            'service_id' => $serviceId,
        ];
        if ($providerId > 0) {
            $params['provider_id'] = $providerId;
        }
        $stmt = Database::connection()->prepare(
            'select * from service_blackouts
             where status = 1
                and (service_id is null or service_id = :service_id)
                ' . $providerSql
        );
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $blackout) {
            if (!$this->dateMatchesBlackout($blackout, $date)) {
                continue;
            }
            if (!$this->timeMatchesBlackout($blackout, $slotStart, $slotEnd)) {
                continue;
            }
            return true;
        }
        return false;
    }

    private function dateMatchesBlackout(array $blackout, string $date): bool
    {
        $start = (string) ($blackout['blackout_date'] ?? '');
        $end = (string) ($blackout['end_date'] ?? '') ?: $start;
        $recurrence = (string) ($blackout['recurrence'] ?? 'none');
        if ($recurrence === 'weekly') {
            return date('w', strtotime($date) ?: time()) === date('w', strtotime($start) ?: time());
        }
        if ($recurrence === 'monthly') {
            return date('d', strtotime($date) ?: time()) === date('d', strtotime($start) ?: time());
        }
        if ($recurrence === 'yearly') {
            return date('m-d', strtotime($date) ?: time()) === date('m-d', strtotime($start) ?: time());
        }
        return $date >= $start && $date <= $end;
    }

    private function timeMatchesBlackout(array $blackout, ?string $slotStart, ?string $slotEnd): bool
    {
        $blockStart = (string) ($blackout['start_time'] ?? '');
        $blockEnd = (string) ($blackout['end_time'] ?? '');
        if ($blockStart === '' || $blockEnd === '' || $slotStart === null || $slotEnd === null) {
            return true;
        }
        return $slotStart < $blockEnd && $slotEnd > $blockStart;
    }

    private function recordPayment(int $bookingId, string $method, float $amount, string $reference, string $note): void
    {
        $status = $method === 'cash_on_service' ? 'pending' : 'pending_verification';
        Database::connection()->prepare(
            'insert into service_payment_transactions
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
