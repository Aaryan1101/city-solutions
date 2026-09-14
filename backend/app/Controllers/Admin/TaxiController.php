<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\TaxiSchema;
use App\Support\Request;
use App\Support\Response;
use App\Support\Settings;
use App\Support\Upload;
use App\Support\View;
use App\Support\WalletSchema;
use App\Support\WalletService;
use App\Support\ZoneSchema;

final class TaxiController
{
    public function index(): void
    {
        Auth::requireAdmin();
        TaxiSchema::ensure();
        ZoneSchema::ensure();
        $db = Database::connection();
        $drivers = $db->prepare(
            'select taxi_drivers.*, zones.name as zone_name, taxi_vehicle_types.name as vehicle_type_name
             from taxi_drivers
             left join zones on zones.id = taxi_drivers.zone_id
             left join taxi_vehicle_types on taxi_vehicle_types.id = taxi_drivers.vehicle_type_id
             where 1 = 1' . Auth::zoneWhere('taxi_drivers') . '
             order by taxi_drivers.id desc'
        );
        $drivers->execute(Auth::zoneParams());

        $rides = $db->prepare(
            'select taxi_rides.*, taxi_vehicle_types.name as vehicle_type_name, taxi_drivers.name as driver_name
             from taxi_rides
             left join taxi_vehicle_types on taxi_vehicle_types.id = taxi_rides.vehicle_type_id
             left join taxi_drivers on taxi_drivers.id = taxi_rides.driver_id
             where 1 = 1' . Auth::zoneWhere('taxi_rides') . '
             order by taxi_rides.id desc
             limit 100'
        );
        $rides->execute(Auth::zoneParams());

        $vehicleTypes = $db->query('select * from taxi_vehicle_types order by sort_order asc, id asc')->fetchAll();
        $zones = Auth::isZoneScoped()
            ? array_values(array_filter(ZoneSchema::active(), fn (array $zone): bool => (int) $zone['id'] === Auth::zoneId()))
            : ZoneSchema::active();

        View::render('admin/taxi', [
            'title' => 'Taxi & Cab',
            'drivers' => $drivers->fetchAll(),
            'rides' => $rides->fetchAll(),
            'vehicleTypes' => $vehicleTypes,
            'zones' => $zones,
            'taxiSettings' => [
                'google_routes_api_key' => Settings::moduleGet('taxi', 'google_routes_api_key'),
                'driver_commission_percent' => Settings::moduleGet('taxi', 'driver_commission_percent', '15'),
                'quote_valid_minutes' => Settings::moduleGet('taxi', 'quote_valid_minutes', '5'),
                'cash_enabled' => Settings::moduleGet('taxi', 'cash_enabled', '1'),
                'wallet_enabled' => Settings::moduleGet('taxi', 'wallet_enabled', '1'),
                'support_phone' => Settings::moduleGet('taxi', 'support_phone'),
                'allow_fallback_quotes' => Settings::moduleGet('taxi', 'allow_fallback_quotes', '0'),
            ],
        ]);
    }

    public function updateSettings(): void
    {
        Auth::requireAdmin();
        Settings::setMany([
            Settings::moduleSettingKey('taxi', 'google_routes_api_key') => trim((string) Request::input('google_routes_api_key')),
            Settings::moduleSettingKey('taxi', 'driver_commission_percent') => max(0, min(100, (float) Request::input('driver_commission_percent', 15))),
            Settings::moduleSettingKey('taxi', 'quote_valid_minutes') => max(2, min(15, (int) Request::input('quote_valid_minutes', 5))),
            Settings::moduleSettingKey('taxi', 'cash_enabled') => (int) Request::input('cash_enabled', 0) === 1 ? '1' : '0',
            Settings::moduleSettingKey('taxi', 'wallet_enabled') => (int) Request::input('wallet_enabled', 0) === 1 ? '1' : '0',
            Settings::moduleSettingKey('taxi', 'support_phone') => trim((string) Request::input('support_phone')),
            Settings::moduleSettingKey('taxi', 'allow_fallback_quotes') => (int) Request::input('allow_fallback_quotes', 0) === 1 ? '1' : '0',
        ]);
        Response::redirect('/admin/taxi#taxi-settings');
    }

