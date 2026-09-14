<?php

declare(strict_types=1);

namespace App\Controllers\ServiceProvider;

use App\Support\Database;
use App\Support\Response;
use App\Support\Security;
use App\Support\ServiceSchema;
use App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('service_provider/login', ['title' => 'Service Provider Login']);
    }

    public function login(): void
    {
        ServiceSchema::ensure();
        $phone = trim($_POST['phone'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        Security::enforceLoginThrottle('service-provider', $phone);
        $stmt = Database::connection()->prepare('select * from service_providers where phone = :phone limit 1');
        $stmt->execute(['phone' => $phone]);
        $provider = $stmt->fetch();

        if (!$provider || empty($provider['password']) || !password_verify($password, $provider['password'])) {
            View::render('service_provider/login', ['title' => 'Service Provider Login', 'error' => 'Invalid phone or password.']);
            return;
        }
        if (empty($provider['status'])) {
            View::render('service_provider/login', ['title' => 'Service Provider Login', 'error' => 'Your provider account is inactive.']);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['service_provider_id'] = $provider['id'];
        $_SESSION['service_provider_name'] = $provider['name'];
        Response::redirect('/service-provider');
    }

    public function logout(): void
    {
        unset($_SESSION['service_provider_id'], $_SESSION['service_provider_name']);
        Response::redirect('/service-provider/login');
    }
}
