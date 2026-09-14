<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\Request;
use App\Support\Response;
use App\Support\Settings;
use App\Support\TaxiSchema;
use App\Support\TaxiRouteService;
use App\Support\WalletSchema;
use App\Support\WalletService;
use App\Support\ZoneSchema;

final class TaxiController
{
    public function config(): void
    {
        TaxiSchema::ensure();
        $paymentMethods = [];
        if (Settings::moduleBool('taxi', 'cash_enabled', true)) {
            $paymentMethods[] = ['id' => 'cash', 'label' => 'Cash', 'requires_reference' => false];
        }
        if (Settings::moduleBool('taxi', 'wallet_enabled', true)) {
            $paymentMethods[] = ['id' => 'wallet', 'label' => 'Wallet', 'requires_reference' => false];
        }
        Response::json([
            'app_name' => Settings::moduleGet('taxi', 'app_name', 'City Cabs'),
            'currency_symbol' => Settings::moduleGet('taxi', 'currency_symbol', '₹'),
            'maintenance_mode' => Settings::moduleBool('taxi', 'maintenance_mode'),
            'maintenance_message' => Settings::moduleGet('taxi', 'maintenance_message'),
            'latest_app_version' => Settings::moduleGet('taxi', 'latest_app_version'),
            'force_update_version' => Settings::moduleGet('taxi', 'force_update_version'),
            'zones' => ZoneSchema::active(),
            'vehicle_types' => $this->vehicleTypes(),
            'payment_methods' => $paymentMethods,
            'support_phone' => Settings::moduleGet('taxi', 'support_phone'),
            'route_provider_ready' => trim(Settings::moduleGet('taxi', 'google_routes_api_key')) !== '',
        ]);
    }

    public function estimate(): void
    {
        TaxiSchema::ensure();
        $body = Request::json();
        if (isset($body['pickup_latitude'], $body['pickup_longitude'], $body['drop_latitude'], $body['drop_longitude'])) {
            $this->quote();
            return;
        }
        Response::json(['message' => 'Pickup and destination coordinates are required for a fare quote'], 422);
    }

