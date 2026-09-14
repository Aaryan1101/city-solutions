<?php

declare(strict_types=1);

namespace App\Controllers\HotelOwner;

use App\Support\Database;
use App\Support\HotelSchema;
use App\Support\Response;
use App\Support\Security;
use App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('hotel_owner/login', ['title' => 'Hotel Owner Login']);
    }

    public function login(): void
    {
        HotelSchema::ensure();
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        Security::enforceLoginThrottle('hotel-owner', $phone);
        $stmt = Database::connection()->prepare('select * from hotel_owners where phone = :phone limit 1');
        $stmt->execute(['phone' => $phone]);
        $owner = $stmt->fetch();
        if (!$owner || empty($owner['password']) || !password_verify($password, $owner['password'])) {
            View::render('hotel_owner/login', ['title' => 'Hotel Owner Login', 'error' => 'Invalid phone or password.']);
            return;
        }
        if (empty($owner['status'])) {
            View::render('hotel_owner/login', ['title' => 'Hotel Owner Login', 'error' => 'Your hotel owner account is inactive.']);
            return;
        }
        session_regenerate_id(true);
        $_SESSION['hotel_owner_id'] = $owner['id'];
        $_SESSION['hotel_owner_name'] = $owner['name'];
        Response::redirect('/hotel-owner');
    }

    public function logout(): void
    {
        unset($_SESSION['hotel_owner_id'], $_SESSION['hotel_owner_name']);
        Response::redirect('/hotel-owner/login');
    }
}
