<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\DeliverySchema;
use App\Support\NotificationLog;
use App\Support\OrderStatusHistory;
use App\Support\Request;
use App\Support\Response;
use App\Support\Security;
use App\Support\ServiceSchema;
use App\Support\Settings;
use App\Support\TaxiSchema;
use App\Support\WalletSchema;
use App\Support\WalletService;

final class WorkerController
{
    public function login(): void
    {
        DeliverySchema::ensure();
        ServiceSchema::ensure();
        TaxiSchema::ensure();
        $body = Request::json();
        $phone = trim((string) ($body['phone'] ?? ''));
        $password = trim((string) ($body['password'] ?? ''));
        if ($phone === '' || $password === '') {
            Response::json(['message' => 'Phone and password are required'], 422);
            return;
        }
        Security::enforceLoginThrottle('worker', $phone);

        foreach ([
            ['delivery_man', 'delivery_men', 'status = 1'],
            ['service_provider', 'service_providers', 'status = 1'],
            ['taxi_driver', 'taxi_drivers', 'status = \'approved\''],
        ] as [$role, $table, $statusSql]) {
            $stmt = Database::connection()->prepare('select * from ' . $table . ' where phone = :phone and ' . $statusSql . ' limit 1');
            $stmt->execute(['phone' => $phone]);
            $worker = $stmt->fetch();
            if (!$worker || empty($worker['password']) || !password_verify($password, (string) $worker['password'])) {
                continue;
            }
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + ((int) Settings::get('worker_session_timeout_hours', '168') * 3600));
            Database::connection()->prepare('update ' . $table . ' set auth_token = :token, auth_token_expires_at = :expires_at, last_seen_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP where id = :id')
                ->execute(['token' => hash('sha256', $token), 'expires_at' => $expiresAt, 'id' => (int) $worker['id']]);
            Response::json([
                'message' => 'Logged in',
                'token' => $token,
                'worker' => $this->publicWorker($worker, $role),
            ]);
            return;
        }

