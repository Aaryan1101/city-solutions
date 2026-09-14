<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\NotificationSchema;
use App\Support\Request;
use App\Support\Response;

final class DeviceTokenController
{
    public function __construct(private readonly string $moduleKey = 'mart')
    {
    }

    public function register(): void
    {
        NotificationSchema::ensure();
        $body = Request::json();
        $token = trim((string) ($body['token'] ?? ''));
        if ($token === '') {
            Response::json(['message' => 'Device token is required'], 422);
            return;
        }

        $ownerType = trim((string) ($body['owner_type'] ?? 'customer'));
        if (!in_array($ownerType, ['customer', 'vendor', 'provider', 'admin', 'delivery_man', 'service_provider', 'taxi_driver', 'worker'], true)) {
            $ownerType = 'customer';
        }
        $guestId = trim((string) ($body['guest_id'] ?? ''));
        $ownerId = (int) ($body['owner_id'] ?? 0);
        $platform = trim((string) ($body['platform'] ?? 'android')) ?: null;

        $db = Database::connection();
        if ($this->moduleKey === 'worker') {
            $worker = $this->authenticatedWorker($db);
            if ($worker === null) {
                Response::json(['message' => 'Worker login required'], 401);
                return;
            }
            $ownerType = $worker['role'];
            $ownerId = $worker['id'];
            $guestId = '';
        } elseif ($ownerId > 0 && !$this->authenticatedCustomer($db, $ownerId)) {
            Response::json(['message' => 'Customer login required'], 401);
            return;
        }

        $existing = $db->prepare(
            'select id from device_tokens where token = :token and module_key = :module_key limit 1'
        );
        $existing->execute(['token' => $token, 'module_key' => $this->moduleKey]);
        $id = (int) ($existing->fetchColumn() ?: 0);

        if ($id > 0) {
            $stmt = $db->prepare(
                'update device_tokens
                 set module_key = :module_key, owner_type = :owner_type, owner_id = :owner_id,
                     guest_id = :guest_id, platform = :platform, last_seen_at = CURRENT_TIMESTAMP,
                     updated_at = CURRENT_TIMESTAMP
                 where id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'module_key' => $this->moduleKey,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId > 0 ? $ownerId : null,
                'guest_id' => $guestId === '' ? null : $guestId,
                'platform' => $platform,
            ]);
        } else {
            $stmt = $db->prepare(
                'insert into device_tokens
                 (module_key, owner_type, owner_id, guest_id, token, platform, last_seen_at, created_at, updated_at)
                 values (:module_key, :owner_type, :owner_id, :guest_id, :token, :platform, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                'module_key' => $this->moduleKey,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId > 0 ? $ownerId : null,
                'guest_id' => $guestId === '' ? null : $guestId,
                'token' => $token,
                'platform' => $platform,
            ]);
        }

        Response::json(['message' => 'Device token saved']);
    }

    private function authenticatedCustomer(\PDO $db, int $customerId): bool
    {
        $token = $this->bearerToken();
        if ($token === '') {
            return false;
        }
        $stmt = $db->prepare('select id from customers where id = :id and auth_token = :token and status = 1 limit 1');
        $stmt->execute(['id' => $customerId, 'token' => hash('sha256', $token)]);
        return (bool) $stmt->fetchColumn();
    }

    private function authenticatedWorker(\PDO $db): ?array
    {
        $token = $this->bearerToken();
        if ($token === '') {
            return null;
        }
        $hash = hash('sha256', $token);
        foreach ([
            ['delivery_man', 'delivery_men', 'status = 1'],
            ['service_provider', 'service_providers', 'status = 1'],
            ['taxi_driver', 'taxi_drivers', 'status = \'approved\''],
        ] as [$role, $table, $status]) {
            $stmt = $db->prepare(
                'select id from ' . $table . ' where auth_token = :token and ' . $status .
                ' and (auth_token_expires_at is null or auth_token_expires_at > CURRENT_TIMESTAMP) limit 1'
            );
            $stmt->execute(['token' => $hash]);
            $id = (int) ($stmt->fetchColumn() ?: 0);
            if ($id > 0) {
                return ['role' => $role, 'id' => $id];
            }
        }
        return null;
    }

    private function bearerToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        return preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1 ? trim($matches[1]) : '';
    }
}