    public function storeDriver(): void
    {
        Auth::requireAdmin();
        TaxiSchema::ensure();
        $password = trim((string) Request::input('password'));
        $profilePhoto = Upload::image('profile_photo', 'taxi/drivers');
        $licenseDocument = Upload::image('license_document', 'taxi/documents');
        $vehicleDocument = Upload::image('vehicle_document', 'taxi/documents');
        $insuranceDocument = Upload::image('insurance_document', 'taxi/documents');
        $stmt = Database::connection()->prepare(
            'insert into taxi_drivers
             (zone_id, name, phone, email, password, vehicle_type_id, vehicle_name, vehicle_number, license_number, rc_number,
              profile_photo, license_document, vehicle_document, insurance_document, license_expiry, insurance_expiry,
              availability_status, status, admin_note, created_at, updated_at)
             values
             (:zone_id, :name, :phone, :email, :password, :vehicle_type_id, :vehicle_name, :vehicle_number, :license_number, :rc_number,
              :profile_photo, :license_document, :vehicle_document, :insurance_document, :license_expiry, :insurance_expiry,
              :availability_status, :status, :admin_note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'zone_id' => $this->zoneId(),
            'name' => trim((string) Request::input('name')),
            'phone' => trim((string) Request::input('phone')),
            'email' => trim((string) Request::input('email')) ?: null,
            'password' => $password === '' ? null : password_hash($password, PASSWORD_DEFAULT),
            'vehicle_type_id' => ((int) Request::input('vehicle_type_id')) > 0 ? (int) Request::input('vehicle_type_id') : null,
            'vehicle_name' => trim((string) Request::input('vehicle_name')) ?: null,
            'vehicle_number' => trim((string) Request::input('vehicle_number')) ?: null,
            'license_number' => trim((string) Request::input('license_number')) ?: null,
            'rc_number' => trim((string) Request::input('rc_number')) ?: null,
            'profile_photo' => $profilePhoto,
            'license_document' => $licenseDocument,
            'vehicle_document' => $vehicleDocument,
            'insurance_document' => $insuranceDocument,
            'license_expiry' => $this->nullableDate((string) Request::input('license_expiry')),
            'insurance_expiry' => $this->nullableDate((string) Request::input('insurance_expiry')),
            'availability_status' => 'offline',
            'status' => $this->clean((string) Request::input('status'), ['pending', 'approved', 'suspended'], 'approved'),
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ]);
        Response::redirect('/admin/taxi#drivers');
    }

