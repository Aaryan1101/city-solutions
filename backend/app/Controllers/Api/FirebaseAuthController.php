<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\FirebaseIdToken;
use App\Support\Request;
use App\Support\Response;
use App\Support\Security;
use App\Support\Settings;

final class FirebaseAuthController
{
    public function config(): void
    {
        Response::json(['data' => [
            'enabled' => Settings::bool('firebase_auth_enabled'),
            'phone_enabled' => Settings::bool('firebase_phone_auth_enabled'),
            'google_enabled' => Settings::bool('firebase_google_auth_enabled'),
        ]]);
    }

    public function session(): void
    {
        if (!Settings::bool('firebase_auth_enabled')) {
            Response::json(['message' => 'Firebase sign-in is not enabled.'], 503);
            return;
        }
        $idToken = trim((string) (Request::json()['id_token'] ?? ''));
        if ($idToken === '') {
            Response::json(['message' => 'Firebase ID token is required.'], 422);
            return;
        }

        try {
            $claims = FirebaseIdToken::verify($idToken);
        } catch (\Throwable $error) {
            Response::json(['message' => 'Firebase sign-in could not be verified.'], 401);
            return;
        }

        $provider = (string) ($claims['firebase']['sign_in_provider'] ?? '');
        if ($provider === 'phone' && !Settings::bool('firebase_phone_auth_enabled')) {
            Response::json(['message' => 'Phone OTP sign-in is disabled.'], 403);
            return;
        }
        if ($provider === 'google.com' && !Settings::bool('firebase_google_auth_enabled')) {
            Response::json(['message' => 'Google sign-in is disabled.'], 403);
            return;
        }
        if (!in_array($provider, ['phone', 'google.com'], true)) {
            Response::json(['message' => 'This Firebase sign-in provider is not allowed.'], 403);
            return;
        }

        $uid = trim((string) $claims['sub']);
        Security::enforceLoginThrottle('firebase-customer-login', $uid);
        $phone = trim((string) ($claims['phone_number'] ?? ''));
        $email = strtolower(trim((string) ($claims['email'] ?? '')));
        $name = trim((string) ($claims['name'] ?? ''));
        if ($name === '') {
            $name = $phone !== '' ? 'Customer' : (strtok($email, '@') ?: 'Customer');
        }

        $this->ensureSchema();
        $db = Database::connection();
        $stmt = $db->prepare('select * from customers where firebase_uid = :uid limit 1');
        $stmt->execute(['uid' => $uid]);
        $customer = $stmt->fetch();
        if (!$customer && $phone !== '') {
            $stmt = $db->prepare('select * from customers where phone = :phone limit 1');
            $stmt->execute(['phone' => $phone]);
            $customer = $stmt->fetch();
        }
        if (!$customer && $email !== '') {
            $stmt = $db->prepare('select * from customers where lower(email) = :email limit 1');
            $stmt->execute(['email' => $email]);
            $customer = $stmt->fetch();
        }

        $sessionToken = bin2hex(random_bytes(32));
        $storedPhone = $phone !== '' ? $phone : 'firebase:' . $uid;
        if ($customer) {
            $update = $db->prepare(
                'update customers set firebase_uid = :uid, auth_provider = :provider,
                 name = :name, email = :email, phone = :phone, email_verified = :email_verified,
                 phone_verified = :phone_verified, profile_photo = :photo, auth_token = :token,
                 updated_at = CURRENT_TIMESTAMP where id = :id'
            );
            $update->execute([
                'id' => $customer['id'], 'uid' => $uid, 'provider' => $provider,
                'name' => $name, 'email' => $email !== '' ? $email : ($customer['email'] ?? null),
                'phone' => $phone !== '' ? $phone : $customer['phone'],
                'email_verified' => !empty($claims['email_verified']) ? 1 : 0,
                'phone_verified' => $phone !== '' ? 1 : 0,
                'photo' => trim((string) ($claims['picture'] ?? '')) ?: null,
                'token' => hash('sha256', $sessionToken),
            ]);
            $customerId = (int) $customer['id'];
        } else {
            $insert = $db->prepare(
                'insert into customers
                 (name, phone, email, password, auth_token, firebase_uid, auth_provider,
                  email_verified, phone_verified, profile_photo, status, created_at, updated_at)
                 values (:name, :phone, :email, null, :token, :uid, :provider,
                  :email_verified, :phone_verified, :photo, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'name' => $name, 'phone' => $storedPhone, 'email' => $email !== '' ? $email : null,
                'token' => hash('sha256', $sessionToken), 'uid' => $uid, 'provider' => $provider,
                'email_verified' => !empty($claims['email_verified']) ? 1 : 0,
                'phone_verified' => $phone !== '' ? 1 : 0,
                'photo' => trim((string) ($claims['picture'] ?? '')) ?: null,
            ]);
            $customerId = (int) $db->lastInsertId();
        }

        $profile = $db->prepare('select * from customers where id = :id and status = 1 limit 1');
        $profile->execute(['id' => $customerId]);
        $data = $profile->fetch() ?: [];
        unset($data['password'], $data['auth_token']);
        if (str_starts_with((string) ($data['phone'] ?? ''), 'firebase:')) {
            $data['phone'] = '';
        }
        Response::json([
            'message' => 'Sign-in successful',
            'data' => $data,
            'guest_id' => 'customer-' . $customerId,
            'token' => $sessionToken,
        ]);
    }

    private function ensureSchema(): void
    {
        $db = Database::connection();
        $columns = [
            'firebase_uid' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(128) null' : 'text',
            'auth_provider' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'varchar(40) null' : 'text',
            'email_verified' => 'integer not null default 0',
            'phone_verified' => 'integer not null default 0',
            'profile_photo' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'text null' : 'text',
        ];
        foreach ($columns as $name => $type) {
            try {
                $db->query('select ' . $name . ' from customers limit 1');
            } catch (\Throwable) {
                $db->exec('alter table customers add column ' . $name . ' ' . $type);
            }
        }
        try {
            $db->exec('create unique index customers_firebase_uid_unique on customers (firebase_uid)');
        } catch (\Throwable) {
            // Existing index or legacy duplicate data is handled by the UID lookup.
        }
    }
}
