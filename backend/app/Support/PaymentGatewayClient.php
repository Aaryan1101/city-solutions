<?php

declare(strict_types=1);

namespace App\Support;

final class PaymentGatewayClient
{
    public static function capture(string $moduleKey, array $payment, array $subject): array
    {
        return self::request($moduleKey, 'capture', [
            'module_key' => $moduleKey,
            'payment_id' => (int) ($payment['id'] ?? 0),
            'order_id' => (int) ($payment['order_id'] ?? 0),
            'booking_id' => (int) ($payment['booking_id'] ?? 0),
            'number' => (string) ($subject['order_number'] ?? $subject['booking_number'] ?? ''),
            'amount' => (float) ($payment['amount'] ?? $subject['grand_total'] ?? $subject['order_amount'] ?? 0),
            'reference' => (string) ($payment['reference'] ?? $subject['payment_reference'] ?? ''),
            'payment_method' => (string) ($payment['payment_method'] ?? $subject['payment_method'] ?? ''),
        ]);
    }

    public static function status(string $moduleKey, array $payment, array $subject): array
    {
        return self::request($moduleKey, 'status', [
            'module_key' => $moduleKey,
            'payment_id' => (int) ($payment['id'] ?? 0),
            'order_id' => (int) ($payment['order_id'] ?? 0),
            'booking_id' => (int) ($payment['booking_id'] ?? 0),
            'number' => (string) ($subject['order_number'] ?? $subject['booking_number'] ?? ''),
            'reference' => (string) ($payment['reference'] ?? $subject['payment_reference'] ?? ''),
        ]);
    }

    public static function refund(string $moduleKey, array $refund, array $subject): array
    {
        return self::request($moduleKey, 'refund', [
            'module_key' => $moduleKey,
            'refund_id' => (int) ($refund['id'] ?? 0),
            'order_id' => (int) ($refund['order_id'] ?? 0),
            'booking_id' => (int) ($refund['booking_id'] ?? 0),
            'number' => (string) ($subject['order_number'] ?? $subject['booking_number'] ?? ''),
            'amount' => (float) ($refund['amount'] ?? $refund['refund_amount'] ?? 0),
            'reference' => (string) ($subject['payment_reference'] ?? $refund['payment_reference'] ?? ''),
            'reason' => (string) ($refund['reason'] ?? $refund['cancellation_reason'] ?? ''),
        ]);
    }

    private static function request(string $moduleKey, string $action, array $payload): array
    {
        $url = Settings::moduleGet($moduleKey, 'online_payment_' . $action . '_url');
        if ($url === '') {
            return ['ok' => false, 'message' => ucfirst($action) . ' URL is not configured.'];
        }
        $urlError = Security::validateOutboundHttpsUrl($url);
        if ($urlError !== null) {
            return ['ok' => false, 'message' => 'Payment gateway URL rejected: ' . $urlError];
        }

        $headers = ['Content-Type: application/json'];
        $authHeader = Settings::moduleGet($moduleKey, 'online_payment_auth_header');
        if ($authHeader !== '') {
            $headers[] = $authHeader;
        } elseif (Settings::moduleGet($moduleKey, 'online_payment_secret_key') !== '') {
            $headers[] = 'Authorization: Bearer ' . Settings::moduleGet($moduleKey, 'online_payment_secret_key');
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return ['ok' => false, 'message' => 'Could not encode payment gateway payload.'];
        }

        $response = self::post($url, $body, $headers);
        $decoded = json_decode((string) ($response['body'] ?? ''), true);
        if (is_array($decoded)) {
            $status = strtolower((string) ($decoded['status'] ?? $decoded['payment_status'] ?? ''));
            if (in_array($status, ['paid', 'captured', 'success', 'succeeded', 'verified', 'refunded'], true)) {
                $response['ok'] = true;
            }
        }

        return $response;
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
                CURLOPT_TIMEOUT => 20,
            ]);
            $body = curl_exec($curl);
            $error = curl_error($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($body === false) {
                return ['ok' => false, 'message' => $error ?: 'Gateway request failed.'];
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
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return ['ok' => false, 'message' => 'Gateway request failed. cURL is not available.'];
        }

        $status = self::streamStatusCode($http_response_header ?? []);
        return [
            'ok' => $status >= 200 && $status < 300,
            'message' => $status > 0 ? 'HTTP ' . $status : 'Gateway response received.',
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
}
