<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class GoogleGeocodingService
{
    public static function reverse(float $latitude, float $longitude): array
    {
        $response = self::request([
            'latlng' => $latitude . ',' . $longitude,
            'language' => 'en',
            'region' => 'in',
        ]);
        $result = $response[0] ?? null;
        if (!is_array($result)) {
            throw new RuntimeException('No address was found for this location.');
        }

        return [
            'address' => (string) ($result['formatted_address'] ?? 'Selected location'),
            'pincode' => self::component($result, 'postal_code'),
            'city' => self::component($result, 'locality')
                ?: self::component($result, 'administrative_area_level_3'),
        ];
    }

    public static function search(string $query): array
    {
        $results = self::request([
            'address' => $query,
            'language' => 'en',
            'region' => 'in',
        ]);

        $places = [];
        foreach (array_slice($results, 0, 8) as $result) {
            if (!is_array($result)) {
                continue;
            }
            $location = $result['geometry']['location'] ?? null;
            if (!is_array($location)) {
                continue;
            }
            $latitude = (float) ($location['lat'] ?? 0);
            $longitude = (float) ($location['lng'] ?? 0);
            if ($latitude === 0.0 || $longitude === 0.0) {
                continue;
            }
            $places[] = [
                'address' => (string) ($result['formatted_address'] ?? $query),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }
        return $places;
    }

    private static function request(array $query): array
    {
        $key = trim(Settings::moduleGet(
            'taxi',
            'google_routes_api_key',
            Settings::get('google_maps_api_key', '')
        ));
        if ($key === '') {
            throw new RuntimeException('Location search is not configured.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Location search is unavailable on this server.');
        }

        $query['key'] = $key;
        $curl = curl_init('https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query($query));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (!is_string($raw) || $status < 200 || $status >= 300) {
            throw new RuntimeException('Location service could not be reached.');
        }

        $decoded = json_decode($raw, true);
        $googleStatus = is_array($decoded) ? (string) ($decoded['status'] ?? '') : '';
        if ($googleStatus === 'ZERO_RESULTS') {
            return [];
        }
        if ($googleStatus !== 'OK') {
            throw new RuntimeException('Location service rejected the request. Check the server API key.');
        }
        return is_array($decoded['results'] ?? null) ? $decoded['results'] : [];
    }

    private static function component(array $result, string $type): string
    {
        foreach (($result['address_components'] ?? []) as $component) {
            if (!is_array($component) || !in_array($type, $component['types'] ?? [], true)) {
                continue;
            }
            return (string) ($component['long_name'] ?? '');
        }
        return '';
    }
}
