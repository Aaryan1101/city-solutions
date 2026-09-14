<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Database;
use App\Support\Env;
use App\Support\RealEstateSchema;
use App\Support\Request;
use App\Support\Response;
use App\Support\Settings;
use App\Support\ZoneSchema;

final class RealEstateController
{
    public function config(): void
    {
        RealEstateSchema::ensure();
        ZoneSchema::ensure();
        Response::json($this->configData());
    }

    public function home(): void
    {
        RealEstateSchema::ensure();
        ZoneSchema::ensure();
        Response::json([
            'config' => $this->configData(),
            'featured_properties' => $this->propertiesData('re_properties.is_featured = 1', [], 10),
            'latest_properties' => $this->propertiesData('1 = 1', [], 20),
            'projects' => $this->projectsData('re_projects.is_featured = 1', [], 8),
            'amenities' => $this->amenitiesData(),
        ]);
    }

    private function configData(): array
    {
        return [
            'app_name' => Settings::moduleGet('real_estate', 'app_name', 'City Real Estate'),
            'currency_symbol' => Settings::moduleGet('real_estate', 'currency_symbol', Settings::get('currency_symbol', '₹')),
            'maintenance_mode' => Settings::moduleBool('real_estate', 'maintenance_mode'),
            'maintenance_message' => Settings::moduleGet('real_estate', 'maintenance_message'),
            'latest_app_version' => Settings::moduleGet('real_estate', 'latest_app_version'),
            'force_update_version' => Settings::moduleGet('real_estate', 'force_update_version'),
            'zones' => ZoneSchema::active(),
            'listing_purposes' => ['sell', 'rent', 'lease'],
            'property_categories' => ['apartment', 'villa', 'plot', 'office', 'shop', 'commercial'],
        ];
    }

    public function properties(): void
    {
        RealEstateSchema::ensure();
        $where = '1 = 1';
        $params = [];

        $query = trim((string) ($_GET['query'] ?? ''));
        if ($query !== '') {
            $where .= ' and (re_properties.title like :query or re_properties.city like :query or re_properties.area like :query or re_properties.address like :query)';
            $params['query'] = '%' . $query . '%';
        }

        foreach (['listing_purpose', 'property_category'] as $key) {
            $value = trim((string) ($_GET[$key] ?? ''));
            if ($value !== '') {
                $where .= ' and re_properties.' . $key . ' = :' . $key;
                $params[$key] = $value;
            }
        }

        $minPrice = (float) ($_GET['min_price'] ?? 0);
        if ($minPrice > 0) {
            $where .= ' and re_properties.price >= :min_price';
            $params['min_price'] = $minPrice;
        }
        $maxPrice = (float) ($_GET['max_price'] ?? 0);
        if ($maxPrice > 0) {
            $where .= ' and re_properties.price <= :max_price';
            $params['max_price'] = $maxPrice;
        }
        $bedrooms = (int) ($_GET['bedrooms'] ?? 0);
        if ($bedrooms > 0) {
            $where .= ' and re_properties.bedrooms >= :bedrooms';
            $params['bedrooms'] = $bedrooms;
        }
        $minArea = (float) ($_GET['min_area'] ?? 0);
        if ($minArea > 0) {
            $where .= ' and (case
                when re_properties.built_up_area >= re_properties.carpet_area and re_properties.built_up_area >= re_properties.plot_area then re_properties.built_up_area
                when re_properties.carpet_area >= re_properties.plot_area then re_properties.carpet_area
                else re_properties.plot_area
            end) >= :min_area';
            $params['min_area'] = $minArea;
        }
        $furnishing = trim((string) ($_GET['furnishing'] ?? ''));
        if ($furnishing !== '') {
            $where .= ' and re_properties.furnishing = :furnishing';
            $params['furnishing'] = $furnishing;
        }
        if ((int) ($_GET['verified_only'] ?? 0) === 1) {
            $where .= ' and re_properties.is_verified = 1';
        }

