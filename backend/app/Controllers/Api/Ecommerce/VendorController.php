<?php

declare(strict_types=1);

namespace App\Controllers\Api\Ecommerce;

use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\VendorSchema;

final class VendorController
{
    public function __construct(private readonly string $moduleKey = 'ecommerce')
    {
    }

    public function register(): void
    {
        VendorSchema::ensure();
        $body = Request::json();
        $shopName = trim($body['shop_name'] ?? '');
        $ownerName = trim($body['owner_name'] ?? '');
        $phone = trim($body['phone'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = (string) ($body['password'] ?? '');
        if ($shopName === '' || $ownerName === '' || $phone === '' || strlen($password) < 6) {
            Response::json(['message' => 'Shop name, owner name, phone, and 6 digit password are required'], 422);
            return;
        }

        $db = Database::connection();
        $exists = $db->prepare('select id from vendors where module_key = :module_key and phone = :phone limit 1');
        $exists->execute(['module_key' => $this->moduleKey, 'phone' => $phone]);
        if ($exists->fetch()) {
            Response::json(['message' => 'Vendor account already exists'], 409);
            return;
        }

        $stmt = $db->prepare(
            'insert into vendors (module_key, shop_name, owner_name, phone, email, password, address, city, status, created_at, updated_at)
             values (:module_key, :shop_name, :owner_name, :phone, :email, :password, :address, :city, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'module_key' => $this->moduleKey,
            'shop_name' => $shopName,
            'owner_name' => $ownerName,
            'phone' => $phone,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'address' => trim($body['address'] ?? ''),
            'city' => trim($body['city'] ?? ''),
        ]);

        Response::json([
            'message' => 'Vendor registration submitted for admin approval',
            'vendor_id' => (int) $db->lastInsertId(),
            'status' => 'pending',
        ]);
    }
}
