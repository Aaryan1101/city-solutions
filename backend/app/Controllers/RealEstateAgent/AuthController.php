<?php

declare(strict_types=1);

namespace App\Controllers\RealEstateAgent;

use App\Support\Database;
use App\Support\RealEstateSchema;
use App\Support\Response;
use App\Support\Security;
use App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('real_estate_agent/login', ['title' => 'Real Estate Agent Login']);
    }

    public function login(): void
    {
        RealEstateSchema::ensure();
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        Security::enforceLoginThrottle('real-estate-agent', $phone);
        $stmt = Database::connection()->prepare('select * from re_agents where phone = :phone limit 1');
        $stmt->execute(['phone' => $phone]);
        $agent = $stmt->fetch();
        if (!$agent || empty($agent['password']) || !password_verify($password, $agent['password'])) {
            View::render('real_estate_agent/login', ['title' => 'Real Estate Agent Login', 'error' => 'Invalid phone or password.']);
            return;
        }
        if (($agent['status'] ?? '') !== 'approved') {
            View::render('real_estate_agent/login', ['title' => 'Real Estate Agent Login', 'error' => 'Your account is not approved yet.']);
            return;
        }
        session_regenerate_id(true);
        $_SESSION['real_estate_agent_id'] = $agent['id'];
        $_SESSION['real_estate_agent_name'] = $agent['business_name'];
        Response::redirect('/real-estate-agent');
    }

    public function logout(): void
    {
        unset($_SESSION['real_estate_agent_id'], $_SESSION['real_estate_agent_name']);
        Response::redirect('/real-estate-agent/login');
    }
}