    public function quote(): void
    {
        TaxiSchema::ensure();
        $body = Request::json();
        $pickupLat = $this->requiredCoordinate($body, 'pickup_latitude', -90, 90);
        $pickupLng = $this->requiredCoordinate($body, 'pickup_longitude', -180, 180);
        $dropLat = $this->requiredCoordinate($body, 'drop_latitude', -90, 90);
        $dropLng = $this->requiredCoordinate($body, 'drop_longitude', -180, 180);
        $pickupAddress = trim((string) ($body['pickup_address'] ?? ''));
        $dropAddress = trim((string) ($body['drop_address'] ?? ''));
        if ($pickupAddress === '' || $dropAddress === '') {
            Response::json(['message' => 'Pickup and destination addresses are required'], 422);
            return;
        }
        if (abs($pickupLat - $dropLat) < 0.00001 && abs($pickupLng - $dropLng) < 0.00001) {
            Response::json(['message' => 'Pickup and destination must be different'], 422);
            return;
        }

        $zone = ZoneSchema::resolve($pickupLat, $pickupLng);
        if ($zone === null) {
            Response::json(['message' => 'Pickup is outside active taxi service zones'], 422);
            return;
        }
        $route = TaxiRouteService::route($pickupLat, $pickupLng, $dropLat, $dropLng);
        if (($route['source'] ?? '') !== 'google_routes' && !Settings::moduleBool('taxi', 'allow_fallback_quotes', false)) {
            Response::json(['message' => 'Live route pricing is temporarily unavailable. Please try again shortly.'], 503);
            return;
        }
        $options = [];
        foreach ($this->vehicleTypes() as $vehicle) {
            $option = $this->fare($vehicle, (float) $route['distance_km'], (int) $route['duration_minutes']);
            $availability = $this->driverAvailability((int) $zone['id'], (int) $vehicle['id'], $pickupLat, $pickupLng);
            $option['available_drivers'] = $availability['count'];
            $option['pickup_eta_minutes'] = $availability['eta'];
            $option['available'] = $availability['count'] > 0;
            $options[] = $option;
        }
        if ($options === []) {
            Response::json(['message' => 'No vehicle types are active'], 422);
            return;
        }

        $customer = $this->currentCustomer();
        $token = bin2hex(random_bytes(32));
        $quoteMinutes = max(2, min(15, (int) Settings::moduleGet('taxi', 'quote_valid_minutes', '5')));
        $expiresAt = date('Y-m-d H:i:s', time() + ($quoteMinutes * 60));
        $stmt = Database::connection()->prepare(
            'insert into taxi_quotes
             (quote_token, customer_id, zone_id, pickup_address, pickup_latitude, pickup_longitude,
              drop_address, drop_latitude, drop_longitude, distance_km, duration_minutes, route_polyline,
              route_source, options_json, expires_at, created_at)
             values
             (:quote_token, :customer_id, :zone_id, :pickup_address, :pickup_latitude, :pickup_longitude,
              :drop_address, :drop_latitude, :drop_longitude, :distance_km, :duration_minutes, :route_polyline,
              :route_source, :options_json, :expires_at, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'quote_token' => hash('sha256', $token),
            'customer_id' => $customer ? (int) $customer['id'] : null,
            'zone_id' => (int) $zone['id'],
            'pickup_address' => $pickupAddress,
            'pickup_latitude' => $pickupLat,
            'pickup_longitude' => $pickupLng,
            'drop_address' => $dropAddress,
            'drop_latitude' => $dropLat,
            'drop_longitude' => $dropLng,
            'distance_km' => $route['distance_km'],
            'duration_minutes' => $route['duration_minutes'],
            'route_polyline' => $route['encoded_polyline'],
            'route_source' => $route['source'],
            'options_json' => json_encode($options, JSON_UNESCAPED_SLASHES),
            'expires_at' => $expiresAt,
        ]);
        Response::json(['data' => [
            'quote_token' => $token,
            'expires_at' => $expiresAt,
            'zone' => $zone,
            'route' => $route,
            'vehicle_options' => $options,
        ]]);
    }

    public function rides(): void
    {
        TaxiSchema::ensure();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select taxi_rides.*, taxi_vehicle_types.name as vehicle_type_name, taxi_drivers.name as driver_name,
                    taxi_drivers.phone as driver_phone, taxi_drivers.vehicle_name, taxi_drivers.vehicle_number,
                    taxi_drivers.current_latitude as driver_latitude, taxi_drivers.current_longitude as driver_longitude
             from taxi_rides
             left join taxi_vehicle_types on taxi_vehicle_types.id = taxi_rides.vehicle_type_id
             left join taxi_drivers on taxi_drivers.id = taxi_rides.driver_id
             where taxi_rides.customer_id = :customer_id or taxi_rides.guest_id = :guest_id
             order by taxi_rides.id desc'
        );
        $stmt->execute(['customer_id' => (int) $customer['id'], 'guest_id' => 'customer-' . (int) $customer['id']]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function show(int $id): void
    {
        TaxiSchema::ensure();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select taxi_rides.*, taxi_vehicle_types.name as vehicle_type_name, taxi_drivers.name as driver_name,
                    taxi_drivers.phone as driver_phone, taxi_drivers.vehicle_name, taxi_drivers.vehicle_number,
                    taxi_drivers.current_latitude as driver_latitude, taxi_drivers.current_longitude as driver_longitude
             from taxi_rides
             left join taxi_vehicle_types on taxi_vehicle_types.id = taxi_rides.vehicle_type_id
             left join taxi_drivers on taxi_drivers.id = taxi_rides.driver_id
             where taxi_rides.id = :id and (taxi_rides.customer_id = :customer_id or taxi_rides.guest_id = :guest_id)
             limit 1'
        );
        $stmt->execute(['id' => $id, 'customer_id' => (int) $customer['id'], 'guest_id' => 'customer-' . (int) $customer['id']]);
        $ride = $stmt->fetch();
        if (!$ride) {
            Response::json(['message' => 'Ride not found'], 404);
            return;
        }
        $history = Database::connection()->prepare('select * from taxi_ride_status_history where ride_id = :ride_id order by id asc');
        $history->execute(['ride_id' => $id]);
        Response::json(['data' => $ride, 'history' => $history->fetchAll()]);
    }