        Response::json(['message' => 'Invalid worker credentials'], 401);
    }

    public function me(): void
    {
        $worker = $this->requireWorker();
        Response::json(['data' => $this->publicWorker($worker['row'], $worker['role'])]);
    }

    public function assignments(): void
    {
        $worker = $this->requireWorker();
        Response::json(['data' => match ($worker['role']) {
            'delivery_man' => $this->deliveryAssignments((int) $worker['row']['id']),
            'service_provider' => $this->serviceAssignments((int) $worker['row']['id']),
            'taxi_driver' => $this->taxiAssignments((int) $worker['row']['id']),
            default => [],
        }]);
    }

    public function updateAssignment(): void
    {
        $worker = $this->requireWorker();
        $body = Request::json();
        $type = trim((string) ($body['type'] ?? ''));
        $id = (int) ($body['id'] ?? 0);
        $status = trim((string) ($body['status'] ?? ''));
        if ($id <= 0 || $status === '') {
            Response::json(['message' => 'Assignment id and status are required'], 422);
            return;
        }

        if ($type === 'order' && $worker['role'] === 'delivery_man') {
            $this->updateOrder((int) $worker['row']['id'], $id, $status, (string) $worker['row']['name']);
            return;
        }
        if ($type === 'service_booking' && $worker['role'] === 'service_provider') {
            $this->updateServiceBooking((int) $worker['row']['id'], $id, $status);
            return;
        }
        if ($type === 'taxi_ride' && $worker['role'] === 'taxi_driver') {
            $this->updateTaxiRide((int) $worker['row']['id'], $id, $status, (string) $worker['row']['name'], $body);
            return;
        }

        Response::json(['message' => 'Assignment type is not allowed for this worker'], 403);
    }

    public function updateLocation(): void
    {
        $worker = $this->requireWorker();
        $body = Request::json();
        $lat = (float) ($body['latitude'] ?? 0);
        $lng = (float) ($body['longitude'] ?? 0);
        $available = trim((string) ($body['availability_status'] ?? ''));
        $table = match ($worker['role']) {
            'delivery_man' => 'delivery_men',
            'service_provider' => 'service_providers',
            'taxi_driver' => 'taxi_drivers',
            default => '',
        };
        if ($table === '') {
            Response::json(['message' => 'Unsupported worker role'], 422);
            return;
        }
        $availabilitySql = '';
        $params = ['id' => (int) $worker['row']['id']];
        if (in_array($available, ['offline', 'online', 'busy'], true)) {
            $availabilitySql = ', availability_status = :availability_status';
            $params['availability_status'] = $available;
        }
        $columns = $table === 'service_providers'
            ? 'last_seen_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP'
            : 'current_latitude = :latitude, current_longitude = :longitude, last_seen_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP';
        if ($table !== 'service_providers') {
            $params['latitude'] = $lat;
            $params['longitude'] = $lng;
        }
        Database::connection()->prepare('update ' . $table . ' set ' . $columns . $availabilitySql . ' where id = :id')->execute($params);
        Response::json(['message' => 'Worker status updated']);
    }

    private function requireWorker(): array
    {
        DeliverySchema::ensure();
        ServiceSchema::ensure();
        TaxiSchema::ensure();
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            $token = trim(substr($header, 7));
        } else {
            $token = trim((string) ($_GET['token'] ?? ''));
        }
        if ($token === '') {
            Response::json(['message' => 'Worker token is required'], 401);
            exit;
        }
        $hash = hash('sha256', $token);
        foreach ([
            ['delivery_man', 'delivery_men', 'status = 1'],
            ['service_provider', 'service_providers', 'status = 1'],
            ['taxi_driver', 'taxi_drivers', 'status = \'approved\''],
        ] as [$role, $table, $statusSql]) {
            $stmt = Database::connection()->prepare('select * from ' . $table . ' where auth_token = :token and ' . $statusSql . ' limit 1');
            $stmt->execute(['token' => $hash]);
            $row = $stmt->fetch();
            if ($row) {
                $expiresAt = strtotime((string) ($row['auth_token_expires_at'] ?? ''));
                if ($expiresAt > 0 && $expiresAt < time()) {
                    Database::connection()->prepare('update ' . $table . ' set auth_token = null, auth_token_expires_at = null, updated_at = CURRENT_TIMESTAMP where id = :id')
                        ->execute(['id' => (int) $row['id']]);
                    Response::json(['message' => 'Worker session expired'], 401);
                    exit;
                }
                return ['role' => $role, 'row' => $row];
            }
        }
        Response::json(['message' => 'Worker session expired'], 401);
        exit;
    }

    private function deliveryAssignments(int $workerId): array
    {
        $stmt = Database::connection()->prepare(
            'select orders.*, delivery_men.name as assignee_name
             from orders
             left join delivery_men on delivery_men.id = orders.delivery_man_id
             where orders.delivery_man_id = :worker_id and orders.order_status not in (\'delivered\', \'cancelled\', \'refunded\')
             order by orders.id desc'
        );
        $stmt->execute(['worker_id' => $workerId]);
        return array_map(static fn (array $row): array => [
            'type' => 'order',
            'id' => (int) $row['id'],
            'module_key' => (string) $row['module_key'],
            'number' => (string) $row['order_number'],
            'title' => ucfirst((string) $row['module_key']) . ' order',
            'customer_name' => (string) $row['customer_name'],
            'customer_phone' => (string) $row['customer_phone'],
            'address' => (string) $row['address'],
            'amount' => (float) $row['order_amount'],
            'payment_method' => (string) $row['payment_method'],
            'payment_status' => (string) $row['payment_status'],
            'status' => (string) $row['order_status'],
            'created_at' => (string) $row['created_at'],
        ], $stmt->fetchAll());
    }

    private function serviceAssignments(int $providerId): array
    {
        $stmt = Database::connection()->prepare(
            'select service_bookings.*, services.name as service_name
             from service_bookings
             join services on services.id = service_bookings.service_id
             where service_bookings.provider_id = :provider_id and service_bookings.booking_status not in (\'completed\', \'cancelled\', \'rejected\')
             order by service_bookings.id desc'
        );
        $stmt->execute(['provider_id' => $providerId]);
        return array_map(static fn (array $row): array => [
            'type' => 'service_booking',
            'id' => (int) $row['id'],
            'module_key' => 'services',
            'number' => (string) $row['booking_number'],
            'title' => (string) $row['service_name'],
            'customer_name' => (string) $row['customer_name'],
            'customer_phone' => (string) $row['customer_phone'],
            'address' => (string) $row['address'],
            'amount' => (float) $row['amount'],
            'payment_method' => (string) $row['payment_method'],
            'payment_status' => (string) $row['payment_status'],
            'status' => (string) $row['booking_status'],
            'scheduled_at' => trim((string) $row['preferred_date'] . ' ' . (string) $row['preferred_time']),
            'created_at' => (string) $row['created_at'],
        ], $stmt->fetchAll());
    }

    private function taxiAssignments(int $driverId): array
    {
        $driverStmt = Database::connection()->prepare('select * from taxi_drivers where id = :id limit 1');
        $driverStmt->execute(['id' => $driverId]);
        $driver = $driverStmt->fetch();
        if (!$driver) {
            return [];
        }
        $stmt = Database::connection()->prepare(
            'select taxi_rides.*, taxi_vehicle_types.name as vehicle_type_name
             from taxi_rides
             left join taxi_vehicle_types on taxi_vehicle_types.id = taxi_rides.vehicle_type_id
             where (
	                 (taxi_rides.driver_id = :assigned_driver_id and taxi_rides.ride_status not in (\'completed\', \'cancelled\', \'rejected\'))
                 or (
                     taxi_rides.driver_id is null and taxi_rides.ride_status = \'requested\'
                     and (:availability_status = \'online\')
                     and (:kyc_ready = 1)
	                     and (:driver_zone_id = 0 or taxi_rides.zone_id is null or taxi_rides.zone_id = :ride_zone_id)
	                     and (:driver_vehicle_type_id = 0 or taxi_rides.vehicle_type_id = :ride_vehicle_type_id)
                     and not exists (
                         select 1 from taxi_ride_driver_responses responses
	                         where responses.ride_id = taxi_rides.id and responses.driver_id = :response_driver_id
                     )
                 )
             )
             order by taxi_rides.id desc'
        );
        $stmt->execute([
	            'assigned_driver_id' => $driverId,
	            'response_driver_id' => $driverId,
            'availability_status' => (string) ($driver['availability_status'] ?? 'offline'),
            'kyc_ready' => (!empty($driver['license_document'])
                && !empty($driver['vehicle_document'])
                && !empty($driver['insurance_document'])
                && (string) ($driver['license_expiry'] ?? '') >= date('Y-m-d')
                && (string) ($driver['insurance_expiry'] ?? '') >= date('Y-m-d')) ? 1 : 0,
	            'driver_zone_id' => (int) ($driver['zone_id'] ?? 0),
	            'ride_zone_id' => (int) ($driver['zone_id'] ?? 0),
	            'driver_vehicle_type_id' => (int) ($driver['vehicle_type_id'] ?? 0),
	            'ride_vehicle_type_id' => (int) ($driver['vehicle_type_id'] ?? 0),
        ]);
        return array_map(static fn (array $row): array => [
            'type' => 'taxi_ride',
            'id' => (int) $row['id'],
            'module_key' => 'taxi',
            'number' => (string) $row['ride_number'],
            'title' => (string) ($row['vehicle_type_name'] ?? 'Taxi ride'),
            'customer_name' => (string) $row['customer_name'],
            'customer_phone' => (string) $row['customer_phone'],
            'pickup_address' => (string) $row['pickup_address'],
            'drop_address' => (string) $row['drop_address'],
            'amount' => (float) $row['final_fare'],
            'payment_method' => (string) $row['payment_method'],
            'payment_status' => (string) $row['payment_status'],
            'status' => (string) $row['ride_status'],
            'is_offer' => empty($row['driver_id']),
            'pickup_latitude' => (float) ($row['pickup_latitude'] ?? 0),
            'pickup_longitude' => (float) ($row['pickup_longitude'] ?? 0),
            'drop_latitude' => (float) ($row['drop_latitude'] ?? 0),
            'drop_longitude' => (float) ($row['drop_longitude'] ?? 0),
            'created_at' => (string) $row['created_at'],
        ], $stmt->fetchAll());
    }

    private function updateOrder(int $workerId, int $id, string $status, string $actorName): void
    {
        $allowed = ['confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            Response::json(['message' => 'Invalid order status'], 422);
            return;
        }
        $stmt = Database::connection()->prepare('update orders set order_status = :status, payment_status = case when :status = \'delivered\' and payment_method = \'cash_on_delivery\' then \'paid\' else payment_status end, updated_at = CURRENT_TIMESTAMP where id = :id and delivery_man_id = :worker_id');
        $stmt->execute(['status' => $status, 'id' => $id, 'worker_id' => $workerId]);
        if ($stmt->rowCount() === 0) {
            Response::json(['message' => 'Assignment not found'], 404);
            return;
        }
        OrderStatusHistory::record($id, null, $status, 'delivery_man', $actorName, 'Updated from worker app');
        NotificationLog::record('admin', null, null, 'Order status updated', 'Order #' . $id . ' marked ' . $status . ' by ' . $actorName, $id);
        Response::json(['message' => 'Order updated']);
    }

    private function updateServiceBooking(int $providerId, int $id, string $status): void
    {
        $allowed = ['confirmed', 'on_the_way', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            Response::json(['message' => 'Invalid booking status'], 422);
            return;
        }
        $timeSql = $status === 'completed' ? ', completed_at = CURRENT_TIMESTAMP' : '';
        $stmt = Database::connection()->prepare('update service_bookings set booking_status = :status, updated_at = CURRENT_TIMESTAMP' . $timeSql . ' where id = :id and provider_id = :provider_id');
        $stmt->execute(['status' => $status, 'id' => $id, 'provider_id' => $providerId]);
        if ($stmt->rowCount() === 0) {
            Response::json(['message' => 'Assignment not found'], 404);
            return;
        }
        Response::json(['message' => 'Booking updated']);
    }

    private function updateTaxiRide(int $driverId, int $id, string $status, string $actorName, array $body): void
    {
        $allowed = ['accepted', 'declined', 'arrived', 'started', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            Response::json(['message' => 'Invalid ride status'], 422);
            return;
        }
        $db = Database::connection();
        if ($status === 'declined') {
            $stmt = $db->prepare(
                'insert into taxi_ride_driver_responses (ride_id, driver_id, response, created_at)
                 values (:ride_id, :driver_id, \'declined\', CURRENT_TIMESTAMP)'
            );
            try {
                $stmt->execute(['ride_id' => $id, 'driver_id' => $driverId]);
            } catch (\Throwable) {
                // A repeated decline remains idempotent.
            }
            Response::json(['message' => 'Ride offer declined']);
            return;
        }

        if ($status === 'accepted') {
            $db->beginTransaction();
            try {
                $driverStmt = $db->prepare('select * from taxi_drivers where id = :id and status = \'approved\' and availability_status = \'online\' and last_seen_at >= :fresh_after and license_document is not null and vehicle_document is not null and insurance_document is not null and license_expiry >= CURRENT_DATE and insurance_expiry >= CURRENT_DATE limit 1');
                $driverStmt->execute(['id' => $driverId, 'fresh_after' => date('Y-m-d H:i:s', time() - 600)]);
                $driver = $driverStmt->fetch();
                if (!$driver) {
                    $db->rollBack();
                    Response::json(['message' => 'Go online before accepting a ride'], 409);
                    return;
                }
                $claim = $db->prepare(
                    'update taxi_rides set driver_id = :driver_id, ride_status = \'accepted\', accepted_at = CURRENT_TIMESTAMP,
                     updated_at = CURRENT_TIMESTAMP where id = :id and driver_id is null and ride_status = \'requested\'
                     and (:driver_zone_id = 0 or zone_id is null or zone_id = :ride_zone_id)
                     and (:driver_vehicle_type_id = 0 or vehicle_type_id = :ride_vehicle_type_id)'
                );
                $claim->execute([
                    'id' => $id,
                    'driver_id' => $driverId,
                    'driver_zone_id' => (int) ($driver['zone_id'] ?? 0),
                    'ride_zone_id' => (int) ($driver['zone_id'] ?? 0),
                    'driver_vehicle_type_id' => (int) ($driver['vehicle_type_id'] ?? 0),
                    'ride_vehicle_type_id' => (int) ($driver['vehicle_type_id'] ?? 0),
                ]);
                if ($claim->rowCount() !== 1) {
                    $db->rollBack();
                    Response::json(['message' => 'This ride was accepted by another driver'], 409);
                    return;
                }
                $driverUpdate = $db->prepare(
                    'update taxi_drivers set availability_status = \'busy\', updated_at = CURRENT_TIMESTAMP
                     where id = :id and status = \'approved\' and availability_status = \'online\''
                );
                $driverUpdate->execute(['id' => $driverId]);
                if ($driverUpdate->rowCount() !== 1) {
                    $db->rollBack();
                    Response::json(['message' => 'Go online before accepting a ride'], 409);
                    return;
                }
                TaxiSchema::recordStatus($id, 'accepted', 'taxi_driver', $actorName, 'Driver accepted ride');
                $db->commit();
                $acceptedStmt = $db->prepare('select guest_id, ride_number from taxi_rides where id = :id limit 1');
                $acceptedStmt->execute(['id' => $id]);
                $acceptedRide = $acceptedStmt->fetch();
                if ($acceptedRide) {
                    NotificationLog::record('customer', null, (string) $acceptedRide['guest_id'], 'Driver assigned', $actorName . ' accepted your ride ' . (string) $acceptedRide['ride_number'] . '.', $id, 'taxi');
                }
                Response::json(['message' => 'Ride accepted']);
                return;
            } catch (\Throwable $error) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $error;
            }
        }

        $rideStmt = $db->prepare('select * from taxi_rides where id = :id and driver_id = :driver_id limit 1');
        $rideStmt->execute(['id' => $id, 'driver_id' => $driverId]);
        $ride = $rideStmt->fetch();
        if (!$ride) {
            Response::json(['message' => 'Assignment not found'], 404);
            return;
        }
        $next = [
            'accepted' => ['arrived', 'cancelled'],
            'arrived' => ['started', 'cancelled'],
            'started' => ['completed'],
        ];
        if (!in_array($status, $next[(string) $ride['ride_status']] ?? [], true)) {
            Response::json(['message' => 'Invalid trip transition from ' . (string) $ride['ride_status'] . ' to ' . $status], 409);
            return;
        }
        if ($status === 'started') {
            $otp = trim((string) ($body['otp_code'] ?? ''));
            if ($otp === '' || !hash_equals((string) ($ride['otp_code'] ?? ''), $otp)) {
                Response::json(['message' => 'Valid ride OTP is required to start the trip'], 422);
                return;
            }
        }
        if ($status === 'cancelled' && (string) $ride['payment_method'] === 'wallet') {
            WalletSchema::ensure();
        }
        $timeField = match ($status) {
            'accepted' => ', accepted_at = CURRENT_TIMESTAMP',
            'arrived' => ', arrived_at = CURRENT_TIMESTAMP',
            'started' => ', started_at = CURRENT_TIMESTAMP',
            'completed' => ', completed_at = CURRENT_TIMESTAMP',
            'cancelled' => ', cancelled_at = CURRENT_TIMESTAMP, cancelled_by = \'driver\'',
            default => '',
        };
        $paymentSql = $status === 'completed' ? ', payment_status = case when payment_method = \'cash\' then \'paid\' else payment_status end' : '';
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('update taxi_rides set ride_status = :status' . $timeField . $paymentSql . ', updated_at = CURRENT_TIMESTAMP where id = :id and driver_id = :driver_id and ride_status = :previous_status');
            $stmt->execute(['status' => $status, 'id' => $id, 'driver_id' => $driverId, 'previous_status' => (string) $ride['ride_status']]);
            if ($stmt->rowCount() !== 1) {
                $db->rollBack();
                Response::json(['message' => 'Ride status changed. Refresh before trying again.'], 409);
                return;
            }
            if ($status === 'completed') {
                $ride['ride_status'] = 'completed';
                $this->settleTaxiDriver($ride);
                $db->prepare('update taxi_drivers set availability_status = \'online\', updated_at = CURRENT_TIMESTAMP where id = :id')->execute(['id' => $driverId]);
            }
            if ($status === 'cancelled') {
                $db->prepare('update taxi_drivers set availability_status = \'online\', updated_at = CURRENT_TIMESTAMP where id = :id')->execute(['id' => $driverId]);
                if ((int) ($ride['customer_id'] ?? 0) > 0 && (string) $ride['payment_method'] === 'wallet' && (string) $ride['payment_status'] === 'paid') {
                    WalletService::credit('customer', 'customer-' . (int) $ride['customer_id'], (float) $ride['final_fare'], 'taxi_driver_cancel_refund', 'taxi-driver-cancel-' . $id, 'Taxi ride cancelled by driver');
                    $db->prepare('update taxi_rides set payment_status = \'refunded\' where id = :id')->execute(['id' => $id]);
                }
            }
            TaxiSchema::recordStatus($id, $status, 'taxi_driver', $actorName, trim((string) ($body['note'] ?? '')));
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
        NotificationLog::record('customer', null, (string) ($ride['guest_id'] ?? ''), 'Ride status updated', 'Your ride ' . (string) $ride['ride_number'] . ' is now ' . str_replace('_', ' ', $status) . '.', $id, 'taxi');
        Response::json(['message' => 'Ride updated']);
    }

    private function settleTaxiDriver(array $ride): void
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
        Database::connection()->prepare('update taxi_rides set driver_earning = :earning, commission_amount = :commission, updated_at = CURRENT_TIMESTAMP where id = :id')
            ->execute(['id' => (int) $ride['id'], 'earning' => $earning, 'commission' => $commission]);
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

    private function publicWorker(array $worker, string $role): array
    {
        $kycReady = $role !== 'taxi_driver' || (
            !empty($worker['license_document'])
            && !empty($worker['vehicle_document'])
            && !empty($worker['insurance_document'])
            && (string) ($worker['license_expiry'] ?? '') >= date('Y-m-d')
            && (string) ($worker['insurance_expiry'] ?? '') >= date('Y-m-d')
        );
        return [
            'id' => (int) $worker['id'],
            'role' => $role,
            'name' => (string) $worker['name'],
            'phone' => (string) $worker['phone'],
            'email' => (string) ($worker['email'] ?? ''),
            'zone_id' => (int) ($worker['zone_id'] ?? 0),
            'availability_status' => (string) ($worker['availability_status'] ?? 'online'),
            'kyc_ready' => $kycReady,
        ];
    }
}
