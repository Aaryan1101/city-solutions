<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;
use App\Support\ZoneSchema;

final class AdminUserController
{
    private const ROLES = ['super_admin', 'zone_manager', 'medical_manager', 'hotel_manager', 'support', 'viewer'];

    public function index(): void
    {
        $this->requireSuperAdmin();
        $this->ensureSchema();
        ZoneSchema::ensure();
        $admins = Database::connection()->query(
            'select admins.id, admins.name, admins.email, admins.role, admins.zone_id, zones.name as zone_name, admins.created_at, admins.updated_at
             from admins
             left join zones on zones.id = admins.zone_id
             order by admins.id desc'
        )->fetchAll();
        View::render('admin/admin_users', [
            'title' => 'Admin Users',
            'admins' => $admins,
            'roles' => self::ROLES,
            'zones' => ZoneSchema::active(),
        ]);
    }

    public function store(): void
    {
        $this->requireSuperAdmin();
        $this->ensureSchema();
        ZoneSchema::ensure();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = $this->role((string) ($_POST['role'] ?? 'viewer'));
        if ($name === '' || $email === '' || strlen($password) < 8) {
            Response::redirect('/admin/admin-users');
        }

        $stmt = Database::connection()->prepare(
            'insert into admins (name, email, password, role, zone_id, created_at, updated_at)
             values (:name, :email, :password, :role, :zone_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'zone_id' => $this->zoneId(),
        ]);
        Response::redirect('/admin/admin-users');
    }

    public function update(int $id): void
    {
        $this->requireSuperAdmin();
        $this->ensureSchema();
        if ($id === (int) ($_SESSION['admin_id'] ?? 0) && ($_POST['role'] ?? 'super_admin') !== 'super_admin') {
            Response::redirect('/admin/admin-users');
        }

        $password = (string) ($_POST['password'] ?? '');
        $passwordSql = $password === '' ? '' : ', password = :password';
        $params = [
            'id' => $id,
            'name' => trim((string) ($_POST['name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'role' => $this->role((string) ($_POST['role'] ?? 'viewer')),
            'zone_id' => $this->zoneId(),
        ];
        if ($password !== '') {
            if (strlen($password) < 8) {
                Response::redirect('/admin/admin-users');
            }
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        Database::connection()->prepare(
            'update admins set name = :name, email = :email, role = :role, zone_id = :zone_id' . $passwordSql . ', updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute($params);

        if ($id === (int) ($_SESSION['admin_id'] ?? 0)) {
            $_SESSION['admin_name'] = $params['name'];
            $_SESSION['admin_role'] = $params['role'];
            $_SESSION['admin_zone_id'] = (int) ($params['zone_id'] ?? 0);
        }
        Response::redirect('/admin/admin-users');
    }

    public function delete(int $id): void
    {
        $this->requireSuperAdmin();
        $this->ensureSchema();
        if ($id === (int) ($_SESSION['admin_id'] ?? 0)) {
            Response::redirect('/admin/admin-users');
        }
        Database::connection()->prepare('delete from admins where id = :id')->execute(['id' => $id]);
        Response::redirect('/admin/admin-users');
    }

    private function requireSuperAdmin(): void
    {
        Auth::requireAdmin();
        if (Auth::role() !== 'super_admin') {
            Response::json(['message' => 'Permission denied'], 403);
            exit;
        }
    }

    private function role(string $role): string
    {
        return in_array($role, self::ROLES, true) ? $role : 'viewer';
    }

    private function zoneId(): ?int
    {
        $id = (int) ($_POST['zone_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function ensureSchema(): void
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
