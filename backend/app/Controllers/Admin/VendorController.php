<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Response;
use App\Support\VendorSchema;
use App\Support\View;
use App\Support\ZoneSchema;

final class VendorController
{
    public function index(): void
    {
        Auth::requireAdmin();
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select vendors.*, zones.name as zone_name
             from vendors
             left join zones on zones.id = vendors.zone_id
             where 1 = 1' . Auth::zoneWhere('vendors') . '
             order by vendors.id desc'
        );
        $stmt->execute(Auth::zoneParams());
        $vendors = $stmt->fetchAll();
        View::render('admin/vendors', [
            'title' => 'Vendors',
            'vendors' => $vendors,
            'zones' => $this->zones(),
        ]);
    }

    public function status(int $id): void
    {
        Auth::requireAdmin();
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $allowed = ['pending', 'approved', 'suspended', 'rejected'];
        $status = $_POST['status'] ?? 'pending';
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $stmt = Database::connection()->prepare('update vendors set zone_id = :zone_id, status = :status, admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP where id = :id' . Auth::zoneWhere('vendors'));
        $stmt->execute(Auth::zoneParams([
            'id' => $id,
            'zone_id' => Auth::isZoneScoped() ? Auth::zoneId() : ((int) ($_POST['zone_id'] ?? 0) ?: null),
            'status' => $status,
            'admin_note' => trim($_POST['admin_note'] ?? ''),
        ]));

        Response::redirect('/admin/vendors');
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
}
