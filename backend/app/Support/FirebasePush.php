<?php

declare(strict_types=1);

namespace App\Support;

final class FirebasePush
{
    public static function sendToRecipient(
        string $moduleKey,
        string $recipientType,
        ?int $recipientId,
        ?string $guestId,
        string $title,
        string $body,
        array $data = []
    ): array {
        if (!Settings::moduleBool($moduleKey, 'firebase_push_enabled')) {
            return ['ok' => true, 'message' => 'Firebase push is disabled for this module.'];
        }

        try {
            NotificationSchema::ensure();
            $lookupType = $recipientType === 'delivery' ? 'delivery_man' : $recipientType;
            $workerTypes = ['delivery_man', 'service_provider', 'taxi_driver', 'worker'];
            $fallbackModule = in_array($lookupType, $workerTypes, true) ? 'worker' : 'global';
            $where = '(module_key = :module_key or module_key = :fallback_module) and owner_type = :owner_type';
            $params = [
                'module_key' => $moduleKey,
                'fallback_module' => $fallbackModule,
                'owner_type' => $lookupType,
            ];
            if ($recipientId !== null && $recipientId > 0) {
                $where .= ' and owner_id = :owner_id';
                $params['owner_id'] = $recipientId;
            } elseif ($guestId !== null && $guestId !== '') {
                $where .= ' and guest_id = :guest_id';
                $params['guest_id'] = $guestId;
            } else {
                return ['ok' => false, 'message' => 'Push recipient is missing.'];
            }

            $stmt = Database::connection()->prepare(
                'select token from device_tokens where ' . $where . ' group by token order by max(last_seen_at) desc, max(id) desc limit 5'
            );
            $stmt->execute($params);
            $tokens = array_map(static fn (array $row): string => (string) $row['token'], $stmt->fetchAll());
            if ($tokens === []) {
                return ['ok' => false, 'message' => 'No device token found for recipient.'];
            }

            $successCount = 0;
            $messages = [];
            foreach ($tokens as $token) {
                $result = self::sendToToken($token, $title, $body, $data, $moduleKey);
                if ((bool) ($result['ok'] ?? false)) {
                    $successCount++;
                } else {
                    $messages[] = (string) ($result['message'] ?? 'Firebase delivery failed.');
                }
            }

            return [
                'ok' => $successCount > 0,
                'message' => $successCount > 0
                    ? 'Firebase accepted ' . $successCount . ' notification(s).'
                    : ($messages[0] ?? 'Firebase delivery failed.'),
                'attempted' => count($tokens),
                'sent' => $successCount,
            ];
        } catch (\Throwable $error) {
            // Push delivery must not break the business flow.
            return ['ok' => false, 'message' => $error->getMessage()];
        }
    }

    public static function sendToToken(string $token, string $title, string $body, array $data = [], ?string $moduleKey = null): array
    {
        if (!self::settingBool($moduleKey, 'firebase_push_enabled')) {
            return ['ok' => false, 'message' => 'Firebase push is disabled in settings.'];
        }
        if (self::setting($moduleKey, 'firebase_service_account_json') !== '') {
            return self::sendHttpV1($token, $title, $body, $data, $moduleKey);
        }
        $serverKey = self::setting($moduleKey, 'firebase_server_key');
        if ($serverKey === '') {
            return ['ok' => false, 'message' => 'Firebase server key or service account JSON is missing.'];
        }
        if ($token === '') {
            return ['ok' => false, 'message' => 'Device token is required.'];
        }

        $payload = json_encode([
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'source' => 'city_solutions_admin_test',
            ] + $data,
        ], JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            return ['ok' => false, 'message' => 'Could not encode Firebase payload.'];
        }

        $response = self::post('https://fcm.googleapis.com/fcm/send', $payload, [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ]);
        if (!$response['ok']) {
            return $response;
        }

        $decoded = json_decode((string) $response['body'], true);
        $success = is_array($decoded) && (int) ($decoded['success'] ?? 0) > 0;

