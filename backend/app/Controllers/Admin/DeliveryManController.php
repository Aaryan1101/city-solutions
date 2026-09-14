<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\DeliverySchema;
use App\Support\Response;
use App\Support\View;
use App\Support\ZoneSchema;

final class DeliveryManController
{
    public function index(): void
    {
        Auth::requireAdmin();
        DeliverySchema::ensure();
        ZoneSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select delivery_men.*, zones.name as zone_name
             from delivery_men
             left join zones on zones.id = delivery_men.zone_id
             where 1 = 1' . Auth::zoneWhere('delivery_men') . '
             order by delivery_men.id desc'
        );
        $stmt->execute(Auth::zoneParams());
        $zones = Auth::isZoneScoped()
            ? array_values(array_filter(ZoneSchema::active(), fn (array $zone): bool => (int) $zone['id'] === Auth::zoneId()))
            : ZoneSchema::active();
        View::render('admin/delivery_men', [
            'title' => 'Delivery Men',
            'deliveryMen' => $stmt->fetchAll(),
            'zones' => $zones,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        DeliverySchema::ensure();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '' || $phone === '') {
            Response::redirect('/admin/delivery-men');
        }
        $password = trim($_POST['password'] ?? '');

        $stmt = Database::connection()->prepare(
            'insert into delivery_men (zone_id, name, phone, email, password, vehicle_type, vehicle_number, status, created_at, updated_at)
             values (:zone_id, :name, :phone, :email, :password, :vehicle_type, :vehicle_number, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'zone_id' => $this->zoneId(),
            'name' => $name,
            'phone' => $phone,
            'email' => trim($_POST['email'] ?? '') ?: null,
            'password' => $password === '' ? null : password_hash($password, PASSWORD_DEFAULT),
            'vehicle_type' => trim($_POST['vehicle_type'] ?? '') ?: null,
            'vehicle_number' => trim($_POST['vehicle_number'] ?? '') ?: null,
            'status' => isset($_POST['status']) ? 1 : 0,
        ]);

        Response::redirect('/admin/delivery-men');
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        DeliverySchema::ensure();
        $existing = Database::connection()->prepare('select * from delivery_men where id = :id' . Auth::zoneWhere('delivery_men') . ' limit 1');
        $existing->execute(Auth::zoneParams(['id' => $id]));
        $person = $existing->fetch();
        if (!$person) {
            Response::redirect('/admin/delivery-men');
        }
        $password = trim($_POST['password'] ?? '');
        $passwordSql = $password === '' ? '' : ', password = :password';
        $stmt = Database::connection()->prepare(
            'update delivery_men
             set zone_id = :zone_id, name = :name, phone = :phone, email = :email, vehicle_type = :vehicle_type, vehicle_number = :vehicle_number, status = :status' . $passwordSql . ', updated_at = CURRENT_TIMESTAMP
             where id = :id'
        );
        $params = [
            'id' => $id,
            'zone_id' => $this->zoneId((int) ($person['zone_id'] ?? 0)),
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? '') ?: null,
            'vehicle_type' => trim($_POST['vehicle_type'] ?? '') ?: null,
            'vehicle_number' => trim($_POST['vehicle_number'] ?? '') ?: null,
            'status' => isset($_POST['status']) ? 1 : 0,
        ];
        if ($password !== '') {
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $stmt->execute($params);

        Response::redirect('/admin/delivery-men');
    }

    private function zoneId(int $fallback = 0): ?int
    {
        if (Auth::isZoneScoped()) {
            return Auth::zoneId();
        }
        $id = (int) ($_POST['zone_id'] ?? $fallback);
        return $id > 0 ? $id : null;
    }
}