        Response::json(['data' => $this->propertiesData($where, $params, 80)]);
    }

    public function show(int $id): void
    {
        RealEstateSchema::ensure();
        $db = Database::connection();
        $stmt = $db->prepare(
            'select re_properties.*, re_agents.business_name as agent_name, re_agents.owner_name as agent_owner_name,
                    re_agents.phone as agent_phone, re_agents.email as agent_email, re_agents.profile_image as agent_profile_image
             from re_properties
             left join re_agents on re_agents.id = re_properties.agent_id
             where re_properties.id = :id and re_properties.status = \'approved\'' . ZoneSchema::inlineSql('re_properties') . '
             limit 1'
        );
        $stmt->execute(['id' => $id]);
        $property = $stmt->fetch();
        if (!$property) {
            Response::json(['message' => 'Property not found'], 404);
            return;
        }

        Response::json([
            'data' => $this->formatProperty($property),
            'images' => $this->imagesData($id),
            'amenities' => $this->propertyAmenities($id),
            'floor_plans' => $this->floorPlans($id),
            'similar_properties' => $this->propertiesData(
                're_properties.id <> :id and re_properties.property_category = :category',
                ['id' => $id, 'category' => (string) $property['property_category']],
                6
            ),
        ]);
    }

    public function projects(): void
    {
        RealEstateSchema::ensure();
        Response::json(['data' => $this->projectsData('1 = 1', [], 40)]);
    }

    public function project(int $id): void
    {
        RealEstateSchema::ensure();
        $db = Database::connection();
        $stmt = $db->prepare(
            'select re_projects.*, re_agents.business_name as builder_name, re_agents.phone as builder_phone
             from re_projects
             left join re_agents on re_agents.id = re_projects.builder_id
             where re_projects.id = :id and re_projects.status = \'approved\'' . ZoneSchema::inlineSql('re_projects') . '
             limit 1'
        );
        $stmt->execute(['id' => $id]);
        $project = $stmt->fetch();
        if (!$project) {
            Response::json(['message' => 'Project not found'], 404);
            return;
        }
        $units = $db->prepare('select * from re_project_units where project_id = :id and status = 1 order by sort_order asc, id asc');
        $units->execute(['id' => $id]);
        Response::json([
            'data' => $this->formatProject($project),
            'units' => array_map([$this, 'formatProjectUnit'], $units->fetchAll()),
        ]);
    }

    public function amenities(): void
    {
        RealEstateSchema::ensure();
        Response::json(['data' => $this->amenitiesData()]);
    }

    public function agent(int $id): void
    {
        RealEstateSchema::ensure();
        $db = Database::connection();
        $stmt = $db->prepare(
            'select re_agents.*, zones.name as zone_name
             from re_agents
             left join zones on zones.id = re_agents.zone_id
             where re_agents.id = :id and re_agents.status = \'approved\'' . ZoneSchema::inlineSql('re_agents') . '
             limit 1'
        );
        $stmt->execute(['id' => $id]);
        $agent = $stmt->fetch();
        if (!$agent) {
            Response::json(['message' => 'Agent not found'], 404);
            return;
        }

        Response::json([
            'data' => $this->formatAgent($agent),
            'properties' => $this->propertiesData('re_properties.agent_id = :agent_id', ['agent_id' => $id], 40),
            'projects' => $this->projectsData('re_projects.builder_id = :agent_id', ['agent_id' => $id], 20),
        ]);
    }

    public function favorites(): void
    {
        RealEstateSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select re_properties.*, re_agents.business_name as agent_name
             from re_favorites
             join re_properties on re_properties.id = re_favorites.property_id
             left join re_agents on re_agents.id = re_properties.agent_id
             where re_favorites.guest_id = :guest_id and re_properties.status = \'approved\'
             order by re_favorites.id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => array_map([$this, 'formatProperty'], $stmt->fetchAll())]);
    }

    public function toggleFavorite(): void
    {
        RealEstateSchema::ensure();
        $body = Request::json();
        $guestId = trim((string) ($body['guest_id'] ?? ''));
        $propertyId = (int) ($body['property_id'] ?? 0);
        if ($guestId === '' || $propertyId <= 0) {
            Response::json(['message' => 'guest_id and property_id are required'], 422);
            return;
        }
        $db = Database::connection();
        $existing = $db->prepare('select id from re_favorites where guest_id = :guest_id and property_id = :property_id limit 1');
        $existing->execute(['guest_id' => $guestId, 'property_id' => $propertyId]);
        $id = $existing->fetchColumn();
        if ($id) {
            $delete = $db->prepare('delete from re_favorites where id = :id');
            $delete->execute(['id' => $id]);
            Response::json(['wishlisted' => false]);
            return;
        }
        $insert = $db->prepare('insert into re_favorites (guest_id, property_id, created_at) values (:guest_id, :property_id, CURRENT_TIMESTAMP)');
        $insert->execute(['guest_id' => $guestId, 'property_id' => $propertyId]);
        Response::json(['wishlisted' => true]);
    }

    public function inquiry(): void
    {
        RealEstateSchema::ensure();
        $body = Request::json();
        $propertyId = (int) ($body['property_id'] ?? 0);
        $customerName = trim((string) ($body['customer_name'] ?? ''));
        $customerPhone = trim((string) ($body['customer_phone'] ?? ''));
        if ($propertyId <= 0 || $customerName === '' || $customerPhone === '') {
            Response::json(['message' => 'Property, name and phone are required'], 422);
            return;
        }
        $property = $this->propertyRow($propertyId);
        if (!$property) {
            Response::json(['message' => 'Property not found'], 404);
            return;
        }
        $number = 'REI' . date('ymdHis') . random_int(10, 99);
        $stmt = Database::connection()->prepare(
            'insert into re_inquiries
             (inquiry_number, guest_id, property_id, agent_id, customer_name, customer_phone, customer_email, message, status, created_at, updated_at)
             values (:inquiry_number, :guest_id, :property_id, :agent_id, :customer_name, :customer_phone, :customer_email, :message, \'open\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'inquiry_number' => $number,
            'guest_id' => trim((string) ($body['guest_id'] ?? '')),
            'property_id' => $propertyId,
            'agent_id' => $property['agent_id'] ?? null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => trim((string) ($body['customer_email'] ?? '')),
            'message' => trim((string) ($body['message'] ?? '')),
        ]);
        Response::json(['message' => 'Inquiry submitted', 'inquiry_number' => $number], 201);
    }

    public function inquiries(): void
    {
        RealEstateSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select re_inquiries.*, re_properties.title as property_title, re_properties.thumbnail,
                    re_agents.business_name as agent_name
             from re_inquiries
             left join re_properties on re_properties.id = re_inquiries.property_id
             left join re_agents on re_agents.id = re_inquiries.agent_id
             where re_inquiries.guest_id = :guest_id
             order by re_inquiries.id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => array_map([$this, 'formatInquiry'], $stmt->fetchAll())]);
    }

    public function siteVisit(): void
    {
        RealEstateSchema::ensure();
        $body = Request::json();
        $propertyId = (int) ($body['property_id'] ?? 0);
        $customerName = trim((string) ($body['customer_name'] ?? ''));
        $customerPhone = trim((string) ($body['customer_phone'] ?? ''));
        $date = trim((string) ($body['requested_date'] ?? ''));
        $time = trim((string) ($body['requested_time'] ?? ''));
        if ($propertyId <= 0 || $customerName === '' || $customerPhone === '' || $date === '' || $time === '') {
            Response::json(['message' => 'Property, name, phone, date and time are required'], 422);
            return;
        }
        $property = $this->propertyRow($propertyId);
        if (!$property) {
            Response::json(['message' => 'Property not found'], 404);
            return;
        }
        $number = 'REV' . date('ymdHis') . random_int(10, 99);
        $stmt = Database::connection()->prepare(
            'insert into re_site_visits
             (visit_number, guest_id, property_id, agent_id, customer_name, customer_phone, customer_email, address, requested_date, requested_time, note, status, created_at, updated_at)
             values (:visit_number, :guest_id, :property_id, :agent_id, :customer_name, :customer_phone, :customer_email, :address, :requested_date, :requested_time, :note, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'visit_number' => $number,
            'guest_id' => trim((string) ($body['guest_id'] ?? '')),
            'property_id' => $propertyId,
            'agent_id' => $property['agent_id'] ?? null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => trim((string) ($body['customer_email'] ?? '')),
            'address' => trim((string) ($body['address'] ?? '')),
            'requested_date' => $date,
            'requested_time' => $time,
            'note' => trim((string) ($body['note'] ?? '')),
        ]);
        Response::json(['message' => 'Site visit requested', 'visit_number' => $number], 201);
    }

    public function siteVisits(): void
    {
        RealEstateSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select re_site_visits.*, re_properties.title as property_title, re_properties.thumbnail
             from re_site_visits
             left join re_properties on re_properties.id = re_site_visits.property_id
             where re_site_visits.guest_id = :guest_id
             order by re_site_visits.id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => array_map([$this, 'formatVisit'], $stmt->fetchAll())]);
    }

    public function complaint(): void
    {
        RealEstateSchema::ensure();
        $body = Request::json();
        $propertyId = (int) ($body['property_id'] ?? 0);
        $message = trim((string) ($body['message'] ?? ''));
        if ($propertyId <= 0 || $message === '') {
            Response::json(['message' => 'Property and report details are required'], 422);
            return;
        }
        $property = $this->propertyRow($propertyId);
        if (!$property) {
            Response::json(['message' => 'Property not found'], 404);
            return;
        }
        $number = 'REC' . date('ymdHis') . random_int(10, 99);
        $stmt = Database::connection()->prepare(
            'insert into re_complaints
             (complaint_number, guest_id, property_id, agent_id, customer_name, customer_phone, message, status, created_at, updated_at)
             values (:complaint_number, :guest_id, :property_id, :agent_id, :customer_name, :customer_phone, :message, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'complaint_number' => $number,
            'guest_id' => trim((string) ($body['guest_id'] ?? '')),
            'property_id' => $propertyId,
            'agent_id' => $property['agent_id'] ?? null,
            'customer_name' => trim((string) ($body['customer_name'] ?? 'Customer')) ?: 'Customer',
            'customer_phone' => trim((string) ($body['customer_phone'] ?? '')),
            'message' => $message,
        ]);
        Response::json(['message' => 'Listing report submitted', 'complaint_number' => $number], 201);
    }

    public function savedSearches(): void
    {
        RealEstateSchema::ensure();
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'select * from re_saved_searches where guest_id = :guest_id order by id desc'
        );
        $stmt->execute(['guest_id' => $guestId]);
        Response::json(['data' => array_map(function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['notify'] = (bool) ($row['notify'] ?? false);
            $row['filters'] = json_decode((string) ($row['filters_json'] ?? '{}'), true) ?: [];
            unset($row['filters_json']);
            return $row;
        }, $stmt->fetchAll())]);
    }

    public function saveSearch(): void
    {
        RealEstateSchema::ensure();
        $body = Request::json();
        $guestId = trim((string) ($body['guest_id'] ?? ''));
        $name = trim((string) ($body['name'] ?? 'Saved search'));
        $filters = $body['filters'] ?? [];
        if ($guestId === '' || !is_array($filters)) {
            Response::json(['message' => 'guest_id and filters are required'], 422);
            return;
        }
        $stmt = Database::connection()->prepare(
            'insert into re_saved_searches (guest_id, name, filters_json, notify, created_at, updated_at)
             values (:guest_id, :name, :filters_json, :notify, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'guest_id' => $guestId,
            'name' => $name === '' ? 'Saved search' : $name,
            'filters_json' => json_encode($filters, JSON_UNESCAPED_SLASHES),
            'notify' => !empty($body['notify']) ? 1 : 0,
        ]);
        Response::json(['message' => 'Saved search added'], 201);
    }

    private function propertiesData(string $where, array $params, int $limit): array
    {
        $stmt = Database::connection()->prepare(
            'select re_properties.*, re_agents.business_name as agent_name, re_agents.phone as agent_phone
             from re_properties
             left join re_agents on re_agents.id = re_properties.agent_id
             where re_properties.status = \'approved\' and re_properties.availability_status = \'available\' and ' . $where . ZoneSchema::inlineSql('re_properties') . '
             order by re_properties.is_featured desc, re_properties.id desc
             limit ' . $limit
        );
        $stmt->execute($params);
        return array_map([$this, 'formatProperty'], $stmt->fetchAll());
    }

    private function projectsData(string $where, array $params, int $limit): array
    {
        $stmt = Database::connection()->prepare(
            'select re_projects.*, re_agents.business_name as builder_name
             from re_projects
             left join re_agents on re_agents.id = re_projects.builder_id
             where re_projects.status = \'approved\' and ' . $where . ZoneSchema::inlineSql('re_projects') . '
             order by re_projects.is_featured desc, re_projects.id desc
             limit ' . $limit
        );
        $stmt->execute($params);
        return array_map([$this, 'formatProject'], $stmt->fetchAll());
    }

    private function amenitiesData(): array
    {
        return Database::connection()->query('select * from re_amenities where status = 1 order by sort_order asc, id asc')->fetchAll();
    }

    private function imagesData(int $propertyId): array
    {
        $stmt = Database::connection()->prepare('select image_url from re_property_images where property_id = :id order by sort_order asc, id asc');
        $stmt->execute(['id' => $propertyId]);
        return array_map(fn (array $row): string => $this->assetUrl((string) $row['image_url']), $stmt->fetchAll());
    }

    private function propertyAmenities(int $propertyId): array
    {
        $stmt = Database::connection()->prepare(
            'select re_amenities.*
             from re_property_amenities
             join re_amenities on re_amenities.id = re_property_amenities.amenity_id
             where re_property_amenities.property_id = :id and re_amenities.status = 1
             order by re_amenities.sort_order asc, re_amenities.id asc'
        );
        $stmt->execute(['id' => $propertyId]);
        return $stmt->fetchAll();
    }

    private function floorPlans(int $propertyId): array
    {
        $stmt = Database::connection()->prepare('select * from re_floor_plans where property_id = :id order by sort_order asc, id asc');
        $stmt->execute(['id' => $propertyId]);
        return array_map(function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['area'] = (float) $row['area'];
            $row['bedrooms'] = (int) $row['bedrooms'];
            $row['bathrooms'] = (int) $row['bathrooms'];
            if (($row['image_url'] ?? '') !== '') {
                $row['image_full_url'] = $this->assetUrl((string) $row['image_url']);
            }
            return $row;
        }, $stmt->fetchAll());
    }

    private function propertyRow(int $id): ?array
    {
        $stmt = Database::connection()->prepare('select * from re_properties where id = :id and status = \'approved\' limit 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function formatProperty(array $row): array
    {
        foreach (['id', 'agent_id', 'zone_id', 'bedrooms', 'bathrooms', 'balconies', 'parking'] as $key) {
            $row[$key] = (int) ($row[$key] ?? 0);
        }
        foreach (['price', 'maintenance_charge', 'deposit_amount', 'latitude', 'longitude', 'built_up_area', 'carpet_area', 'plot_area'] as $key) {
            $row[$key] = $row[$key] === null ? null : (float) $row[$key];
        }
        $row['is_verified'] = (bool) ($row['is_verified'] ?? false);
        $row['is_featured'] = (bool) ($row['is_featured'] ?? false);
        if (($row['thumbnail'] ?? '') !== '') {
            $row['thumbnail_full_url'] = $this->assetUrl((string) $row['thumbnail']);
        }
        if (($row['agent_profile_image'] ?? '') !== '') {
            $row['agent_profile_full_url'] = $this->assetUrl((string) $row['agent_profile_image']);
        }
        return $row;
    }

    private function formatAgent(array $row): array
    {
        foreach (['id', 'zone_id'] as $key) {
            $row[$key] = (int) ($row[$key] ?? 0);
        }
        if (($row['profile_image'] ?? '') !== '') {
            $row['profile_image_full_url'] = $this->assetUrl((string) $row['profile_image']);
        }
        return $row;
    }

    private function formatProject(array $row): array
    {
        foreach (['id', 'builder_id', 'zone_id'] as $key) {
            $row[$key] = (int) ($row[$key] ?? 0);
        }
        foreach (['latitude', 'longitude'] as $key) {
            $row[$key] = $row[$key] === null ? null : (float) $row[$key];
        }
        $row['is_featured'] = (bool) ($row['is_featured'] ?? false);
        if (($row['thumbnail'] ?? '') !== '') {
            $row['thumbnail_full_url'] = $this->assetUrl((string) $row['thumbnail']);
        }
        return $row;
    }

    private function formatProjectUnit(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['project_id'] = (int) $row['project_id'];
        $row['price_from'] = (float) $row['price_from'];
        $row['area'] = (float) $row['area'];
        $row['bedrooms'] = (int) $row['bedrooms'];
        $row['bathrooms'] = (int) $row['bathrooms'];
        if (($row['floor_plan_image'] ?? '') !== '') {
            $row['floor_plan_full_url'] = $this->assetUrl((string) $row['floor_plan_image']);
        }
        return $row;
    }

    private function formatVisit(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['property_id'] = (int) $row['property_id'];
        $row['agent_id'] = (int) ($row['agent_id'] ?? 0);
        if (($row['thumbnail'] ?? '') !== '') {
            $row['thumbnail_full_url'] = $this->assetUrl((string) $row['thumbnail']);
        }
        return $row;
    }

    private function formatInquiry(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['property_id'] = (int) $row['property_id'];
        $row['agent_id'] = (int) ($row['agent_id'] ?? 0);
        if (($row['thumbnail'] ?? '') !== '') {
            $row['thumbnail_full_url'] = $this->assetUrl((string) $row['thumbnail']);
        }
        return $row;
    }

    private function assetUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }
        return $this->publicBaseUrl() . '/' . ltrim($path, '/');
    }

    private function publicBaseUrl(): string
    {
        $configured = rtrim(Env::get('APP_URL', ''), '/');
        if ($configured !== '' && !str_contains($configured, '127.0.0.1') && !str_contains($configured, 'localhost')) {
            return $configured;
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        if ($proto === null) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        }
        return rtrim($proto . '://' . $host, '/');
    }
}
