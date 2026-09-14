<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\ComplaintSchema;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\Upload;

final class ComplaintController
{
    public function store(): void
    {
        ComplaintSchema::ensure();
        $body = Request::json();
        $moduleKey = $this->moduleKey((string) ($body['module_key'] ?? 'global'));
        $severity = $this->severity((string) ($body['severity'] ?? 'normal'));
        $category = trim((string) ($body['category'] ?? 'Other')) ?: 'Other';
        $location = trim((string) ($body['location'] ?? ''));
        if ($location === '') {
            Response::json(['message' => 'Location is required'], 422);
            return;
        }
        $description = trim((string) ($body['description'] ?? ''));
        if (strlen($description) < 10) {
            Response::json(['message' => 'Please describe the issue in at least 10 characters'], 422);
            return;
        }

        $imagePath = $this->saveImage(
            trim((string) ($body['image_base64'] ?? '')),
            trim((string) ($body['image_name'] ?? 'complaint.jpg'))
        );
        $number = 'CMP' . date('ymdHis') . random_int(100, 999);
        $stmt = Database::connection()->prepare(
            'insert into complaints
             (complaint_number, module_key, severity, guest_id, customer_name, customer_phone, zone_id, order_id, booking_id, vendor_id, provider_id, hotel_id, real_estate_property_id, real_estate_agent_id, category, location, description, image_path, status, created_at, updated_at)
             values (:complaint_number, :module_key, :severity, :guest_id, :customer_name, :customer_phone, :zone_id, :order_id, :booking_id, :vendor_id, :provider_id, :hotel_id, :real_estate_property_id, :real_estate_agent_id, :category, :location, :description, :image_path, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'complaint_number' => $number,
            'module_key' => $moduleKey,
            'severity' => $severity,
            'guest_id' => trim((string) ($body['guest_id'] ?? '')) ?: null,
            'customer_name' => trim((string) ($body['customer_name'] ?? '')) ?: null,
            'customer_phone' => trim((string) ($body['customer_phone'] ?? '')) ?: null,
            'zone_id' => $this->nullableId($body['zone_id'] ?? null),
            'order_id' => $this->nullableId($body['order_id'] ?? null),
            'booking_id' => $this->nullableId($body['booking_id'] ?? null),
            'vendor_id' => $this->nullableId($body['vendor_id'] ?? null),
            'provider_id' => $this->nullableId($body['provider_id'] ?? null),
            'hotel_id' => $this->nullableId($body['hotel_id'] ?? null),
            'real_estate_property_id' => $this->nullableId($body['real_estate_property_id'] ?? null),
            'real_estate_agent_id' => $this->nullableId($body['real_estate_agent_id'] ?? null),
            'category' => $category,
            'location' => $location,
            'description' => $description,
            'image_path' => $imagePath,
        ]);

        Response::json([
            'message' => 'Complaint submitted',
            'data' => [
                'id' => (int) Database::connection()->lastInsertId(),
                'complaint_number' => $number,
                'status' => 'pending',
            ],
        ], 201);
    }

    private function moduleKey(string $value): string
    {
        return in_array($value, ['global', 'mart', 'ecommerce', 'medical', 'services', 'hotel', 'restaurant', 'real_estate'], true)
            ? $value
            : 'global';
    }

    private function severity(string $value): string
    {
        return in_array($value, ['low', 'normal', 'high', 'urgent'], true) ? $value : 'normal';
    }

    private function nullableId(mixed $value): ?int
    {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    private function saveImage(string $base64, string $name): ?string
    {
        if ($base64 === '') {
            return null;
        }
        return Upload::base64Image($base64, 'complaints', $name);
    }
}