        return [
            'ok' => $success,
            'message' => $success ? 'Firebase accepted the test notification.' : 'Firebase returned an error.',
            'body' => (string) $response['body'],
        ];
    }

    public static function processOutbox(int $limit = 20): void
    {
        try {
            NotificationSchema::ensure();
            $db = Database::connection();
            if ($db->inTransaction()) {
                return;
            }
            $stmt = $db->prepare(
                'select * from push_outbox
                 where sent_at is null and attempts < 5
                 order by id asc
                 limit ' . max(1, min(100, $limit))
            );
            $stmt->execute();
            foreach ($stmt->fetchAll() as $row) {
                $data = json_decode((string) ($row['data_json'] ?? '{}'), true);
                if (!is_array($data)) {
                    $data = [];
                }
                $result = self::sendToRecipient(
                    (string) $row['module_key'],
                    (string) $row['recipient_type'],
                    $row['recipient_id'] === null ? null : (int) $row['recipient_id'],
                    $row['guest_id'] === null ? null : (string) $row['guest_id'],
                    (string) $row['title'],
                    (string) ($row['message'] ?? ''),
                    $data
                );
                if ((bool) ($result['ok'] ?? false)) {
                    $db->prepare(
                        'update push_outbox
                         set sent_at = CURRENT_TIMESTAMP, attempts = attempts + 1, last_error = null, updated_at = CURRENT_TIMESTAMP
                         where id = :id'
                    )->execute(['id' => (int) $row['id']]);
                    continue;
                }

                $db->prepare(
                    'update push_outbox
                     set attempts = attempts + 1, last_error = :last_error, updated_at = CURRENT_TIMESTAMP
                     where id = :id'
                )->execute([
                    'id' => (int) $row['id'],
                    'last_error' => substr((string) ($result['message'] ?? 'Firebase delivery failed.'), 0, 1000),
                ]);
            }
        } catch (\Throwable) {
            // Outbox retries are best effort and must never break the request.
        }
    }

    private static function post(string $url, string $payload, array $headers): array
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            $body = curl_exec($curl);
            $error = curl_error($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($body === false) {
                return ['ok' => false, 'message' => $error ?: 'Firebase request failed.'];
            }

            return [
                'ok' => $status >= 200 && $status < 300,
                'message' => 'HTTP ' . $status,
                'body' => (string) $body,
            ];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return ['ok' => false, 'message' => 'Firebase request failed. cURL is not available.'];
        }

        $status = self::streamStatusCode($http_response_header ?? []);
        return [
            'ok' => $status >= 200 && $status < 300,
            'message' => $status > 0 ? 'HTTP ' . $status : 'Firebase response received.',
            'body' => (string) $body,
        ];
    }

    private static function streamStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string) $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }
        return 0;
    }

    private static function sendHttpV1(string $token, string $title, string $body, array $data, ?string $moduleKey): array
    {
        if ($token === '') {
            return ['ok' => false, 'message' => 'Device token is required.'];
        }

        $serviceAccount = json_decode(self::setting($moduleKey, 'firebase_service_account_json'), true);
        if (!is_array($serviceAccount)) {
            return ['ok' => false, 'message' => 'Firebase service account JSON is invalid.'];
        }

        $projectId = (string) ($serviceAccount['project_id'] ?? self::setting($moduleKey, 'firebase_project_id'));
        $accessToken = self::accessToken($serviceAccount);
        if ($projectId === '' || $accessToken === '') {
            return ['ok' => false, 'message' => 'Firebase project id or access token is missing.'];
        }

        $payload = json_encode([
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map(static fn ($value): string => (string) $value, $data + ['source' => 'city_solutions']),
            ],
        ], JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            return ['ok' => false, 'message' => 'Could not encode Firebase v1 payload.'];
        }

        $response = self::post(
            'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send',
            $payload,
            [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ]
        );
        if (!$response['ok']) {
            return $response;
        }

        return [
            'ok' => true,
            'message' => 'Firebase accepted the notification.',
            'body' => (string) ($response['body'] ?? ''),
        ];
    }

    private static function accessToken(array $serviceAccount): string
    {
        $clientEmail = (string) ($serviceAccount['client_email'] ?? '');
        $privateKey = (string) ($serviceAccount['private_key'] ?? '');
        if ($clientEmail === '' || $privateKey === '' || !function_exists('openssl_sign')) {
            return '';
        }

        $now = time();
        $header = self::base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']) ?: '');
        $claims = self::base64Url(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]) ?: '');
        $unsigned = $header . '.' . $claims;
        $signature = '';
        if (!openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return '';
        }

        $response = self::post(
            'https://oauth2.googleapis.com/token',
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned . '.' . self::base64Url($signature),
            ]),
            ['Content-Type: application/x-www-form-urlencoded']
        );
        if (!$response['ok']) {
            return '';
        }

        $decoded = json_decode((string) ($response['body'] ?? ''), true);
        return is_array($decoded) ? (string) ($decoded['access_token'] ?? '') : '';
    }

    private static function setting(?string $moduleKey, string $key, string $default = ''): string
    {
        return $moduleKey === null ? Settings::get($key, $default) : Settings::moduleGet($moduleKey, $key, $default);
    }

    private static function settingBool(?string $moduleKey, string $key, bool $default = false): bool
    {
        return $moduleKey === null ? Settings::bool($key, $default) : Settings::moduleBool($moduleKey, $key, $default);
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