    public function book(): void
    {
        TaxiSchema::ensure();
        NotificationSchema::ensure();
        ZoneSchema::ensure();
        WalletSchema::ensure();
        $body = Request::json();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login is required to request a taxi'], 401);
            return;
        }
        $quoteToken = trim((string) ($body['quote_token'] ?? ''));
        $vehicleTypeId = (int) ($body['vehicle_type_id'] ?? 0);
        if ($quoteToken === '' || $vehicleTypeId <= 0) {
            Response::json(['message' => 'A fresh route quote and vehicle selection are required'], 422);
            return;
        }
        $enabledPayments = [];
        if (Settings::moduleBool('taxi', 'cash_enabled', true)) {
            $enabledPayments[] = 'cash';
        }
        if (Settings::moduleBool('taxi', 'wallet_enabled', true)) {
            $enabledPayments[] = 'wallet';
        }
        $paymentMethod = trim((string) ($body['payment_method'] ?? ''));
        if (!in_array($paymentMethod, $enabledPayments, true)) {
            Response::json(['message' => 'Select an available payment method'], 422);
            return;
        }
        $rideNumber = 'CTX' . date('ymdHis') . random_int(100, 999);
        $otp = (string) random_int(1000, 9999);
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $quoteStmt = $db->prepare('select * from taxi_quotes where quote_token = :quote_token and consumed_at is null and expires_at > CURRENT_TIMESTAMP limit 1');
            $quoteStmt->execute(['quote_token' => hash('sha256', $quoteToken)]);
            $quote = $quoteStmt->fetch();
            if (!$quote || (!empty($quote['customer_id']) && (int) $quote['customer_id'] !== (int) $customer['id'])) {
                $db->rollBack();
                Response::json(['message' => 'This fare quote expired. Please refresh ride options.'], 422);
                return;
            }
            $options = json_decode((string) $quote['options_json'], true);
            $fare = null;
            foreach (is_array($options) ? $options : [] as $option) {
                if ((int) ($option['vehicle_type_id'] ?? 0) === $vehicleTypeId) {
                    $fare = $option;
                    break;
                }
            }
            if ($fare === null) {
                $db->rollBack();
                Response::json(['message' => 'Selected vehicle is not part of this quote'], 422);
                return;
            }
            if (($fare['available'] ?? false) !== true) {
                $db->rollBack();
                Response::json(['message' => 'No nearby driver is available for this vehicle type'], 409);
                return;
            }
            $claim = $db->prepare('update taxi_quotes set consumed_at = CURRENT_TIMESTAMP where id = :id and consumed_at is null');
            $claim->execute(['id' => (int) $quote['id']]);
            if ($claim->rowCount() !== 1) {
                $db->rollBack();
                Response::json(['message' => 'This quote has already been used'], 409);
                return;
            }
            $amount = (float) $fare['estimated_fare'];
            if ($paymentMethod === 'wallet' && !WalletService::canDebit('customer', 'customer-' . (int) $customer['id'], $amount)) {
                $db->rollBack();
                Response::json(['message' => 'Insufficient wallet balance'], 422);
                return;
            }
        $stmt = $db->prepare(
            'insert into taxi_rides
             (ride_number, guest_id, customer_id, customer_name, customer_phone, customer_email, zone_id, driver_id, vehicle_type_id,
              pickup_address, pickup_latitude, pickup_longitude, drop_address, drop_latitude, drop_longitude,
              distance_km, duration_minutes, estimated_fare, final_fare, payment_method, payment_status, ride_status,
              otp_code, customer_note, quote_token, route_polyline, route_source, created_at, updated_at)
             values
             (:ride_number, :guest_id, :customer_id, :customer_name, :customer_phone, :customer_email, :zone_id, :driver_id, :vehicle_type_id,
              :pickup_address, :pickup_latitude, :pickup_longitude, :drop_address, :drop_latitude, :drop_longitude,
              :distance_km, :duration_minutes, :estimated_fare, :final_fare, :payment_method, :payment_status, :ride_status,
              :otp_code, :customer_note, :quote_token, :route_polyline, :route_source, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'ride_number' => $rideNumber,
            'guest_id' => 'customer-' . (int) $customer['id'],
            'customer_id' => (int) $customer['id'],
            'customer_name' => (string) $customer['name'],
            'customer_phone' => (string) $customer['phone'],
            'customer_email' => trim((string) ($customer['email'] ?? '')) ?: null,
            'zone_id' => (int) $quote['zone_id'],
            'driver_id' => null,
            'vehicle_type_id' => $vehicleTypeId,
            'pickup_address' => (string) $quote['pickup_address'],
            'pickup_latitude' => (float) $quote['pickup_latitude'],
            'pickup_longitude' => (float) $quote['pickup_longitude'],
            'drop_address' => (string) $quote['drop_address'],
            'drop_latitude' => (float) $quote['drop_latitude'],
            'drop_longitude' => (float) $quote['drop_longitude'],
            'distance_km' => (float) $quote['distance_km'],
            'duration_minutes' => (int) $quote['duration_minutes'],
            'estimated_fare' => $fare['estimated_fare'],
            'final_fare' => $fare['estimated_fare'],
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentMethod === 'wallet' ? 'paid' : 'unpaid',
            'ride_status' => 'requested',
            'otp_code' => $otp,
            'customer_note' => trim((string) ($body['customer_note'] ?? '')) ?: null,
            'quote_token' => hash('sha256', $quoteToken),
            'route_polyline' => $quote['route_polyline'],
            'route_source' => $quote['route_source'],
        ]);
        $rideId = (int) $db->lastInsertId();
        if ($paymentMethod === 'wallet') {
            WalletService::debit('customer', 'customer-' . (int) $customer['id'], $amount, 'taxi_ride', 'taxi-ride-' . $rideId, 'Taxi ride ' . $rideNumber);
        }
        TaxiSchema::recordStatus($rideId, 'requested', 'customer', (string) $customer['name'], 'Searching for a driver');
        $db->commit();
        NotificationLog::record('admin', null, null, 'New taxi ride', $rideNumber . ' requested.', $rideId, 'taxi');
        Response::json([
            'message' => 'Searching for a nearby driver',
            'ride_id' => $rideId,
            'ride_number' => $rideNumber,
            'otp_code' => $otp,
            'driver' => null,
        ]);
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }

    public function cancel(int $id): void
    {
        TaxiSchema::ensure();
        $body = Request::json();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }
        $db = Database::connection();
        $stmt = $db->prepare('select * from taxi_rides where id = :id and customer_id = :customer_id limit 1');
        $stmt->execute(['id' => $id, 'customer_id' => (int) $customer['id']]);
        $ride = $stmt->fetch();
        if (!$ride) {
            Response::json(['message' => 'Ride not found'], 404);
            return;
        }
        if (!in_array((string) $ride['ride_status'], ['requested', 'accepted', 'arrived'], true)) {
            Response::json(['message' => 'Ride cannot be cancelled now'], 422);
            return;
        }
        $reason = trim((string) ($body['reason'] ?? ''));
        $fee = in_array((string) $ride['ride_status'], ['accepted', 'arrived'], true)
            ? $this->cancellationFee((int) ($ride['vehicle_type_id'] ?? 0))
            : 0.0;
        if ((string) $ride['payment_method'] === 'wallet' && (string) $ride['payment_status'] === 'paid') {
            WalletSchema::ensure();
        }
        $db->beginTransaction();
        try {
            $update = $db->prepare(
                'update taxi_rides set ride_status = \'cancelled\', cancellation_reason = :reason, cancelled_by = \'customer\', cancellation_fee = :fee, final_fare = :fee, cancelled_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP where id = :id and ride_status = :previous_status'
            );
            $update->execute(['id' => $id, 'reason' => $reason === '' ? null : $reason, 'fee' => $fee, 'previous_status' => (string) $ride['ride_status']]);
            if ($update->rowCount() !== 1) {
                $db->rollBack();
                Response::json(['message' => 'Ride status changed. Refresh before cancelling.'], 409);
                return;
            }
            if ((string) $ride['payment_method'] === 'wallet' && (string) $ride['payment_status'] === 'paid') {
                $refund = max(0, (float) $ride['final_fare'] - $fee);
                WalletService::credit('customer', 'customer-' . (int) $customer['id'], $refund, 'taxi_cancellation_refund', 'taxi-cancel-' . $id, 'Taxi cancellation refund');
                $db->prepare('update taxi_rides set payment_status = :status where id = :id')->execute([
                    'id' => $id,
                    'status' => $fee > 0 ? 'partially_refunded' : 'refunded',
                ]);
            }
            if (!empty($ride['driver_id'])) {
                $db->prepare('update taxi_drivers set availability_status = \'online\', updated_at = CURRENT_TIMESTAMP where id = :id')->execute(['id' => (int) $ride['driver_id']]);
            }
            TaxiSchema::recordStatus($id, 'cancelled', 'customer', (string) $ride['customer_name'], $reason);
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
        Response::json(['message' => $fee > 0 ? 'Ride cancelled. Cancellation fee applies.' : 'Ride cancelled', 'cancellation_fee' => $fee]);
    }

    public function rate(int $id): void
    {
        TaxiSchema::ensure();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }
        $body = Request::json();
        $rating = (int) ($body['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            Response::json(['message' => 'Rating must be between 1 and 5'], 422);
            return;
        }
        $stmt = Database::connection()->prepare(
            'update taxi_rides set customer_rating = :rating, customer_review = :review, updated_at = CURRENT_TIMESTAMP
             where id = :id and customer_id = :customer_id and ride_status = \'completed\' and customer_rating is null'
        );
        $stmt->execute([
            'rating' => $rating,
            'review' => trim((string) ($body['review'] ?? '')) ?: null,
            'id' => $id,
            'customer_id' => (int) $customer['id'],
        ]);
        if ($stmt->rowCount() === 0) {
            Response::json(['message' => 'Completed ride not found'], 404);
            return;
        }
        $driver = Database::connection()->prepare('select driver_id from taxi_rides where id = :id');
        $driver->execute(['id' => $id]);
        $driverId = (int) $driver->fetchColumn();
        if ($driverId > 0) {
            Database::connection()->prepare(
                'update taxi_drivers set rating = (select coalesce(avg(customer_rating), 0) from taxi_rides where driver_id = :driver_id and customer_rating is not null), updated_at = CURRENT_TIMESTAMP where id = :driver_id'
            )->execute(['driver_id' => $driverId]);
        }
        Response::json(['message' => 'Thanks for rating your ride']);
    }

    private function vehicleTypes(): array
    {
        return Database::connection()
            ->query('select * from taxi_vehicle_types where status = 1 order by sort_order asc, id asc')
            ->fetchAll();
    }

    private function vehicleType(int $id): ?array
    {
        $stmt = Database::connection()->prepare('select * from taxi_vehicle_types where id = :id and status = 1 limit 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function fare(array $vehicle, float $distanceKm, int $durationMinutes): array
    {
        $raw = (float) $vehicle['base_fare']
            + ((float) $vehicle['per_km_fare'] * $distanceKm)
            + ((float) $vehicle['per_minute_fare'] * $durationMinutes)
            + (float) ($vehicle['service_fee'] ?? 0);
        $estimated = max((float) $vehicle['minimum_fare'], round($raw, 2));
        return [
            'vehicle_type_id' => (int) $vehicle['id'],
            'vehicle_type_name' => (string) $vehicle['name'],
            'distance_km' => round($distanceKm, 2),
            'duration_minutes' => $durationMinutes,
            'estimated_fare' => $estimated,
            'cancellation_fee' => (float) $vehicle['cancellation_fee'],
            'service_fee' => (float) ($vehicle['service_fee'] ?? 0),
            'waiting_fee_per_minute' => (float) ($vehicle['waiting_fee_per_minute'] ?? 0),
            'included_wait_minutes' => (int) ($vehicle['included_wait_minutes'] ?? 3),
        ];
    }

    private function currentCustomer(): ?array
    {
        return (new CustomerController())->walletCustomer();
    }

    private function requiredCoordinate(array $body, string $field, float $minimum, float $maximum): float
    {
        $raw = $body[$field] ?? null;
        if ($raw === null || !is_numeric($raw)) {
            Response::json(['message' => str_replace('_', ' ', ucfirst($field)) . ' is required'], 422);
            exit;
        }
        $value = (float) $raw;
        if ($value < $minimum || $value > $maximum || $value == 0.0) {
            Response::json(['message' => 'Invalid ' . str_replace('_', ' ', $field)], 422);
            exit;
        }
        return $value;
    }

    private function driverAvailability(int $zoneId, int $vehicleTypeId, float $pickupLat, float $pickupLng): array
    {
        $stmt = Database::connection()->prepare(
            'select current_latitude, current_longitude from taxi_drivers
             where status = \'approved\' and availability_status = \'online\'
               and license_document is not null and vehicle_document is not null and insurance_document is not null
               and license_expiry >= CURRENT_DATE and insurance_expiry >= CURRENT_DATE
               and (zone_id is null or zone_id = :zone_id)
               and (vehicle_type_id is null or vehicle_type_id = :vehicle_type_id)
               and last_seen_at is not null and last_seen_at >= :fresh_after
             order by last_seen_at desc limit 30'
        );
        $stmt->execute([
            'zone_id' => $zoneId,
            'vehicle_type_id' => $vehicleTypeId,
            'fresh_after' => date('Y-m-d H:i:s', time() - 600),
        ]);
        $drivers = $stmt->fetchAll();
        $nearest = null;
        $validCount = 0;
        foreach ($drivers as $driver) {
            $lat = (float) ($driver['current_latitude'] ?? 0);
            $lng = (float) ($driver['current_longitude'] ?? 0);
            if ($lat == 0.0 || $lng == 0.0) {
                continue;
            }
            $validCount++;
            $distance = $this->distanceKm($pickupLat, $pickupLng, $lat, $lng);
            $nearest = $nearest === null ? $distance : min($nearest, $distance);
        }
        return [
            'count' => $validCount,
            'eta' => $nearest === null ? null : max(2, (int) ceil(($nearest / 24) * 60)),
        ];
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        return (float) $value;
    }

    private function autoMatchDriver(int $zoneId, int $vehicleTypeId, ?float $pickupLat, ?float $pickupLng): ?array
    {
        $where = 'status = \'approved\' and availability_status = \'online\'';
        $params = [];
        if ($zoneId > 0) {
            $where .= ' and (zone_id is null or zone_id = :zone_id)';
            $params['zone_id'] = $zoneId;
        }
        if ($vehicleTypeId > 0) {
            $where .= ' and (vehicle_type_id is null or vehicle_type_id = :vehicle_type_id)';
            $params['vehicle_type_id'] = $vehicleTypeId;
        }
        $stmt = Database::connection()->prepare('select * from taxi_drivers where ' . $where . ' order by last_seen_at desc, id asc limit 20');
        $stmt->execute($params);
        $drivers = $stmt->fetchAll();
        if ($drivers === []) {
            return null;
        }
        if ($pickupLat === null || $pickupLng === null) {
            return $drivers[0];
        }
        usort($drivers, function (array $a, array $b) use ($pickupLat, $pickupLng): int {
            $ad = $this->distanceKm($pickupLat, $pickupLng, (float) ($a['current_latitude'] ?? 0), (float) ($a['current_longitude'] ?? 0));
            $bd = $this->distanceKm($pickupLat, $pickupLng, (float) ($b['current_latitude'] ?? 0), (float) ($b['current_longitude'] ?? 0));
            return $ad <=> $bd;
        });
        return $drivers[0];
    }

    private function cancellationFee(int $vehicleTypeId): float
    {
        $vehicle = $this->vehicleType($vehicleTypeId);
        return $vehicle ? (float) ($vehicle['cancellation_fee'] ?? 0) : 0.0;
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        if ($lat2 == 0.0 || $lng2 == 0.0) {
            return 999999.0;
        }
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
