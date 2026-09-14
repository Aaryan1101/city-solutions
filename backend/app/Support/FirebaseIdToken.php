<?php

declare(strict_types=1);

namespace App\Support;

final class FirebaseIdToken
{
    private const CERTS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    public static function verify(string $token): array
    {
        $projectId = trim(Settings::get('firebase_project_id'));
        if ($projectId === '') {
            $serviceAccount = json_decode(Settings::get('firebase_service_account_json'), true);
            $projectId = is_array($serviceAccount) ? trim((string) ($serviceAccount['project_id'] ?? '')) : '';
        }
        if ($projectId === '') {
            throw new \RuntimeException('Firebase project ID is not configured.');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3 || strlen($token) > 16384) {
            throw new \RuntimeException('Invalid Firebase token.');
        }
        $header = self::decodePart($parts[0]);
        $claims = self::decodePart($parts[1]);
        if (($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
            throw new \RuntimeException('Invalid Firebase token header.');
        }

        $certs = self::certificates();
        $certificate = $certs[(string) $header['kid']] ?? null;
        if (!is_string($certificate) || $certificate === '') {
            // Google may have rotated keys since the cache was written.
            $certs = self::certificates(true);
            $certificate = $certs[(string) $header['kid']] ?? null;
        }
        if (!is_string($certificate) || $certificate === '') {
            throw new \RuntimeException('Firebase signing certificate is unavailable.');
        }

        $signature = self::base64UrlDecode($parts[2]);
        $verified = openssl_verify($parts[0] . '.' . $parts[1], $signature, $certificate, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new \RuntimeException('Firebase token signature is invalid.');
        }

        $now = time();
        $subject = trim((string) ($claims['sub'] ?? ''));
        if (($claims['aud'] ?? '') !== $projectId
            || ($claims['iss'] ?? '') !== 'https://securetoken.google.com/' . $projectId
            || $subject === ''
            || strlen($subject) > 128
            || (int) ($claims['exp'] ?? 0) <= $now
            || (int) ($claims['iat'] ?? 0) > $now + 300
            || (int) ($claims['auth_time'] ?? 0) > $now + 300) {
            throw new \RuntimeException('Firebase token claims are invalid or expired.');
        }

        return $claims;
    }

    private static function decodePart(string $value): array
    {
        $decoded = self::base64UrlDecode($value);
        $json = json_decode($decoded, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Invalid Firebase token encoding.');
        }
        return $json;
    }

    private static function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid Firebase token encoding.');
        }
        return $decoded;
    }

    private static function certificates(bool $refresh = false): array
    {
        $cachePath = dirname(__DIR__, 2) . '/storage/firebase-auth-certs.json';
        if (!$refresh && is_file($cachePath)) {
            $cached = json_decode((string) file_get_contents($cachePath), true);
            if (is_array($cached) && (int) ($cached['expires_at'] ?? 0) > time() && is_array($cached['certs'] ?? null)) {
                return $cached['certs'];
            }
        }

        $ch = curl_init(self::CERTS_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        if (!is_string($response) || $status !== 200) {
            throw new \RuntimeException('Could not validate Firebase authentication right now.');
        }
        $headers = substr($response, 0, $headerSize);
        $certs = json_decode(substr($response, $headerSize), true);
        if (!is_array($certs) || $certs === []) {
            throw new \RuntimeException('Firebase signing certificates are invalid.');
        }
        $maxAge = preg_match('/max-age=(\d+)/i', $headers, $match) === 1 ? (int) $match[1] : 3600;
        $payload = json_encode(['expires_at' => time() + max(300, $maxAge - 60), 'certs' => $certs]);
        if (is_string($payload)) {
            @file_put_contents($cachePath, $payload, LOCK_EX);
        }
        return $certs;
    }
}
