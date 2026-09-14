<?php

declare(strict_types=1);

namespace App\Controllers\Api\Ecommerce;

use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\Security;

final class CustomerController
{
    public function register(): void
    {
        $this->ensureTables();
        $body = Request::json();
        $name = trim($body['name'] ?? '');
        $phone = trim($body['phone'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = (string) ($body['password'] ?? '');
        Security::enforceLoginThrottle('ecommerce-customer-register', $phone);
        if ($name === '' || $phone === '' || strlen($password) < 6) {
            Response::json(['message' => 'Name, phone, and 6 digit password are required'], 422);
            return;
        }

        $db = Database::connection();
        $exists = $db->prepare('select id from customers where phone = :phone limit 1');
        $exists->execute(['phone' => $phone]);
        $token = bin2hex(random_bytes(32));
        $existing = $exists->fetch();
        if ($existing) {
            $current = $db->prepare('select * from customers where id = :id limit 1');
            $current->execute(['id' => $existing['id']]);
            $customer = $current->fetch();
            if (!empty($customer['password'])) {
                Response::json(['message' => 'Account already exists. Please login.'], 409);
                return;
            }

            $upgrade = $db->prepare(
                'update customers set name = :name, email = :email, password = :password, auth_token = :auth_token, updated_at = CURRENT_TIMESTAMP where id = :id'
            );
            $upgrade->execute([
                'id' => $existing['id'],
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'auth_token' => hash('sha256', $token),
            ]);
            $this->authResponse((int) $existing['id'], $token, 'Account secured');
            return;
        }

        $stmt = $db->prepare(
            'insert into customers (name, phone, email, password, auth_token, status, created_at, updated_at)
             values (:name, :phone, :email, :password, :auth_token, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'auth_token' => hash('sha256', $token),
        ]);

        $this->authResponse((int) $db->lastInsertId(), $token, 'Account created');
    }

    public function login(): void
    {
        $this->ensureTables();
        $body = Request::json();
        $phone = trim($body['phone'] ?? '');
        $password = (string) ($body['password'] ?? '');
        Security::enforceLoginThrottle('ecommerce-customer-login', $phone);
        if ($phone === '' || $password === '') {
            Response::json(['message' => 'Phone and password are required'], 422);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('select * from customers where phone = :phone limit 1');
        $stmt->execute(['phone' => $phone]);
        $customer = $stmt->fetch();
        if (!$customer || empty($customer['password']) || !password_verify($password, (string) $customer['password'])) {
            Response::json(['message' => 'Invalid phone or password'], 401);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $update = $db->prepare('update customers set auth_token = :auth_token, updated_at = CURRENT_TIMESTAMP where id = :id');
        $update->execute([
            'id' => $customer['id'],
            'auth_token' => hash('sha256', $token),
        ]);

        $this->authResponse((int) $customer['id'], $token, 'Login successful');
    }

    public function profile(): void
    {
        $this->ensureTables();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Customer not found'], 404);
            return;
        }

        unset($customer['password'], $customer['auth_token']);
        Response::json(['data' => $customer, 'guest_id' => $this->guestId((int) $customer['id'])]);
    }

    public function addresses(): void
    {
        $this->ensureTables();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['data' => []]);
            return;
        }

        $stmt = Database::connection()->prepare('select * from customer_addresses where customer_id = :customer_id order by is_default desc, id desc');
        $stmt->execute(['customer_id' => $customer['id']]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function saveAddress(): void
    {
        $this->ensureTables();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }

        $body = Request::json();
        $label = trim($body['label'] ?? 'Home');
        $address = trim($body['address'] ?? '');
        if ($address === '') {
            Response::json(['message' => 'Address is required'], 422);
            return;
        }

        $db = Database::connection();
        $hasAddress = $db->prepare('select count(*) from customer_addresses where customer_id = :customer_id');
        $hasAddress->execute(['customer_id' => $customer['id']]);
        $isDefault = (int) $hasAddress->fetchColumn() === 0 || !empty($body['is_default']);
        if ($isDefault) {
            $reset = $db->prepare('update customer_addresses set is_default = 0 where customer_id = :customer_id');
            $reset->execute(['customer_id' => $customer['id']]);
        }

        $stmt = $db->prepare(
            'insert into customer_addresses (customer_id, label, contact_name, contact_phone, address, city, state, pincode, is_default, created_at, updated_at)
             values (:customer_id, :label, :contact_name, :contact_phone, :address, :city, :state, :pincode, :is_default, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'customer_id' => $customer['id'],
            'label' => $label,
            'contact_name' => trim($body['contact_name'] ?? $customer['name']),
            'contact_phone' => trim($body['contact_phone'] ?? $customer['phone']),
            'address' => $address,
            'city' => trim($body['city'] ?? ''),
            'state' => trim($body['state'] ?? ''),
            'pincode' => trim($body['pincode'] ?? ''),
            'is_default' => $isDefault ? 1 : 0,
        ]);

        $this->addresses();
    }

    public function deleteAddress(int $id): void
    {
        $this->ensureTables();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }

        $stmt = Database::connection()->prepare('delete from customer_addresses where id = :id and customer_id = :customer_id');
        $stmt->execute(['id' => $id, 'customer_id' => $customer['id']]);
        $this->addresses();
    }

    public function defaultAddress(int $id): void
    {
        $this->ensureTables();
        $customer = $this->currentCustomer();
        if (!$customer) {
            Response::json(['message' => 'Login required'], 401);
            return;
        }

        $db = Database::connection();
        $db->prepare('update customer_addresses set is_default = 0 where customer_id = :customer_id')->execute(['customer_id' => $customer['id']]);
        $db->prepare('update customer_addresses set is_default = 1 where id = :id and customer_id = :customer_id')->execute(['id' => $id, 'customer_id' => $customer['id']]);
        $this->addresses();
    }

    public function walletCustomer(): ?array
    {
        $this->ensureTables();
        return $this->currentCustomer();
    }

    private function currentCustomer(): ?array
    {
        $token = $this->bearerToken();
        if ($token === '') {
            return null;
        }

        $stmt = Database::connection()->prepare('select * from customers where auth_token = :auth_token and status = 1 limit 1');
        $stmt->execute(['auth_token' => hash('sha256', $token)]);
        $customer = $stmt->fetch();
        return $customer ?: null;
    }

    private function customer(int $id): ?array
    {
        $stmt = Database::connection()->prepare('select * from customers where id = :id and status = 1');
        $stmt->execute(['id' => $id]);
        $customer = $stmt->fetch();
        return $customer ?: null;
    }

    private function guestId(int $customerId): string
    {
        return 'customer-' . $customerId;
    }

    private function authResponse(int $customerId, string $token, string $message): void
    {
        $profile = $this->customer($customerId);
        if ($profile) {
            unset($profile['password'], $profile['auth_token']);
        }

        Response::json([
            'message' => $message,
            'data' => $profile,
            'guest_id' => $this->guestId($customerId),
            'token' => $token,
        ]);
    }

    private function bearerToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }

    private function ensureTables(): void
    {
        $db = Database::connection();
        if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->exec(
                'create table if not exists customers (
                    id bigint unsigned primary key auto_increment,
                    name varchar(190) not null,
                    phone varchar(60) not null unique,
                    email varchar(190) null,
                    password varchar(255) null,
                    auth_token varchar(128) null,
                    status tinyint(1) not null default 1,
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
            $db->exec(
                'create table if not exists customer_addresses (
                    id bigint unsigned primary key auto_increment,
                    customer_id bigint unsigned not null,
                    label varchar(80) null,
                    contact_name varchar(190) null,
                    contact_phone varchar(60) null,
                    address text not null,
                    city varchar(120) null,
                    state varchar(120) null,
                    pincode varchar(30) null,
                    is_default tinyint(1) not null default 0,
                    created_at timestamp null,
                    updated_at timestamp null
                )'
            );
            $this->ensureCustomerColumns($db);
            return;
        }

        $db->exec(
            'create table if not exists customers (
                id integer primary key autoincrement,
                    name text not null,
                    phone text not null unique,
                    email text,
                    password text,
                    auth_token text,
                    status integer not null default 1,
                created_at text,
                updated_at text
            )'
        );
        $this->ensureCustomerColumns($db);
        $db->exec(
            'create table if not exists customer_addresses (
                id integer primary key autoincrement,
                customer_id integer not null,
                label text,
                contact_name text,
                contact_phone text,
                address text not null,
                city text,
                state text,
                pincode text,
                is_default integer not null default 0,
                created_at text,
                updated_at text
            )'
        );
    }

    private function ensureCustomerColumns(\PDO $db): void
    {
        try {
            $db->query('select password from customers limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(255) null' : 'text';
            $db->exec('alter table customers add column password ' . $type);
        }

        try {
            $db->query('select auth_token from customers limit 1');
        } catch (\Throwable) {
            $type = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(128) null' : 'text';
            $db->exec('alter table customers add column auth_token ' . $type);
        }
    }
}
