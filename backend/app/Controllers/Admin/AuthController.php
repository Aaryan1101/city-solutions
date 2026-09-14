<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Database;
use App\Support\Env;
use App\Support\Auth;
use App\Support\Response;
use App\Support\Security;
use App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('admin/login', ['title' => 'Admin Login']);
    }

    public function login(): void
    {
        $this->ensureAdminColumns();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        Security::enforceLoginThrottle('admin', $email);

        $stmt = Database::connection()->prepare('select * from admins where email = :email limit 1');
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        $envEmail = Env::get('ADMIN_EMAIL');
        $envPassword = Env::get('ADMIN_PASSWORD');
        $envMatch = $email === $envEmail && $password === $envPassword;

        if (($admin && password_verify($password, $admin['password'])) || $envMatch) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'] ?? 0;
            $_SESSION['admin_name'] = $admin['name'] ?? 'Admin';
            $_SESSION['admin_role'] = $admin['role'] ?? 'super_admin';
            $_SESSION['admin_zone_id'] = (int) ($admin['zone_id'] ?? 0);
            Auth::csrfToken();
            Response::redirect('/admin');
        }

        View::render('admin/login', ['title' => 'Admin Login', 'error' => 'Invalid email or password.']);
    }

    public function logout(): void
    {
        session_destroy();
        Response::redirect('/admin/login');
    }

    private function ensureAdminColumns(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $stmt = $db->prepare(
                'select count(*) from information_schema.columns
                 where table_schema = database() and table_name = \'admins\' and column_name = \'role\''
            );
            $stmt->execute();
            if ((int) $stmt->fetchColumn() === 0) {
                $db->exec('alter table admins add column role varchar(60) not null default \'super_admin\'');
            }
            $stmt = $db->prepare(
                'select count(*) from information_schema.columns
                 where table_schema = database() and table_name = \'admins\' and column_name = \'zone_id\''
            );
            $stmt->execute();
            if ((int) $stmt->fetchColumn() === 0) {
                $db->exec('alter table admins add column zone_id bigint unsigned null after role');
            }
            return;
        }
        $columns = $db->query('pragma table_info(admins)')->fetchAll();
        $hasRole = false;
        $hasZone = false;
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'role') {
                $hasRole = true;
            }
            if (($column['name'] ?? '') === 'zone_id') {
                $hasZone = true;
            }
        }
        if (!$hasRole) {
            $db->exec('alter table admins add column role text not null default \'super_admin\'');
        }
        if (!$hasZone) {
            $db->exec('alter table admins add column zone_id integer null');
        }
    }
}
