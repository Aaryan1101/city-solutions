<?php

declare(strict_types=1);

namespace App\Support;

final class TaxiRouteService
{
    public static function route(float $pickupLat, float $pickupLng, float $dropLat, float $dropLng): array
    {
        $key = trim(Settings::moduleGet('taxi', 'google_routes_api_key', Settings::get('google_maps_api_key', '')));
        if ($key !== '' && function_exists('curl_init')) {
            $google = self::googleRoute($key, $pickupLat, $pickupLng, $dropLat, $dropLng);
            if ($google !== null) {
                return $google;
            }
        }

        $straightKm = self::distanceKm($pickupLat, $pickupLng, $dropLat, $dropLng);
        $distanceKm = max(0.5, round($straightKm * 1.25, 2));
        $durationMinutes = max(3, (int) ceil(($distanceKm / 28) * 60));
        return [
            'distance_km' => $distanceKm,
            'duration_minutes' => $durationMinutes,
            'encoded_polyline' => '',
            'source' => 'geographic_fallback',
        ];
    }

    private static function googleRoute(string $key, float $pickupLat, float $pickupLng, float $dropLat, float $dropLng): ?array
    {
        $payload = json_encode([
            'origin' => ['location' => ['latLng' => ['latitude' => $pickupLat, 'longitude' => $pickupLng]]],
            'destination' => ['location' => ['latLng' => ['latitude' => $dropLat, 'longitude' => $dropLng]]],
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE',
            'computeAlternativeRoutes' => false,
            'languageCode' => 'en-IN',
            'units' => 'METRIC',
        ], JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return null;
        }

        $curl = curl_init('https://routes.googleapis.com/directions/v2:computeRoutes');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . $key,
                'X-Goog-FieldMask: routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline',
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (!is_string($raw) || $status < 200 || $status >= 300) {
            return null;
        }
        $decoded = json_decode($raw, true);
        $route = is_array($decoded) && isset($decoded['routes'][0]) && is_array($decoded['routes'][0])
            ? $decoded['routes'][0]
            : null;
        if ($route === null || (int) ($route['distanceMeters'] ?? 0) <= 0) {
            return null;
        }
        $seconds = (int) rtrim((string) ($route['duration'] ?? '0s'), 's');
        return [
            'distance_km' => round(((int) $route['distanceMeters']) / 1000, 2),
            'duration_minutes' => max(1, (int) ceil($seconds / 60)),
            'encoded_polyline' => (string) ($route['polyline']['encodedPolyline'] ?? ''),
            'source' => 'google_routes',
        ];
    }

    private static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earth * 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));
    }
}
