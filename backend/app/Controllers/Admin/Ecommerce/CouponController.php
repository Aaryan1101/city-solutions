<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\CouponSchema;
use App\Support\Database;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\View;

final class CouponController
{
    public function __construct(
        private readonly string $moduleKey = 'ecommerce',
        private readonly string $basePath = '/admin/ecommerce/coupons',
        private readonly string $moduleLabel = 'Coupons'
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        CouponSchema::ensure();
        $stmt = Database::connection()->prepare('select * from coupons where module_key = :module_key order by id desc');
        $stmt->execute(['module_key' => $this->moduleKey]);
        View::render('admin/coupons', [
            'title' => $this->moduleLabel,
            'coupons' => $stmt->fetchAll(),
            'basePath' => $this->basePath,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        CouponSchema::ensure();
        $code = $this->code($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        if ($code === '' || $title === '') {
            Response::redirect($this->basePath);
        }

        $stmt = Database::connection()->prepare(
            'insert into coupons (module_key, code, title, discount_type, discount_value, minimum_order_amount, maximum_discount, usage_limit, starts_at, expires_at, status, created_at, updated_at)
             values (:module_key, :code, :title, :discount_type, :discount_value, :minimum_order_amount, :maximum_discount, :usage_limit, :starts_at, :expires_at, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->payload($code, $title));
        Response::redirect($this->basePath);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        CouponSchema::ensure();
        $code = $this->code($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        if ($code === '' || $title === '') {
            Response::redirect($this->basePath);
        }

        $payload = $this->payload($code, $title);
        $payload['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update coupons set code = :code, title = :title, discount_type = :discount_type, discount_value = :discount_value, minimum_order_amount = :minimum_order_amount, maximum_discount = :maximum_discount, usage_limit = :usage_limit, starts_at = :starts_at, expires_at = :expires_at, status = :status, updated_at = CURRENT_TIMESTAMP where id = :id and module_key = :module_key'
        );
        $stmt->execute($payload);
        Response::redirect($this->basePath);
    }

    public function delete(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        CouponSchema::ensure();
        $stmt = Database::connection()->prepare('delete from coupons where id = :id and module_key = :module_key');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        Response::redirect($this->basePath);
    }

    private function payload(string $code, string $title): array
    {
        $discountType = ($_POST['discount_type'] ?? 'flat') === 'percent' ? 'percent' : 'flat';
        return [
            'module_key' => $this->moduleKey,
            'code' => $code,
            'title' => $title,
            'discount_type' => $discountType,
            'discount_value' => max(0, (float) ($_POST['discount_value'] ?? 0)),
            'minimum_order_amount' => max(0, (float) ($_POST['minimum_order_amount'] ?? 0)),
            'maximum_discount' => ($_POST['maximum_discount'] ?? '') === '' ? null : max(0, (float) $_POST['maximum_discount']),
            'usage_limit' => ($_POST['usage_limit'] ?? '') === '' ? null : max(1, (int) $_POST['usage_limit']),
            'starts_at' => ($_POST['starts_at'] ?? '') === '' ? null : $_POST['starts_at'],
            'expires_at' => ($_POST['expires_at'] ?? '') === '' ? null : $_POST['expires_at'],
            'status' => isset($_POST['status']) ? 1 : 0,
        ];
    }

    private function code(string $value): string
    {
        return strtoupper(trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $value)));
    }
}