    public function storeVehicleType(): void
    {
        Auth::requireAdmin();
        TaxiSchema::ensure();
        $name = trim((string) Request::input('name'));
        if ($name === '') {
            Response::redirect('/admin/taxi#vehicle-types');
        }
        $stmt = Database::connection()->prepare(
            'insert into taxi_vehicle_types
             (name, slug, seats, base_fare, per_km_fare, per_minute_fare, minimum_fare, cancellation_fee,
              service_fee, waiting_fee_per_minute, included_wait_minutes, icon, status, sort_order, created_at, updated_at)
             values
             (:name, :slug, :seats, :base_fare, :per_km_fare, :per_minute_fare, :minimum_fare, :cancellation_fee,
              :service_fee, :waiting_fee_per_minute, :included_wait_minutes, :icon, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->vehicleTypeParams($name));
        Response::redirect('/admin/taxi#vehicle-types');
    }

    public function updateVehicleType(int $id): void
    {
        Auth::requireAdmin();
        TaxiSchema::ensure();
        $name = trim((string) Request::input('name'));
        if ($name === '') {
            Response::redirect('/admin/taxi#vehicle-types');
        }
        $stmt = Database::connection()->prepare(
            'update taxi_vehicle_types
             set name = :name, slug = :slug, seats = :seats, base_fare = :base_fare,
                 per_km_fare = :per_km_fare, per_minute_fare = :per_minute_fare,
                 minimum_fare = :minimum_fare, cancellation_fee = :cancellation_fee,
                 service_fee = :service_fee, waiting_fee_per_minute = :waiting_fee_per_minute,
                 included_wait_minutes = :included_wait_minutes,
                 icon = :icon, status = :status, sort_order = :sort_order,
                 updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $params = $this->vehicleTypeParams($name);
        $params['id'] = $id;
        $stmt->execute($params);
        Response::redirect('/admin/taxi#vehicle-types');
    }

    public function updateDriver(int $id): void
    {
        Auth::requireAdmin();
        TaxiSchema::ensure();
        $current = Database::connection()->prepare('select * from taxi_drivers where id = :id' . Auth::zoneWhere('taxi_drivers') . ' limit 1');
        $current->execute(Auth::zoneParams(['id' => $id]));
        $driver = $current->fetch();
        if (!$driver) {
            Response::redirect('/admin/taxi#drivers');
        }
        $password = trim((string) Request::input('password'));
        $profilePhoto = Upload::image('profile_photo', 'taxi/drivers') ?: ($driver['profile_photo'] ?? null);
        $licenseDocument = Upload::image('license_document', 'taxi/documents') ?: ($driver['license_document'] ?? null);
        $vehicleDocument = Upload::image('vehicle_document', 'taxi/documents') ?: ($driver['vehicle_document'] ?? null);
        $insuranceDocument = Upload::image('insurance_document', 'taxi/documents') ?: ($driver['insurance_document'] ?? null);
        $passwordSql = $password === '' ? '' : ', password = :password';
        $stmt = Database::connection()->prepare(
            'update taxi_drivers set zone_id = :zone_id, name = :name, phone = :phone, email = :email,
             vehicle_type_id = :vehicle_type_id, vehicle_name = :vehicle_name, vehicle_number = :vehicle_number,
             license_number = :license_number, rc_number = :rc_number, profile_photo = :profile_photo,
             license_document = :license_document, vehicle_document = :vehicle_document,
             insurance_document = :insurance_document, license_expiry = :license_expiry,
             insurance_expiry = :insurance_expiry, status = :status, admin_note = :admin_note' . $passwordSql . ',
             updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $params = [
            'id' => $id,
            'zone_id' => $this->zoneId((int) ($driver['zone_id'] ?? 0)),
            'name' => trim((string) Request::input('name')),
            'phone' => trim((string) Request::input('phone')),
            'email' => trim((string) Request::input('email')) ?: null,
            'vehicle_type_id' => ((int) Request::input('vehicle_type_id')) > 0 ? (int) Request::input('vehicle_type_id') : null,
            'vehicle_name' => trim((string) Request::input('vehicle_name')) ?: null,
            'vehicle_number' => trim((string) Request::input('vehicle_number')) ?: null,
            'license_number' => trim((string) Request::input('license_number')) ?: null,
            'rc_number' => trim((string) Request::input('rc_number')) ?: null,
            'profile_photo' => $profilePhoto,
            'license_document' => $licenseDocument,
            'vehicle_document' => $vehicleDocument,
            'insurance_document' => $insuranceDocument,
            'license_expiry' => $this->nullableDate((string) Request::input('license_expiry')),
            'insurance_expiry' => $this->nullableDate((string) Request::input('insurance_expiry')),
            'status' => $this->clean((string) Request::input('status'), ['pending', 'approved', 'suspended'], 'approved'),
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ];
        if ($password !== '') {
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $stmt->execute($params);
        Response::redirect('/admin/taxi#drivers');
    }

    public function assignRide(int $id): void
    {
        Auth::requireAdmin();
        TaxiSchema::ensure();
        $driverId = (int) Request::input('driver_id');
        if ($driverId <= 0) {
            Response::redirect('/admin/taxi#rides');
        }
        $db = Database::connection();
        $rideStmt = $db->prepare('select * from taxi_rides where id = :id' . Auth::zoneWhere('taxi_rides') . ' limit 1');
        $rideStmt->execute(Auth::zoneParams(['id' => $id]));
        $ride = $rideStmt->fetch();
        if (!$ride || (string) $ride['ride_status'] !== 'requested' || !empty($ride['driver_id'])) {
            Response::redirect('/admin/taxi#rides');
        }
        $driverStmt = $db->prepare(
            'select * from taxi_drivers where id = :id and status = \'approved\' and availability_status = \'online\' and last_seen_at >= :fresh_after and license_document is not null and vehicle_document is not null and insurance_document is not null and license_expiry >= CURRENT_DATE and insurance_expiry >= CURRENT_DATE limit 1'
        );
        $driverStmt->execute(['id' => $driverId, 'fresh_after' => date('Y-m-d H:i:s', time() - 600)]);
        $driver = $driverStmt->fetch();
        $zoneMatches = $driver && ((int) ($driver['zone_id'] ?? 0) === 0 || (int) ($driver['zone_id'] ?? 0) === (int) ($ride['zone_id'] ?? 0));
        $typeMatches = $driver && ((int) ($driver['vehicle_type_id'] ?? 0) === 0 || (int) ($driver['vehicle_type_id'] ?? 0) === (int) ($ride['vehicle_type_id'] ?? 0));
        if (!$driver || !$zoneMatches || !$typeMatches) {
            Response::redirect('/admin/taxi#rides');
        }
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'update taxi_rides set driver_id = :driver_id, ride_status = \'accepted\', accepted_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP where id = :id and driver_id is null and ride_status = \'requested\''
            );
            $stmt->execute(['id' => $id, 'driver_id' => $driverId]);
            if ($stmt->rowCount() !== 1) {
                $db->rollBack();
                Response::redirect('/admin/taxi#rides');
            }
            $db->prepare('update taxi_drivers set availability_status = \'busy\', updated_at = CURRENT_TIMESTAMP where id = :id')->execute(['id' => $driverId]);
            TaxiSchema::recordStatus($id, 'accepted', 'admin', $_SESSION['admin_name'] ?? 'Admin', 'Driver assigned by dispatch');
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
        NotificationLog::record('taxi_driver', $driverId, null, 'Taxi ride assigned', 'Ride #' . $id . ' has been assigned to you.', $id, 'taxi');
        Response::redirect('/admin/taxi#rides');
    }

    public function rideStatus(int $id): void
    {
        Auth::requireAdmin();
        TaxiSchema::ensure();
        $db = Database::connection();
        $currentStmt = $db->prepare('select * from taxi_rides where id = :id' . Auth::zoneWhere('taxi_rides') . ' limit 1');
        $currentStmt->execute(Auth::zoneParams(['id' => $id]));
        $current = $currentStmt->fetch();
        if (!$current) {
            Response::redirect('/admin/taxi#rides');
        }
        $status = $this->clean((string) Request::input('ride_status'), ['requested', 'accepted', 'arrived', 'started', 'completed', 'cancelled', 'rejected'], 'requested');
        $paymentStatus = $this->clean((string) Request::input('payment_status'), ['unpaid', 'pending', 'paid', 'failed', 'refunded', 'partially_refunded'], (string) $current['payment_status']);
        $transitions = [
            'requested' => ['requested', 'accepted', 'cancelled', 'rejected'],
            'accepted' => ['accepted', 'arrived', 'cancelled'],
            'arrived' => ['arrived', 'started', 'cancelled'],
            'started' => ['started', 'completed'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
            'rejected' => ['rejected'],
        ];
        if (!in_array($status, $transitions[(string) $current['ride_status']] ?? [], true)) {
            Response::redirect('/admin/taxi#rides');
        }
        if ($status === 'accepted' && empty($current['driver_id'])) {
            Response::redirect('/admin/taxi#rides');
        }
        $timeField = match ($status) {
            'accepted' => ', accepted_at = CURRENT_TIMESTAMP',
            'arrived' => ', arrived_at = CURRENT_TIMESTAMP',
            'started' => ', started_at = CURRENT_TIMESTAMP',
            'completed' => ', completed_at = CURRENT_TIMESTAMP',
            'cancelled', 'rejected' => ', cancelled_at = CURRENT_TIMESTAMP, cancelled_by = \'admin\'',
            default => '',
        };
        $willRefundWallet = in_array($status, ['cancelled', 'rejected'], true)
            && (int) ($current['customer_id'] ?? 0) > 0
            && (string) $current['payment_method'] === 'wallet'
            && (string) $current['payment_status'] === 'paid';
        if ($willRefundWallet) {
            WalletSchema::ensure();
        }
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'update taxi_rides set ride_status = :ride_status, payment_status = :payment_status,
                 final_fare = :final_fare, cancellation_reason = :cancellation_reason, updated_at = CURRENT_TIMESTAMP' . $timeField . '
                 where id = :id and ride_status = :previous_status' . Auth::zoneWhere('taxi_rides')
            );
            $stmt->execute(Auth::zoneParams([
                'id' => $id,
                'previous_status' => (string) $current['ride_status'],
                'ride_status' => $status,
                'payment_status' => $paymentStatus,
                'final_fare' => max(0, (float) Request::input('final_fare', $current['final_fare'] ?? $current['estimated_fare'] ?? 0)),
                'cancellation_reason' => trim((string) Request::input('cancellation_reason')) ?: null,
            ]));
            if ($stmt->rowCount() !== 1) {
                $db->rollBack();
                Response::redirect('/admin/taxi#rides');
            }
            $rideStmt = $db->prepare('select * from taxi_rides where id = :id limit 1');
            $rideStmt->execute(['id' => $id]);
            $ride = $rideStmt->fetch();
            if ($ride && $status === 'completed') {
                $this->settleDriver($ride);
                if (!empty($ride['driver_id'])) {
                    $db->prepare('update taxi_drivers set availability_status = \'online\', updated_at = CURRENT_TIMESTAMP where id = :id')->execute(['id' => (int) $ride['driver_id']]);
                }
            }
            if ($ride && in_array($status, ['cancelled', 'rejected'], true) && !empty($ride['driver_id'])) {
                $db->prepare('update taxi_drivers set availability_status = \'online\', updated_at = CURRENT_TIMESTAMP where id = :id')->execute(['id' => (int) $ride['driver_id']]);
            }
            if ($ride && $willRefundWallet) {
                $refund = max(0, (float) $current['final_fare']);
                WalletService::credit('customer', 'customer-' . (int) $current['customer_id'], $refund, 'taxi_admin_refund', 'taxi-admin-refund-' . $id, 'Taxi ride cancellation refund');
                $db->prepare('update taxi_rides set payment_status = \'refunded\' where id = :id')->execute(['id' => $id]);
            }
            TaxiSchema::recordStatus($id, $status, 'admin', $_SESSION['admin_name'] ?? 'Admin', trim((string) Request::input('admin_note')));
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
        NotificationLog::record('customer', null, (string) ($ride['guest_id'] ?? ''), 'Taxi ride updated', 'Your ride ' . (string) ($ride['ride_number'] ?? ('#' . $id)) . ' is now ' . str_replace('_', ' ', $status) . '.', $id, 'taxi');
        Response::redirect('/admin/taxi#rides');
    }

    private function zoneId(int $fallback = 0): ?int
    {
        if (Auth::isZoneScoped()) {
            return Auth::zoneId();
        }
        $id = (int) Request::input('zone_id', $fallback);
        return $id > 0 ? $id : null;
    }

    private function clean(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function nullableDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function vehicleTypeParams(string $name): array
    {
        return [
            'name' => $name,
            'slug' => $this->slug($name),
            'seats' => max(1, (int) Request::input('seats', 4)),
            'base_fare' => max(0, (float) Request::input('base_fare', 0)),
            'per_km_fare' => max(0, (float) Request::input('per_km_fare', 0)),
            'per_minute_fare' => max(0, (float) Request::input('per_minute_fare', 0)),
            'minimum_fare' => max(0, (float) Request::input('minimum_fare', 0)),
            'cancellation_fee' => max(0, (float) Request::input('cancellation_fee', 0)),
            'service_fee' => max(0, (float) Request::input('service_fee', 0)),
            'waiting_fee_per_minute' => max(0, (float) Request::input('waiting_fee_per_minute', 0)),
            'included_wait_minutes' => max(0, (int) Request::input('included_wait_minutes', 3)),
            'icon' => trim((string) Request::input('icon')) ?: 'local_taxi',
            'status' => (int) Request::input('status', 1) === 1 ? 1 : 0,
            'sort_order' => (int) Request::input('sort_order', 0),
        ];
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
        return $slug !== '' ? $slug : 'taxi-type';
    }

    private function settleDriver(array $ride): void
    {
        $driverId = (int) ($ride['driver_id'] ?? 0);
        if ($driverId <= 0) {
            return;
        }
        $fare = (float) ($ride['final_fare'] ?? $ride['estimated_fare'] ?? 0);
        if ($fare <= 0) {
            return;
        }
        $commissionPercent = (float) Settings::moduleGet('taxi', 'driver_commission_percent', '15');
        $commission = round(($fare * $commissionPercent) / 100, 2);
        $earning = max(0, round($fare - $commission, 2));
        Database::connection()->prepare(
            'update taxi_rides set driver_earning = :earning, commission_amount = :commission, updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute(['id' => (int) $ride['id'], 'earning' => $earning, 'commission' => $commission]);
        $db = Database::connection();
        $upsert = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' on duplicate key update amount = values(amount), description = values(description), updated_at = CURRENT_TIMESTAMP'
            : ' on conflict(driver_id, ride_id, entry_type) do update set amount = excluded.amount, description = excluded.description, updated_at = CURRENT_TIMESTAMP';
        $db->prepare(
            'insert into taxi_driver_ledgers (driver_id, ride_id, direction, amount, entry_type, description, created_at, updated_at)
             values (:driver_id, :ride_id, \'credit\', :amount, \'ride_earning\', :description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)' . $upsert
        )->execute([
            'driver_id' => $driverId,
            'ride_id' => (int) $ride['id'],
            'amount' => $earning,
            'description' => 'Ride earning for ' . (string) $ride['ride_number'],
        ]);
    }
}
