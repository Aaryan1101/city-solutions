<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use App\Support\ZoneSchema;

final class ZoneController
{
    public function index(): void
    {
        Auth::requireAdmin();
        ZoneSchema::ensure();
        $zones = Database::connection()->query('select * from zones order by sort_order asc, id desc')->fetchAll();
        View::render('admin/zones', [
            'title' => 'Zones',
            'zones' => $zones,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        ZoneSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into zones (name, city, state, pincode, pincodes, latitude, longitude, radius_km, status, sort_order, created_at, updated_at)
             values (:name, :city, :state, :pincode, :pincodes, :latitude, :longitude, :radius_km, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->params());
        Response::redirect('/admin/zones');
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        ZoneSchema::ensure();
        $params = $this->params();
        $params['id'] = $id;
        Database::connection()->prepare(
            'update zones
             set name = :name, city = :city, state = :state, pincode = :pincode, pincodes = :pincodes, latitude = :latitude, longitude = :longitude, radius_km = :radius_km, status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP
             where id = :id'
        )->execute($params);
        Response::redirect('/admin/zones');
    }

    public function delete(int $id): void
    {
        Auth::requireAdmin();
        ZoneSchema::ensure();
        Database::connection()->prepare('delete from zones where id = :id')->execute(['id' => $id]);
        Response::redirect('/admin/zones');
    }

    private function params(): array
    {
        return [
            'name' => trim((string) Request::input('name')),
            'city' => trim((string) Request::input('city')) ?: null,
            'state' => trim((string) Request::input('state')) ?: null,
            'pincode' => trim((string) Request::input('pincode')) ?: null,
            'pincodes' => trim((string) Request::input('pincodes')) ?: null,
            'latitude' => trim((string) Request::input('latitude')) === '' ? null : (float) Request::input('latitude'),
            'longitude' => trim((string) Request::input('longitude')) === '' ? null : (float) Request::input('longitude'),
            'radius_km' => max(0, (float) Request::input('radius_km', 0)),
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int) Request::input('sort_order', 0),
        ];
    }
}
