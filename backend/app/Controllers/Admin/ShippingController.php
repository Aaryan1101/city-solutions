<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Response;
use App\Support\ShippingSchema;
use App\Support\View;

final class ShippingController
{
    public function index(): void
    {
        Auth::requireAdmin();
        ShippingSchema::ensure();
        $methods = Database::connection()->query(
            'select * from shipping_methods order by sort_order asc, id desc'
        )->fetchAll();

        View::render('admin/shipping', [
            'title' => 'Shipping',
            'methods' => $methods,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        ShippingSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into shipping_methods (name, description, cost, expected_days, sort_order, status, created_at, updated_at)
             values (:name, :description, :cost, :expected_days, :sort_order, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->payload());
        Response::redirect('/admin/shipping');
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        ShippingSchema::ensure();
        $payload = $this->payload();
        $payload['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update shipping_methods set name = :name, description = :description, cost = :cost, expected_days = :expected_days, sort_order = :sort_order, status = :status, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $stmt->execute($payload);
        Response::redirect('/admin/shipping');
    }

    private function payload(): array
    {
        return [
            'name' => trim($_POST['name'] ?? 'Standard Delivery'),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'cost' => max(0, (float) ($_POST['cost'] ?? 0)),
            'expected_days' => trim($_POST['expected_days'] ?? '') ?: null,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'status' => isset($_POST['status']) ? 1 : 0,
        ];
    }
}
