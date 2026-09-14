<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\Response;
use App\Support\ServiceSchema;
use App\Support\View;
use App\Support\ZoneSchema;

final class ServiceController
{
    public function index(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        ZoneSchema::ensure();
        $db = Database::connection();
        $statsStmt = $db->prepare(
            'select count(*) as total_bookings,
                    coalesce(sum(amount), 0) as total_amount,
                    sum(case when booking_status = \'pending\' then 1 else 0 end) as pending_count,
                    sum(case when booking_status = \'completed\' then 1 else 0 end) as completed_count,
                    sum(case when booking_status = \'cancelled\' then 1 else 0 end) as cancelled_count,
                    sum(case when booking_status = \'cancellation_requested\' then 1 else 0 end) as cancellation_requested_count
             from service_bookings where 1 = 1' . Auth::zoneWhere('service_bookings')
        );
        $statsStmt->execute(Auth::zoneParams());
        $stats = $statsStmt->fetch();
        $providerReportsStmt = $db->prepare(
            'select service_providers.id, service_providers.name,
                    service_providers.commission_percent,
                    count(service_bookings.id) as booking_count,
                    coalesce(sum(service_bookings.amount), 0) as gross_amount,
                    coalesce(sum(service_bookings.amount * service_providers.commission_percent / 100), 0) as commission_amount
             from service_providers
             left join service_bookings on service_bookings.provider_id = service_providers.id
                and service_bookings.booking_status = \'completed\'
             where 1 = 1' . Auth::zoneWhere('service_providers') . '
             group by service_providers.id, service_providers.name, service_providers.commission_percent
             order by commission_amount desc, gross_amount desc'
        );
        $providerReportsStmt->execute(Auth::zoneParams());
        $providerReports = $providerReportsStmt->fetchAll();
        $providersStmt = $db->prepare(
            'select service_providers.*, zones.name as zone_name
             from service_providers
             left join zones on zones.id = service_providers.zone_id
             where 1 = 1' . Auth::zoneWhere('service_providers') . '
             order by service_providers.status desc, service_providers.name asc'
        );
        $providersStmt->execute(Auth::zoneParams());
        $servicesStmt = $db->prepare(
            'select services.*, service_categories.name as category_name, service_providers.name as provider_name, zones.name as zone_name
             from services
             left join service_categories on service_categories.id = services.category_id
             left join service_providers on service_providers.id = services.provider_id
             left join zones on zones.id = services.zone_id
             where 1 = 1' . Auth::zoneWhere('services') . '
             order by services.id desc'
        );
        $servicesStmt->execute(Auth::zoneParams());
        $bookingsStmt = $db->prepare(
            'select service_bookings.*, services.name as service_name, service_providers.name as provider_name,
                    service_slots.start_time as slot_start_time, service_slots.end_time as slot_end_time,
                    service_payment_transactions.id as payment_transaction_id,
                    service_payment_transactions.reference as payment_reference,
                    service_payment_transactions.status as transaction_status
             from service_bookings
             left join services on services.id = service_bookings.service_id
             left join service_providers on service_providers.id = service_bookings.provider_id
             left join service_slots on service_slots.id = service_bookings.slot_id
             left join service_payment_transactions on service_payment_transactions.booking_id = service_bookings.id
             where 1 = 1' . Auth::zoneWhere('service_bookings') . '
             order by service_bookings.id desc
             limit 50'
        );
        $bookingsStmt->execute(Auth::zoneParams());
        View::render('admin/services', [
            'title' => 'Services',
            'stats' => $stats,
            'providerReports' => $providerReports,
            'categoryReports' => $this->categoryReports(),
            'bookingStatusReports' => $this->bookingStatusReports(),
            'categories' => $db->query('select * from service_categories order by sort_order asc, id desc')->fetchAll(),
            'providers' => $providersStmt->fetchAll(),
            'zones' => $this->zones(),
            'services' => $servicesStmt->fetchAll(),
            'slots' => $this->serviceScopedRows(
                'select service_slots.*, services.name as service_name, service_providers.name as provider_name
                 from service_slots
                 join services on services.id = service_slots.service_id
                 left join service_providers on service_providers.id = service_slots.provider_id
                 where 1 = 1' . Auth::zoneWhere('services') . '
                 order by service_slots.day_of_week asc, service_slots.start_time asc, service_slots.id desc'
            ),
            'blackouts' => $this->serviceScopedRows(
                'select service_blackouts.*, services.name as service_name, service_providers.name as provider_name
                 from service_blackouts
                 left join services on services.id = service_blackouts.service_id
                 left join service_providers on service_providers.id = service_blackouts.provider_id
                 where 1 = 1' . Auth::zoneWhere('services') . '
                 order by service_blackouts.blackout_date desc, service_blackouts.id desc'
            ),
            'addons' => $this->serviceScopedRows(
                'select service_addons.*, services.name as service_name
                 from service_addons
                 join services on services.id = service_addons.service_id
                 where 1 = 1' . Auth::zoneWhere('services') . '
                 order by services.name asc, service_addons.sort_order asc, service_addons.id desc'
            ),
            'settlements' => $this->providerScopedRows(
                'select service_provider_settlements.*, service_providers.name as provider_name
                 from service_provider_settlements
                 join service_providers on service_providers.id = service_provider_settlements.provider_id
                 where 1 = 1' . Auth::zoneWhere('service_providers') . '
                 order by service_provider_settlements.id desc
                 limit 50'
            ),
            'bookings' => $bookingsStmt->fetchAll(),
        ]);
    }

    public function storeCategory(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        ZoneSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into service_categories (name, description, icon, status, sort_order, created_at, updated_at)
             values (:name, :description, :icon, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'icon' => trim($_POST['icon'] ?? '') ?: null,
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        Response::redirect('/admin/services');
    }

    public function updateCategory(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        ZoneSchema::ensure();
        $stmt = Database::connection()->prepare(
            'update service_categories
             set name = :name, description = :description, icon = :icon, status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'icon' => trim($_POST['icon'] ?? '') ?: null,
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        Response::redirect('/admin/services');
    }

    public function archiveCategory(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        Database::connection()->prepare(
            'update service_categories set status = 0, updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute(['id' => $id]);
        Response::redirect('/admin/services');
    }

    public function storeService(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        ZoneSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into services (zone_id, category_id, provider_id, name, description, checklist_json, duration_minutes, warranty_days, price, discount_price, status, is_featured, created_at, updated_at)
             values (:zone_id, :category_id, :provider_id, :name, :description, :checklist_json, :duration_minutes, :warranty_days, :price, :discount_price, :status, :is_featured, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'zone_id' => $this->zoneId(),
            'category_id' => (int) ($_POST['category_id'] ?? 0) ?: null,
            'provider_id' => (int) ($_POST['provider_id'] ?? 0) ?: null,
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'checklist_json' => $this->checklistJson($_POST['checklist'] ?? ''),
            'duration_minutes' => max(15, (int) ($_POST['duration_minutes'] ?? 60)),
            'warranty_days' => max(0, (int) ($_POST['warranty_days'] ?? 0)),
            'price' => max(0, (float) ($_POST['price'] ?? 0)),
            'discount_price' => trim($_POST['discount_price'] ?? '') === '' ? null : max(0, (float) $_POST['discount_price']),
            'status' => isset($_POST['status']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ]);
        Response::redirect('/admin/services');
    }

    public function updateService(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $stmt = Database::connection()->prepare(
             'update services
             set category_id = :category_id, provider_id = :provider_id, name = :name, description = :description, duration_minutes = :duration_minutes,
                 checklist_json = :checklist_json, warranty_days = :warranty_days, zone_id = :zone_id, price = :price, discount_price = :discount_price, status = :status, is_featured = :is_featured, updated_at = CURRENT_TIMESTAMP
             where id = :id' . Auth::zoneWhere('services')
        );
        $stmt->execute(Auth::zoneParams([
            'id' => $id,
            'zone_id' => $this->zoneId(),
            'category_id' => (int) ($_POST['category_id'] ?? 0) ?: null,
            'provider_id' => (int) ($_POST['provider_id'] ?? 0) ?: null,
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'checklist_json' => $this->checklistJson($_POST['checklist'] ?? ''),
            'duration_minutes' => max(15, (int) ($_POST['duration_minutes'] ?? 60)),
            'warranty_days' => max(0, (int) ($_POST['warranty_days'] ?? 0)),
            'price' => max(0, (float) ($_POST['price'] ?? 0)),
            'discount_price' => trim($_POST['discount_price'] ?? '') === '' ? null : max(0, (float) $_POST['discount_price']),
            'status' => isset($_POST['status']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ]));
        Response::redirect('/admin/services');
    }

    public function storeProvider(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into service_providers (zone_id, name, phone, email, area, password, commission_percent, status, created_at, updated_at)
             values (:zone_id, :name, :phone, :email, :area, :password, :commission_percent, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $password = trim((string) ($_POST['password'] ?? ''));
        $stmt->execute([
            'zone_id' => $this->zoneId(),
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'email' => trim($_POST['email'] ?? '') ?: null,
            'area' => trim($_POST['area'] ?? '') ?: null,
            'password' => $password === '' ? null : password_hash($password, PASSWORD_DEFAULT),
            'commission_percent' => min(100, max(0, (float) ($_POST['commission_percent'] ?? 0))),
            'status' => isset($_POST['status']) ? 1 : 0,
        ]);
        Response::redirect('/admin/services');
    }

    public function updateProvider(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        ZoneSchema::ensure();
        $password = trim((string) ($_POST['password'] ?? ''));
        $passwordSql = $password === '' ? '' : ', password = :password';
        $params = [
            'id' => $id,
            'zone_id' => $this->zoneId(),
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'email' => trim($_POST['email'] ?? '') ?: null,
            'area' => trim($_POST['area'] ?? '') ?: null,
            'commission_percent' => min(100, max(0, (float) ($_POST['commission_percent'] ?? 0))),
            'status' => isset($_POST['status']) ? 1 : 0,
        ];
        if ($password !== '') {
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $stmt = Database::connection()->prepare(
            'update service_providers
             set zone_id = :zone_id, name = :name, phone = :phone, email = :email, area = :area, commission_percent = :commission_percent, status = :status' . $passwordSql . ', updated_at = CURRENT_TIMESTAMP
             where id = :id' . Auth::zoneWhere('service_providers')
        );
        $stmt->execute(Auth::zoneParams($params));
        Response::redirect('/admin/services');
    }

    public function archiveProvider(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        Database::connection()->prepare(
            'update service_providers set status = 0, updated_at = CURRENT_TIMESTAMP where id = :id' . Auth::zoneWhere('service_providers')
        )->execute(Auth::zoneParams(['id' => $id]));
        Response::redirect('/admin/services');
    }

    public function storeSlot(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into service_slots (service_id, provider_id, day_of_week, start_time, end_time, capacity, status, created_at, updated_at)
             values (:service_id, :provider_id, :day_of_week, :start_time, :end_time, :capacity, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->slotPayload());
        Response::redirect('/admin/services');
    }

    public function updateSlot(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $payload = $this->slotPayload();
        $payload['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update service_slots
             set service_id = :service_id, provider_id = :provider_id, day_of_week = :day_of_week,
                 start_time = :start_time, end_time = :end_time, capacity = :capacity, status = :status,
                 updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute($payload);
        Response::redirect('/admin/services');
    }

    public function archiveSlot(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        Database::connection()->prepare(
            'update service_slots set status = 0, updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute(['id' => $id]);
        Response::redirect('/admin/services');
    }

    public function storeBlackout(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into service_blackouts (service_id, provider_id, blackout_date, end_date, recurrence, start_time, end_time, reason, status, created_at, updated_at)
             values (:service_id, :provider_id, :blackout_date, :end_date, :recurrence, :start_time, :end_time, :reason, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->blackoutPayload());
        Response::redirect('/admin/services');
    }

    public function updateBlackout(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $payload = $this->blackoutPayload();
        $payload['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update service_blackouts
             set service_id = :service_id, provider_id = :provider_id, blackout_date = :blackout_date,
                 end_date = :end_date, recurrence = :recurrence, start_time = :start_time, end_time = :end_time,
                 reason = :reason, status = :status, updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute($payload);
        Response::redirect('/admin/services');
    }

    public function archiveBlackout(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        Database::connection()->prepare(
            'update service_blackouts set status = 0, updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute(['id' => $id]);
        Response::redirect('/admin/services');
    }

    public function storeAddon(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into service_addons (service_id, name, description, price, status, sort_order, created_at, updated_at)
             values (:service_id, :name, :description, :price, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->addonPayload());
        Response::redirect('/admin/services');
    }

    public function updateAddon(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $payload = $this->addonPayload();
        $payload['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update service_addons
             set service_id = :service_id, name = :name, description = :description, price = :price,
                 status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute($payload);
        Response::redirect('/admin/services');
    }

    public function archiveAddon(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        Database::connection()->prepare(
            'update service_addons set status = 0, updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute(['id' => $id]);
        Response::redirect('/admin/services');
    }

    public function archiveService(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        Database::connection()->prepare(
            'update services set status = 0, updated_at = CURRENT_TIMESTAMP where id = :id' . Auth::zoneWhere('services')
        )->execute(Auth::zoneParams(['id' => $id]));
        Response::redirect('/admin/services');
    }

    public function storeSettlement(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $providerId = max(1, (int) ($_POST['provider_id'] ?? 0));
        $periodStart = trim((string) ($_POST['period_start'] ?? '')) ?: null;
        $periodEnd = trim((string) ($_POST['period_end'] ?? '')) ?: null;
        $summary = $this->providerSettlementSummary($providerId, $periodStart, $periodEnd);
        $status = $_POST['status'] ?? 'paid';
        if (!in_array($status, ['pending', 'paid'], true)) {
            $status = 'paid';
        }
        $settledAt = $status === 'paid' ? 'CURRENT_TIMESTAMP' : 'null';
        $stmt = Database::connection()->prepare(
            'insert into service_provider_settlements
             (provider_id, period_start, period_end, gross_amount, commission_amount, payable_amount, payment_reference, note, status, settled_at, created_at, updated_at)
             values (:provider_id, :period_start, :period_end, :gross_amount, :commission_amount, :payable_amount, :payment_reference, :note, :status, ' . $settledAt . ', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'provider_id' => $providerId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'gross_amount' => $summary['gross_amount'],
            'commission_amount' => $summary['commission_amount'],
            'payable_amount' => $summary['payable_amount'],
            'payment_reference' => trim((string) ($_POST['payment_reference'] ?? '')) ?: null,
            'note' => trim((string) ($_POST['note'] ?? '')) ?: null,
            'status' => $status,
        ]);
        Response::redirect('/admin/services');
    }

    public function settlementStatus(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $status = $_POST['status'] ?? 'paid';
        if (!in_array($status, ['pending', 'paid', 'cancelled'], true)) {
            $status = 'paid';
        }
        $settledAtSql = $status === 'paid' ? ', settled_at = CURRENT_TIMESTAMP' : '';
        Database::connection()->prepare(
            'update service_provider_settlements
             set status = :status, payment_reference = :payment_reference, note = :note' . $settledAtSql . ', updated_at = CURRENT_TIMESTAMP
             where id = :id'
        )->execute([
            'id' => $id,
            'status' => $status,
            'payment_reference' => trim((string) ($_POST['payment_reference'] ?? '')) ?: null,
            'note' => trim((string) ($_POST['note'] ?? '')) ?: null,
        ]);
        Response::redirect('/admin/services');
    }

    public function bookingStatus(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $allowed = ['pending', 'accepted', 'ongoing', 'completed', 'cancelled', 'cancellation_requested'];
        $status = $_POST['booking_status'] ?? 'pending';
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }
        $db = Database::connection();
        $isMysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        $completedAtSql = '';
        if ($status === 'completed') {
            $warrantySql = $isMysql
                ? 'date_add(current_date, interval warranty_days day)'
                : 'date(CURRENT_TIMESTAMP, \'+\' || warranty_days || \' days\')';
            $completedAtSql = ', completed_at = CURRENT_TIMESTAMP, warranty_until = case when coalesce(warranty_days, 0) > 0 then ' . $warrantySql . ' else null end';
        }
        $cancelledAtSql = $status === 'cancelled' ? ', cancelled_at = CURRENT_TIMESTAMP' : '';
        $lookup = $db->prepare('select * from service_bookings where id = :id' . Auth::zoneWhere('service_bookings') . ' limit 1');
        $lookup->execute(Auth::zoneParams(['id' => $id]));
        $booking = $lookup->fetch();
        if (!$booking) {
            Response::redirect('/admin/services');
            return;
        }
        $db->prepare(
            'update service_bookings set booking_status = :status, admin_note = :admin_note' . $completedAtSql . $cancelledAtSql . ', updated_at = CURRENT_TIMESTAMP where id = :id' . Auth::zoneWhere('service_bookings')
        )->execute(Auth::zoneParams([
            'id' => $id,
            'status' => $status,
            'admin_note' => trim($_POST['admin_note'] ?? '') ?: null,
        ]));
        NotificationLog::record(
            'customer',
            null,
            (string) ($booking['guest_id'] ?? ''),
            'Booking status updated',
            'Your booking ' . $booking['booking_number'] . ' is now ' . $status . '.',
            null,
            'services'
        );
        if (!empty($booking['provider_id'])) {
            NotificationLog::record(
                'provider',
                (int) $booking['provider_id'],
                null,
                'Booking status updated',
                $booking['booking_number'] . ' is now ' . $status . '.',
                null,
                'services'
            );
        }
        Response::redirect('/admin/services');
    }

    public function paymentStatus(int $id): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $status = $_POST['status'] ?? 'verified';
        if (!in_array($status, ['pending', 'pending_verification', 'verified', 'rejected'], true)) {
            $status = 'pending_verification';
        }
        $paymentStatus = match ($status) {
            'verified' => 'paid',
            'rejected' => 'payment_rejected',
            default => 'pending_verification',
        };
        $verifiedSql = $status === 'verified' ? ', verified_at = CURRENT_TIMESTAMP' : '';
        $db = Database::connection();
        $booking = $db->prepare(
            'select service_bookings.* from service_bookings
             join service_payment_transactions on service_payment_transactions.booking_id = service_bookings.id
             where service_payment_transactions.id = :id' . Auth::zoneWhere('service_bookings') . ' limit 1'
        );
        $booking->execute(Auth::zoneParams(['id' => $id]));
        $row = $booking->fetch();
        if ($row) {
            $stmt = $db->prepare(
                'update service_payment_transactions
                 set status = :status' . $verifiedSql . ', note = :note, updated_at = CURRENT_TIMESTAMP
                 where id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'status' => $status,
                'note' => trim((string) ($_POST['note'] ?? '')) ?: null,
            ]);
            $db->prepare('update service_bookings set payment_status = :payment_status, updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['payment_status' => $paymentStatus, 'id' => (int) $row['id']]);
            NotificationLog::record(
                'customer',
                null,
                (string) ($row['guest_id'] ?? ''),
                'Service payment updated',
                'Payment for booking ' . $row['booking_number'] . ' is now ' . $paymentStatus . '.',
                null,
                'services'
            );
        }
        Response::redirect('/admin/services');
    }

    public function exportBookings(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select service_bookings.booking_number, services.name as service_name, service_providers.name as provider_name,
                    service_slots.start_time as slot_start_time, service_slots.end_time as slot_end_time,
                    service_bookings.customer_name,
                    service_bookings.customer_phone, service_bookings.address, service_bookings.preferred_date,
                    service_bookings.preferred_time, service_bookings.amount, service_bookings.payment_method,
                    service_bookings.payment_status, service_bookings.booking_status, service_bookings.admin_note,
                    service_bookings.created_at
             from service_bookings
             left join services on services.id = service_bookings.service_id
             left join service_providers on service_providers.id = service_bookings.provider_id
             left join service_slots on service_slots.id = service_bookings.slot_id
             where 1 = 1' . Auth::zoneWhere('service_bookings') . '
             order by service_bookings.id desc'
        );
        $stmt->execute(Auth::zoneParams());
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="service-bookings.csv"');
        $output = fopen('php://output', 'wb');
        fputcsv($output, ['Booking', 'Service', 'Provider', 'Customer', 'Phone', 'Address', 'Date', 'Time', 'Slot', 'Amount', 'Payment Method', 'Payment Status', 'Booking Status', 'Admin Note', 'Created At']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['booking_number'],
                $row['service_name'] ?? '-',
                $row['provider_name'] ?? '-',
                $row['customer_name'],
                $row['customer_phone'],
                $row['address'],
                $row['preferred_date'],
                $row['preferred_time'],
                trim((string) ($row['slot_start_time'] ?? '') . '-' . (string) ($row['slot_end_time'] ?? ''), '-'),
                $row['amount'],
                $row['payment_method'],
                $row['payment_status'],
                $row['booking_status'],
                $row['admin_note'],
                $row['created_at'],
            ]);
        }
        fclose($output);
        exit;
    }

    public function exportSettlements(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select service_provider_settlements.*, service_providers.name as provider_name
             from service_provider_settlements
             join service_providers on service_providers.id = service_provider_settlements.provider_id
             where 1 = 1' . Auth::zoneWhere('service_providers') . '
             order by service_provider_settlements.id desc'
        );
        $stmt->execute(Auth::zoneParams());
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="service-provider-settlements.csv"');
        $output = fopen('php://output', 'wb');
        fputcsv($output, ['Provider', 'Period Start', 'Period End', 'Gross', 'Commission', 'Payable', 'Reference', 'Status', 'Settled At', 'Note', 'Created At']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['provider_name'],
                $row['period_start'],
                $row['period_end'],
                $row['gross_amount'],
                $row['commission_amount'],
                $row['payable_amount'],
                $row['payment_reference'],
                $row['status'],
                $row['settled_at'],
                $row['note'],
                $row['created_at'],
            ]);
        }
        fclose($output);
        exit;
    }

    public function exportProviderReports(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $this->csv('service-provider-reports.csv', ['Provider', 'Bookings', 'Gross', 'Commission %', 'Commission', 'Payable'], array_map(static function (array $row): array {
            $commission = (float) $row['commission_amount'];
            return [
                $row['name'],
                (string) $row['booking_count'],
                (string) $row['gross_amount'],
                (string) $row['commission_percent'],
                (string) $commission,
                (string) max(0, (float) $row['gross_amount'] - $commission),
            ];
        }, $this->providerReports()));
    }

    public function exportCategoryReports(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $this->csv('service-category-performance.csv', ['Category', 'Bookings', 'Completed', 'Cancelled', 'Gross'], array_map(static fn (array $row): array => [
            $row['category_name'],
            (string) $row['booking_count'],
            (string) $row['completed_count'],
            (string) $row['cancelled_count'],
            (string) $row['gross_amount'],
        ], $this->categoryReports()));
    }

    public function exportStatusReports(): void
    {
        Auth::requireAdmin();
        ServiceSchema::ensure();
        $this->csv('service-booking-status-report.csv', ['Status', 'Count', 'Amount'], array_map(static fn (array $row): array => [
            $row['booking_status'],
            (string) $row['booking_count'],
            (string) $row['gross_amount'],
        ], $this->bookingStatusReports()));
    }

    private function slotPayload(): array
    {
        return [
            'service_id' => max(1, (int) ($_POST['service_id'] ?? 0)),
            'provider_id' => (int) ($_POST['provider_id'] ?? 0) ?: null,
            'day_of_week' => min(6, max(0, (int) ($_POST['day_of_week'] ?? 0))),
            'start_time' => trim($_POST['start_time'] ?? '09:00'),
            'end_time' => trim($_POST['end_time'] ?? '10:00'),
            'capacity' => max(1, (int) ($_POST['capacity'] ?? 1)),
            'status' => isset($_POST['status']) ? 1 : 0,
        ];
    }

    private function blackoutPayload(): array
    {
        return [
            'service_id' => (int) ($_POST['service_id'] ?? 0) ?: null,
            'provider_id' => (int) ($_POST['provider_id'] ?? 0) ?: null,
            'blackout_date' => trim((string) ($_POST['blackout_date'] ?? date('Y-m-d'))),
            'end_date' => trim((string) ($_POST['end_date'] ?? '')) ?: null,
            'recurrence' => in_array(trim((string) ($_POST['recurrence'] ?? 'none')), ['none', 'weekly', 'monthly', 'yearly'], true)
                ? trim((string) ($_POST['recurrence'] ?? 'none'))
                : 'none',
            'start_time' => trim((string) ($_POST['start_time'] ?? '')) ?: null,
            'end_time' => trim((string) ($_POST['end_time'] ?? '')) ?: null,
            'reason' => trim((string) ($_POST['reason'] ?? '')) ?: null,
            'status' => isset($_POST['status']) ? 1 : 0,
        ];
    }

    private function addonPayload(): array
    {
        return [
            'service_id' => max(1, (int) ($_POST['service_id'] ?? 0)),
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'price' => max(0, (float) ($_POST['price'] ?? 0)),
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
    }

    private function providerSettlementSummary(int $providerId, ?string $periodStart, ?string $periodEnd): array
    {
        $where = 'provider_id = :provider_id and booking_status = \'completed\'';
        $params = ['provider_id' => $providerId];
        if ($periodStart !== null) {
            $where .= ' and date(coalesce(completed_at, updated_at, created_at)) >= :period_start';
            $params['period_start'] = $periodStart;
        }
        if ($periodEnd !== null) {
            $where .= ' and date(coalesce(completed_at, updated_at, created_at)) <= :period_end';
            $params['period_end'] = $periodEnd;
        }
        $stmt = Database::connection()->prepare('select coalesce(sum(amount), 0) from service_bookings where ' . $where . Auth::zoneWhere('service_bookings'));
        $stmt->execute(Auth::zoneParams($params));
        $gross = (float) $stmt->fetchColumn();
        $provider = Database::connection()->prepare('select commission_percent from service_providers where id = :id' . Auth::zoneWhere('service_providers') . ' limit 1');
        $provider->execute(Auth::zoneParams(['id' => $providerId]));
        $commissionPercent = (float) ($provider->fetchColumn() ?: 0);
        $commission = round($gross * $commissionPercent / 100, 2);
        return [
            'gross_amount' => $gross,
            'commission_amount' => $commission,
            'payable_amount' => max(0, $gross - $commission),
        ];
    }

    private function providerReports(): array
    {
        return $this->providerScopedRows(
            'select service_providers.id, service_providers.name,
                    service_providers.commission_percent,
                    count(service_bookings.id) as booking_count,
                    coalesce(sum(service_bookings.amount), 0) as gross_amount,
                    coalesce(sum(service_bookings.amount * service_providers.commission_percent / 100), 0) as commission_amount
             from service_providers
             left join service_bookings on service_bookings.provider_id = service_providers.id
                and service_bookings.booking_status = \'completed\'
             where 1 = 1' . Auth::zoneWhere('service_providers') . '
             group by service_providers.id, service_providers.name, service_providers.commission_percent
             order by commission_amount desc, gross_amount desc'
        );
    }

    private function categoryReports(): array
    {
        $where = Auth::isZoneScoped() ? ' where service_bookings.id is null or service_bookings.zone_id = :admin_zone_id' : '';
        $stmt = Database::connection()->prepare(
            'select coalesce(service_categories.name, \'Uncategorized\') as category_name,
                    count(service_bookings.id) as booking_count,
                    sum(case when service_bookings.booking_status = \'completed\' then 1 else 0 end) as completed_count,
                    sum(case when service_bookings.booking_status in (\'cancelled\', \'cancellation_requested\') then 1 else 0 end) as cancelled_count,
                    coalesce(sum(service_bookings.amount), 0) as gross_amount
             from service_categories
             left join services on services.category_id = service_categories.id
             left join service_bookings on service_bookings.service_id = services.id
             ' . $where . '
             group by service_categories.id, service_categories.name
             order by booking_count desc, gross_amount desc'
        );
        $stmt->execute(Auth::zoneParams());
        return $stmt->fetchAll();
    }

    private function bookingStatusReports(): array
    {
        $stmt = Database::connection()->prepare(
            'select booking_status, count(*) as booking_count, coalesce(sum(amount), 0) as gross_amount
             from service_bookings
             where 1 = 1' . Auth::zoneWhere('service_bookings') . '
             group by booking_status
             order by booking_count desc'
        );
        $stmt->execute(Auth::zoneParams());
        return $stmt->fetchAll();
    }

    private function zoneId(): ?int
    {
        if (Auth::isZoneScoped()) {
            return Auth::zoneId();
        }
        $id = (int) ($_POST['zone_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function checklistJson(string $raw): ?string
    {
        $items = array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            preg_split('/\R/', $raw) ?: []
        ), static fn (string $item): bool => $item !== ''));

        if ($items === []) {
            return null;
        }

        return json_encode(array_slice($items, 0, 12), JSON_UNESCAPED_UNICODE);
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

    private function serviceScopedRows(string $sql): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(Auth::zoneParams());
        return $stmt->fetchAll();
    }

    private function providerScopedRows(string $sql): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(Auth::zoneParams());
        return $stmt->fetchAll();
    }

    private function csv(string $filename, array $header, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'wb');
        fputcsv($output, $header);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}
