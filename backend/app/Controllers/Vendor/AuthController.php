<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\Response;
use App\Support\Security;
use App\Support\VendorSchema;
use App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('vendor/login', ['title' => 'Vendor Login']);
    }

    public function login(): void
    {
        VendorSchema::ensure();
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $moduleKey = $this->moduleKey($_POST['module_key'] ?? 'mart');
        Security::enforceLoginThrottle('vendor-' . $moduleKey, $phone);
        $stmt = Database::connection()->prepare('select * from vendors where module_key = :module_key and phone = :phone limit 1');
        $stmt->execute(['module_key' => $moduleKey, 'phone' => $phone]);
        $vendor = $stmt->fetch();

        if (!$vendor || !password_verify($password, $vendor['password'])) {
            View::render('vendor/login', ['title' => 'Vendor Login', 'error' => 'Invalid phone or password.']);
            return;
        }
        if ($vendor['status'] !== 'approved') {
            View::render('vendor/login', ['title' => 'Vendor Login', 'error' => 'Your vendor account is ' . $vendor['status'] . '.']);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['vendor_id'] = $vendor['id'];
        $_SESSION['vendor_name'] = $vendor['shop_name'];
        $_SESSION['vendor_module_key'] = $vendor['module_key'] ?? $moduleKey;
        Response::redirect('/vendor');
    }

    public function logout(): void
    {
        unset($_SESSION['vendor_id'], $_SESSION['vendor_name'], $_SESSION['vendor_module_key']);
        Response::redirect('/vendor/login');
    }

    private function moduleKey(string $value): string
    {
        return in_array($value, ['mart', 'ecommerce', 'medical'], true) ? $value : 'mart';
    }
}
