<?php

declare(strict_types=1);

namespace App\Controllers\ServiceProvider;

use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\Response;
use App\Support\ServiceProviderAuth;
use App\Support\ServiceSchema;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        ServiceProviderAuth::requireProvider();
        ServiceSchema::ensure();
        NotificationSchema::ensure();
        $db = Database::connection();
        $providerId = ServiceProviderAuth::id();

        $providerStmt = $db->prepare('select * from service_providers where id = :id limit 1');
        $providerStmt->execute(['id' => $providerId]);
        $provider = $providerStmt->fetch();

        $statsStmt = $db->prepare(
            'select count(*) as total_bookings,
                    sum(case when booking_status in (\'pending\', \'accepted\', \'ongoing\') then 1 else 0 end) as active_bookings,
                    sum(case when booking_status = \'completed\' then 1 else 0 end) as completed_bookings,
                    coalesce(sum(case when booking_status = \'completed\' then amount else 0 end), 0) as completed_amount
             from service_bookings
             where provider_id = :provider_id'
        );
        $statsStmt->execute(['provider_id' => $providerId]);
        $stats = $statsStmt->fetch();
        $commissionPercent = (float) ($provider['commission_percent'] ?? 0);
        $completedAmount = (float) ($stats['completed_amount'] ?? 0);
        $stats['commission_percent'] = $commissionPercent;
        $stats['commission_amount'] = round($completedAmount * $commissionPercent / 100, 2);
        $stats['payable_amount'] = max(0, $completedAmount - (float) $stats['commission_amount']);

        $bookingsStmt = $db->prepare(
            'select service_bookings.*, services.name as service_name
             from service_bookings
             left join services on services.id = service_bookings.service_id
             where service_bookings.provider_id = :provider_id
             order by service_bookings.id desc
             limit 100'
        );
        $bookingsStmt->execute(['provider_id' => $providerId]);
        $settlementsStmt = $db->prepare(
            'select * from service_provider_settlements
             where provider_id = :provider_id
             order by id desc
             limit 20'
        );
        $settlementsStmt->execute(['provider_id' => $providerId]);
        $blackoutsStmt = $db->prepare(
            'select service_blackouts.*, services.name as service_name
             from service_blackouts
             left join services on services.id = service_blackouts.service_id
             where service_blackouts.provider_id = :provider_id
             order by service_blackouts.blackout_date desc, service_blackouts.id desc
             limit 50'
        );
        $blackoutsStmt->execute(['provider_id' => $providerId]);
        $servicesStmt = $db->prepare('select id, name from services where provider_id = :provider_id and status = 1 order by name asc');
        $servicesStmt->execute(['provider_id' => $providerId]);
        $notificationsStmt = $db->prepare(
            'select * from notifications
             where recipient_type = \'provider\' and recipient_id = :provider_id
             order by id desc
             limit 30'
        );
        $notificationsStmt->execute(['provider_id' => $providerId]);

        View::render('service_provider/dashboard', [
            'title' => 'Service Provider Dashboard',
            'provider' => $provider,
            'stats' => $stats,
            'bookings' => $bookingsStmt->fetchAll(),
            'settlements' => $settlementsStmt->fetchAll(),
            'notifications' => $notificationsStmt->fetchAll(),
            'blackouts' => $blackoutsStmt->fetchAll(),
            'services' => $servicesStmt->fetchAll(),
        ]);
    }

    public function status(int $id): void
    {
        ServiceProviderAuth::requireProvider();
        ServiceSchema::ensure();
        $allowed = ['accepted', 'ongoing', 'completed'];
        $status = $_POST['booking_status'] ?? 'accepted';
        if (!in_array($status, $allowed, true)) {
            $status = 'accepted';
        }
        $completedAtSql = $status === 'completed' ? ', completed_at = CURRENT_TIMESTAMP' : '';
        $stmt = Database::connection()->prepare(
            'update service_bookings
             set booking_status = :status' . $completedAtSql . ', updated_at = CURRENT_TIMESTAMP
             where id = :id and provider_id = :provider_id and booking_status != \'cancelled\''
        );
        $stmt->execute([
            'id' => $id,
            'provider_id' => ServiceProviderAuth::id(),
            'status' => $status,
        ]);
        $bookingStmt = Database::connection()->prepare('select * from service_bookings where id = :id limit 1');
        $bookingStmt->execute(['id' => $id]);
        $booking = $bookingStmt->fetch();
        if ($booking) {
            NotificationLog::record(
                'customer',
                null,
                (string) ($booking['guest_id'] ?? ''),
                'Booking status updated',
                'Your booking ' . $booking['booking_number'] . ' is now ' . $status . '.',
                null,
                'services'
            );
        }
        Response::redirect('/service-provider');
    }

    public function profile(): void
    {
        ServiceProviderAuth::requireProvider();
        ServiceSchema::ensure();
        $providerId = ServiceProviderAuth::id();
        $password = trim((string) ($_POST['password'] ?? ''));
        $passwordSql = $password === '' ? '' : ', password = :password';
        $params = [
            'id' => $providerId,
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'email' => trim($_POST['email'] ?? '') ?: null,
            'area' => trim($_POST['area'] ?? '') ?: null,
        ];
        if ($password !== '') {
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $stmt = Database::connection()->prepare(
            'update service_providers
             set name = :name, phone = :phone, email = :email, area = :area' . $passwordSql . ', updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $stmt->execute($params);
        $_SESSION['service_provider_name'] = trim($_POST['name'] ?? '');
        Response::redirect('/service-provider');
    }

    public function storeBlackout(): void
    {
        ServiceProviderAuth::requireProvider();
        ServiceSchema::ensure();
        $recurrence = trim((string) ($_POST['recurrence'] ?? 'none'));
        if (!in_array($recurrence, ['none', 'weekly', 'monthly', 'yearly'], true)) {
            $recurrence = 'none';
        }
        Database::connection()->prepare(
            'insert into service_blackouts
             (service_id, provider_id, blackout_date, end_date, recurrence, start_time, end_time, reason, status, created_at, updated_at)
             values (:service_id, :provider_id, :blackout_date, :end_date, :recurrence, :start_time, :end_time, :reason, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        )->execute([
            'service_id' => (int) ($_POST['service_id'] ?? 0) ?: null,
            'provider_id' => ServiceProviderAuth::id(),
            'blackout_date' => trim((string) ($_POST['blackout_date'] ?? date('Y-m-d'))),
            'end_date' => trim((string) ($_POST['end_date'] ?? '')) ?: null,
            'recurrence' => $recurrence,
            'start_time' => trim((string) ($_POST['start_time'] ?? '')) ?: null,
            'end_time' => trim((string) ($_POST['end_time'] ?? '')) ?: null,
            'reason' => trim((string) ($_POST['reason'] ?? '')) ?: null,
        ]);
        Response::redirect('/service-provider');
    }

    public function archiveBlackout(int $id): void
    {
        ServiceProviderAuth::requireProvider();
        ServiceSchema::ensure();
        Database::connection()->prepare(
            'update service_blackouts set status = 0, updated_at = CURRENT_TIMESTAMP where id = :id and provider_id = :provider_id'
        )->execute(['id' => $id, 'provider_id' => ServiceProviderAuth::id()]);
        Response::redirect('/service-provider');
    }
}
