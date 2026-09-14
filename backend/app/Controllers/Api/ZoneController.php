<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\GoogleGeocodingService;
use App\Support\RateLimiter;
use App\Support\Response;
use App\Support\Request;
use App\Support\Security;
use App\Support\ZoneSchema;
use RuntimeException;

final class ZoneController
{
    public function index(): void
    {
        Response::json(['data' => ZoneSchema::active()]);
    }

    public function resolve(): void
    {
        $body = Request::json();
        $latitude = (float) ($body['latitude'] ?? $_GET['latitude'] ?? 0);
        $longitude = (float) ($body['longitude'] ?? $_GET['longitude'] ?? 0);
        if ($latitude == 0.0 || $longitude == 0.0) {
            Response::json(['message' => 'Latitude and longitude are required'], 422);
            return;
        }
        $zone = ZoneSchema::resolve(
            $latitude,
            $longitude,
            trim((string) ($body['pincode'] ?? $_GET['pincode'] ?? '')),
            trim((string) ($body['city'] ?? $_GET['city'] ?? ''))
        );
        Response::json([
            'data' => [
                'zone' => $zone,
                'serviceable' => $zone !== null,
            ],
        ]);
    }

    public function reverseGeocode(): void
    {
        $this->limitGeocoding();
        $body = Request::json();
        $latitude = (float) ($body['latitude'] ?? 0);
        $longitude = (float) ($body['longitude'] ?? 0);
        if (!$this->validCoordinates($latitude, $longitude)) {
            Response::json(['message' => 'Valid latitude and longitude are required.'], 422);
            return;
        }

        try {
            Response::json(['data' => GoogleGeocodingService::reverse($latitude, $longitude)]);
        } catch (RuntimeException $error) {
            Response::json(['message' => $error->getMessage()], 503);
        }
    }

    public function search(): void
    {
        $this->limitGeocoding();
        $body = Request::json();
        $query = trim((string) ($body['query'] ?? ''));
        $length = function_exists('mb_strlen') ? mb_strlen($query) : strlen($query);
        if ($length < 3 || $length > 200) {
            Response::json(['message' => 'Enter an address between 3 and 200 characters.'], 422);
            return;
        }

        try {
            Response::json(['data' => GoogleGeocodingService::search($query)]);
        } catch (RuntimeException $error) {
            Response::json(['message' => $error->getMessage()], 503);
        }
    }

    private function validCoordinates(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90
            && $longitude >= -180 && $longitude <= 180
            && !($latitude === 0.0 && $longitude === 0.0);
    }

    private function limitGeocoding(): void
    {
        RateLimiter::enforceKeyLimit(
            'geocoding',
            Security::clientIp(),
            30,
            60,
            'Too many location searches. Please wait a minute and try again.'
        );
    }
}
