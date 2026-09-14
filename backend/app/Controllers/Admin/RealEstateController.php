<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\RealEstateSchema;
use App\Support\Request;
use App\Support\Response;
use App\Support\Upload;
use App\Support\View;
use App\Support\ZoneSchema;

final class RealEstateController
{
    public function index(): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        ZoneSchema::ensure();
        $db = Database::connection();

        $agents = $db->prepare(
            'select re_agents.*, zones.name as zone_name
             from re_agents
             left join zones on zones.id = re_agents.zone_id
             where 1 = 1' . Auth::zoneWhere('re_agents') . '
             order by re_agents.id desc'
        );
        $agents->execute(Auth::zoneParams());

        $properties = $db->prepare(
            'select re_properties.*, re_agents.business_name as agent_name, zones.name as zone_name
             from re_properties
             left join re_agents on re_agents.id = re_properties.agent_id
             left join zones on zones.id = re_properties.zone_id
             where 1 = 1' . Auth::zoneWhere('re_properties') . '
             order by re_properties.id desc'
        );
        $properties->execute(Auth::zoneParams());

        $projects = $db->prepare(
            'select re_projects.*, re_agents.business_name as builder_name, zones.name as zone_name
             from re_projects
             left join re_agents on re_agents.id = re_projects.builder_id
             left join zones on zones.id = re_projects.zone_id
             where 1 = 1' . Auth::zoneWhere('re_projects') . '
             order by re_projects.id desc'
        );
        $projects->execute(Auth::zoneParams());

        $units = $db->prepare(
            'select re_project_units.*, re_projects.name as project_name
             from re_project_units
             inner join re_projects on re_projects.id = re_project_units.project_id
             where 1 = 1' . Auth::zoneWhere('re_projects') . '
             order by re_project_units.sort_order asc, re_project_units.id desc'
        );
        $units->execute(Auth::zoneParams());

        $inquiries = $db->prepare(
            'select re_inquiries.*, re_properties.title as property_title, re_agents.business_name as agent_name
             from re_inquiries
             left join re_properties on re_properties.id = re_inquiries.property_id
             left join re_agents on re_agents.id = re_inquiries.agent_id
             where 1 = 1' . Auth::zoneWhere('re_properties') . '
             order by re_inquiries.id desc
             limit 100'
        );
        $inquiries->execute(Auth::zoneParams());

        $visits = $db->prepare(
            'select re_site_visits.*, re_properties.title as property_title, re_agents.business_name as agent_name
             from re_site_visits
             left join re_properties on re_properties.id = re_site_visits.property_id
             left join re_agents on re_agents.id = re_site_visits.agent_id
             where 1 = 1' . Auth::zoneWhere('re_properties') . '
             order by re_site_visits.id desc
             limit 100'
        );
        $visits->execute(Auth::zoneParams());

        $complaints = $db->prepare(
            'select re_complaints.*, re_properties.title as property_title, re_agents.business_name as agent_name
             from re_complaints
             left join re_properties on re_properties.id = re_complaints.property_id
             left join re_agents on re_agents.id = re_complaints.agent_id
             where 1 = 1' . Auth::zoneWhere('re_properties') . '
             order by re_complaints.id desc
             limit 100'
        );
        $complaints->execute(Auth::zoneParams());

        View::render('admin/real_estate', [
            'title' => 'Real Estate',
            'zones' => $this->zones(),
            'agents' => $agents->fetchAll(),
            'amenities' => $db->query('select * from re_amenities order by sort_order asc, id desc')->fetchAll(),
            'properties' => $properties->fetchAll(),
            'projects' => $projects->fetchAll(),
            'units' => $units->fetchAll(),
            'inquiries' => $inquiries->fetchAll(),
            'visits' => $visits->fetchAll(),
            'complaints' => $complaints->fetchAll(),
        ]);
    }

    public function storeAgent(): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $profileImage = Upload::image('profile_image', 'real-estate/agents');
        $password = trim((string) Request::input('password'));
        $stmt = Database::connection()->prepare(
            'insert into re_agents
             (zone_id, business_name, owner_name, phone, email, password, license_no, bio, profile_image, status, admin_note, created_at, updated_at)
             values
             (:zone_id, :business_name, :owner_name, :phone, :email, :password, :license_no, :bio, :profile_image, :status, :admin_note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'zone_id' => $this->zoneId(),
            'business_name' => trim((string) Request::input('business_name')),
            'owner_name' => trim((string) Request::input('owner_name')),
            'phone' => trim((string) Request::input('phone')) ?: null,
            'email' => trim((string) Request::input('email')) ?: null,
            'password' => $password === '' ? null : password_hash($password, PASSWORD_DEFAULT),
            'license_no' => trim((string) Request::input('license_no')) ?: null,
            'bio' => trim((string) Request::input('bio')) ?: null,
            'profile_image' => $profileImage,
            'status' => trim((string) Request::input('status', 'approved')),
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ]);
        Response::redirect('/admin/real-estate#agents');
    }

    public function updateAgent(int $id): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $current = $this->find('re_agents', $id);
        if (!$current) {
            Response::redirect('/admin/real-estate#agents');
        }
        $profileImage = Upload::image('profile_image', 'real-estate/agents') ?: ($current['profile_image'] ?? null);
        $password = trim((string) Request::input('password'));
        $passwordSql = $password === '' ? '' : ', password = :password';
        $params = [
            'id' => $id,
            'zone_id' => $this->zoneId(),
            'business_name' => trim((string) Request::input('business_name')),
            'owner_name' => trim((string) Request::input('owner_name')),
            'phone' => trim((string) Request::input('phone')) ?: null,
            'email' => trim((string) Request::input('email')) ?: null,
            'license_no' => trim((string) Request::input('license_no')) ?: null,
            'bio' => trim((string) Request::input('bio')) ?: null,
            'profile_image' => $profileImage,
            'status' => trim((string) Request::input('status', 'approved')),
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ];
        if ($password !== '') {
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $stmt = Database::connection()->prepare(
            'update re_agents set zone_id = :zone_id, business_name = :business_name, owner_name = :owner_name,
                phone = :phone, email = :email' . $passwordSql . ', license_no = :license_no, bio = :bio,
                profile_image = :profile_image, status = :status, admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP
             where id = :id' . Auth::zoneWhere('re_agents')
        );
        $stmt->execute(Auth::zoneParams($params));
        Response::redirect('/admin/real-estate#agents');
    }

    public function storeAmenity(): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $stmt = Database::connection()->prepare(
            'insert into re_amenities (name, icon, status, sort_order, created_at, updated_at)
             values (:name, :icon, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->amenityParams());
        Response::redirect('/admin/real-estate#amenities');
    }

    public function updateAmenity(int $id): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $params = $this->amenityParams();
        $params['id'] = $id;
        Database::connection()->prepare(
            'update re_amenities set name = :name, icon = :icon, status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute($params);
        Response::redirect('/admin/real-estate#amenities');
    }

    public function storeProperty(): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $thumbnail = Upload::image('thumbnail', 'real-estate/properties');
        $gallery = Upload::images('gallery', 'real-estate/properties');
        $db = Database::connection();
        $stmt = $db->prepare(
            'insert into re_properties
             (agent_id, zone_id, title, slug, listing_purpose, property_category, price, price_unit, maintenance_charge,
              deposit_amount, city, area, address, latitude, longitude, description, bedrooms, bathrooms, balconies,
              parking, built_up_area, carpet_area, plot_area, area_unit, furnishing, ownership_type, property_age,
              contact_preference, thumbnail, status, availability_status, is_verified, verified_at, is_featured,
              rejection_reason, created_at, updated_at)
             values
             (:agent_id, :zone_id, :title, :slug, :listing_purpose, :property_category, :price, :price_unit, :maintenance_charge,
              :deposit_amount, :city, :area, :address, :latitude, :longitude, :description, :bedrooms, :bathrooms, :balconies,
              :parking, :built_up_area, :carpet_area, :plot_area, :area_unit, :furnishing, :ownership_type, :property_age,
              :contact_preference, :thumbnail, :status, :availability_status, :is_verified, :verified_at, :is_featured,
              :rejection_reason, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $params = $this->propertyParams($thumbnail);
        $stmt->execute($params);
        $propertyId = (int) $db->lastInsertId();
        $this->syncPropertyImages($propertyId, $gallery);
        $this->syncPropertyAmenities($propertyId, $_POST['amenity_ids'] ?? []);
        Response::redirect('/admin/real-estate#properties');
    }

    public function updateProperty(int $id): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $current = $this->find('re_properties', $id);
        if (!$current) {
            Response::redirect('/admin/real-estate#properties');
        }
        $thumbnail = Upload::image('thumbnail', 'real-estate/properties') ?: ($current['thumbnail'] ?? null);
        $gallery = Upload::images('gallery', 'real-estate/properties');
        $params = $this->propertyParams($thumbnail);
        $params['id'] = $id;
        $stmt = Database::connection()->prepare(
            'update re_properties set agent_id = :agent_id, zone_id = :zone_id, title = :title, slug = :slug,
                listing_purpose = :listing_purpose, property_category = :property_category, price = :price, price_unit = :price_unit,
                maintenance_charge = :maintenance_charge, deposit_amount = :deposit_amount, city = :city, area = :area,
                address = :address, latitude = :latitude, longitude = :longitude, description = :description,
                bedrooms = :bedrooms, bathrooms = :bathrooms, balconies = :balconies, parking = :parking,
                built_up_area = :built_up_area, carpet_area = :carpet_area, plot_area = :plot_area, area_unit = :area_unit,
                furnishing = :furnishing, ownership_type = :ownership_type, property_age = :property_age,
                contact_preference = :contact_preference, thumbnail = :thumbnail, status = :status,
                availability_status = :availability_status, is_verified = :is_verified, verified_at = :verified_at,
                is_featured = :is_featured, rejection_reason = :rejection_reason, updated_at = CURRENT_TIMESTAMP
             where id = :id' . Auth::zoneWhere('re_properties')
        );
        $stmt->execute(Auth::zoneParams($params));
        if ($gallery !== []) {
            $this->syncPropertyImages($id, $gallery, true);
        }
        $this->syncPropertyAmenities($id, $_POST['amenity_ids'] ?? []);
        Response::redirect('/admin/real-estate#properties');
    }

    public function storeProject(): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $thumbnail = Upload::image('thumbnail', 'real-estate/projects');
        $stmt = Database::connection()->prepare(
            'insert into re_projects
             (builder_id, zone_id, name, slug, city, area, address, latitude, longitude, description, launch_date,
              thumbnail, status, is_featured, created_at, updated_at)
             values
             (:builder_id, :zone_id, :name, :slug, :city, :area, :address, :latitude, :longitude, :description, :launch_date,
              :thumbnail, :status, :is_featured, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->projectParams($thumbnail));
        Response::redirect('/admin/real-estate#projects');
    }

    public function updateProject(int $id): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $current = $this->find('re_projects', $id);
        if (!$current) {
            Response::redirect('/admin/real-estate#projects');
        }
        $thumbnail = Upload::image('thumbnail', 'real-estate/projects') ?: ($current['thumbnail'] ?? null);
        $params = $this->projectParams($thumbnail);
        $params['id'] = $id;
        Database::connection()->prepare(
            'update re_projects set builder_id = :builder_id, zone_id = :zone_id, name = :name, slug = :slug,
                city = :city, area = :area, address = :address, latitude = :latitude, longitude = :longitude,
                description = :description, launch_date = :launch_date, thumbnail = :thumbnail,
                status = :status, is_featured = :is_featured, updated_at = CURRENT_TIMESTAMP
             where id = :id' . Auth::zoneWhere('re_projects')
        )->execute(Auth::zoneParams($params));
        Response::redirect('/admin/real-estate#projects');
    }

    public function storeProjectUnit(): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $floorPlan = Upload::image('floor_plan_image', 'real-estate/floor-plans');
        $stmt = Database::connection()->prepare(
            'insert into re_project_units
             (project_id, name, price_from, area, bedrooms, bathrooms, floor_plan_image, status, sort_order, created_at, updated_at)
             values
             (:project_id, :name, :price_from, :area, :bedrooms, :bathrooms, :floor_plan_image, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->projectUnitParams($floorPlan));
        Response::redirect('/admin/real-estate#project-units');
    }

    public function updateProjectUnit(int $id): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $current = $this->find('re_project_units', $id);
        if (!$current) {
            Response::redirect('/admin/real-estate#project-units');
        }
        $floorPlan = Upload::image('floor_plan_image', 'real-estate/floor-plans') ?: ($current['floor_plan_image'] ?? null);
        $params = $this->projectUnitParams($floorPlan);
        $params['id'] = $id;
        Database::connection()->prepare(
            'update re_project_units set project_id = :project_id, name = :name,
                price_from = :price_from, area = :area, bedrooms = :bedrooms, bathrooms = :bathrooms,
                floor_plan_image = :floor_plan_image, status = :status, sort_order = :sort_order,
                updated_at = CURRENT_TIMESTAMP
             where id = :id
               and exists (
                   select 1 from re_projects
                   where re_projects.id = :project_id' . Auth::zoneWhere('re_projects') . '
               )'
        )->execute(Auth::zoneParams($params));
        Response::redirect('/admin/real-estate#project-units');
    }

    public function inquiryStatus(int $id): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $status = trim((string) Request::input('status', 'open'));
        $reply = trim((string) Request::input('agent_reply'));
        $replySql = $reply === '' ? '' : ', agent_reply = :agent_reply, replied_at = CURRENT_TIMESTAMP';
        $params = [
            'id' => $id,
            'status' => $status,
        ];
        if ($reply !== '') {
            $params['agent_reply'] = $reply;
        }
        Database::connection()->prepare(
            'update re_inquiries set status = :status' . $replySql . ', updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute($params);
        Response::redirect('/admin/real-estate#inquiries');
    }

    public function visitStatus(int $id): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $status = trim((string) Request::input('status', 'pending'));
        $timeSql = match ($status) {
            'confirmed' => ', confirmed_at = CURRENT_TIMESTAMP',
            'completed' => ', completed_at = CURRENT_TIMESTAMP',
            'cancelled', 'rejected' => ', cancelled_at = CURRENT_TIMESTAMP',
            default => '',
        };
        Database::connection()->prepare(
            'update re_site_visits set status = :status, admin_note = :admin_note' . $timeSql . ', updated_at = CURRENT_TIMESTAMP where id = :id'
        )->execute([
            'id' => $id,
            'status' => $status,
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ]);
        Response::redirect('/admin/real-estate#visits');
    }

    public function complaintStatus(int $id): void
    {
        Auth::requireAdmin();
        RealEstateSchema::ensure();
        $status = trim((string) Request::input('status', 'pending'));
        if (!in_array($status, ['pending', 'reviewing', 'resolved', 'dismissed'], true)) {
            $status = 'pending';
        }
        $stmt = Database::connection()->prepare(
            'update re_complaints set status = :status, admin_note = :admin_note, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ]);
        Response::redirect('/admin/real-estate#complaints');
    }

    private function propertyParams(?string $thumbnail): array
    {
        $isVerified = isset($_POST['is_verified']) ? 1 : 0;
        return [
            'agent_id' => (int) Request::input('agent_id', 0) ?: null,
            'zone_id' => $this->zoneId(),
            'title' => trim((string) Request::input('title')),
            'slug' => $this->slug(trim((string) Request::input('title'))),
            'listing_purpose' => trim((string) Request::input('listing_purpose', 'sell')),
            'property_category' => trim((string) Request::input('property_category', 'apartment')),
            'price' => max(0, (float) Request::input('price', 0)),
            'price_unit' => trim((string) Request::input('price_unit')) ?: null,
            'maintenance_charge' => max(0, (float) Request::input('maintenance_charge', 0)),
            'deposit_amount' => max(0, (float) Request::input('deposit_amount', 0)),
            'city' => trim((string) Request::input('city', 'Lucknow')),
            'area' => trim((string) Request::input('area')) ?: null,
            'address' => trim((string) Request::input('address')),
            'latitude' => trim((string) Request::input('latitude')) === '' ? null : (float) Request::input('latitude'),
            'longitude' => trim((string) Request::input('longitude')) === '' ? null : (float) Request::input('longitude'),
            'description' => trim((string) Request::input('description')) ?: null,
            'bedrooms' => max(0, (int) Request::input('bedrooms', 0)),
            'bathrooms' => max(0, (int) Request::input('bathrooms', 0)),
            'balconies' => max(0, (int) Request::input('balconies', 0)),
            'parking' => max(0, (int) Request::input('parking', 0)),
            'built_up_area' => max(0, (float) Request::input('built_up_area', 0)),
            'carpet_area' => max(0, (float) Request::input('carpet_area', 0)),
            'plot_area' => max(0, (float) Request::input('plot_area', 0)),
            'area_unit' => trim((string) Request::input('area_unit', 'sq ft')),
            'furnishing' => trim((string) Request::input('furnishing')) ?: null,
            'ownership_type' => trim((string) Request::input('ownership_type')) ?: null,
            'property_age' => trim((string) Request::input('property_age')) ?: null,
            'contact_preference' => trim((string) Request::input('contact_preference', 'inquiry')),
            'thumbnail' => $thumbnail,
            'status' => trim((string) Request::input('status', 'approved')),
            'availability_status' => trim((string) Request::input('availability_status', 'available')),
            'is_verified' => $isVerified,
            'verified_at' => $isVerified === 1 ? date('Y-m-d H:i:s') : null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'rejection_reason' => trim((string) Request::input('rejection_reason')) ?: null,
        ];
    }

    private function projectParams(?string $thumbnail): array
    {
        return [
            'builder_id' => (int) Request::input('builder_id', 0) ?: null,
            'zone_id' => $this->zoneId(),
            'name' => trim((string) Request::input('name')),
            'slug' => $this->slug(trim((string) Request::input('name'))),
            'city' => trim((string) Request::input('city', 'Lucknow')),
            'area' => trim((string) Request::input('area')) ?: null,
            'address' => trim((string) Request::input('address')) ?: null,
            'latitude' => trim((string) Request::input('latitude')) === '' ? null : (float) Request::input('latitude'),
            'longitude' => trim((string) Request::input('longitude')) === '' ? null : (float) Request::input('longitude'),
            'description' => trim((string) Request::input('description')) ?: null,
            'launch_date' => trim((string) Request::input('launch_date')) ?: null,
            'thumbnail' => $thumbnail,
            'status' => trim((string) Request::input('status', 'approved')),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ];
    }

    private function projectUnitParams(?string $floorPlan): array
    {
        return [
            'project_id' => max(1, (int) Request::input('project_id', 0)),
            'name' => trim((string) Request::input('name')),
            'price_from' => max(0, (float) Request::input('price_from', 0)),
            'area' => max(0, (float) Request::input('area', 0)),
            'bedrooms' => max(0, (int) Request::input('bedrooms', 0)),
            'bathrooms' => max(0, (int) Request::input('bathrooms', 0)),
            'floor_plan_image' => $floorPlan,
            'status' => (int) Request::input('status', 1) === 1 ? 1 : 0,
            'sort_order' => (int) Request::input('sort_order', 0),
        ];
    }

    private function amenityParams(): array
    {
        return [
            'name' => trim((string) Request::input('name')),
            'icon' => trim((string) Request::input('icon')) ?: null,
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int) Request::input('sort_order', 0),
        ];
    }

    private function syncPropertyImages(int $propertyId, array $gallery, bool $replace = false): void
    {
        $db = Database::connection();
        if ($replace) {
            $db->prepare('delete from re_property_images where property_id = :property_id')->execute(['property_id' => $propertyId]);
        }
        $stmt = $db->prepare(
            'insert into re_property_images (property_id, image_url, sort_order, created_at) values (:property_id, :image_url, :sort_order, CURRENT_TIMESTAMP)'
        );
        foreach ($gallery as $index => $path) {
            $stmt->execute([
                'property_id' => $propertyId,
                'image_url' => $path,
                'sort_order' => $index,
            ]);
        }
    }

    private function syncPropertyAmenities(int $propertyId, mixed $amenityIds): void
    {
        $ids = is_array($amenityIds) ? array_map('intval', $amenityIds) : [];
        $db = Database::connection();
        $db->prepare('delete from re_property_amenities where property_id = :property_id')->execute(['property_id' => $propertyId]);
        if ($ids === []) {
            return;
        }
        $stmt = $db->prepare(
            'insert into re_property_amenities (property_id, amenity_id, created_at) values (:property_id, :amenity_id, CURRENT_TIMESTAMP)'
        );
        foreach (array_unique($ids) as $amenityId) {
            if ($amenityId > 0) {
                $stmt->execute(['property_id' => $propertyId, 'amenity_id' => $amenityId]);
            }
        }
    }

    private function zoneId(): ?int
    {
        if (Auth::isZoneScoped()) {
            return (int) ($_SESSION['admin_zone_id'] ?? 0) ?: null;
        }
        return (int) Request::input('zone_id', 0) ?: null;
    }

    private function zones(): array
    {
        if (Auth::isZoneScoped()) {
            $zoneId = (int) ($_SESSION['admin_zone_id'] ?? 0);
            if ($zoneId <= 0) {
                return [];
            }
            $stmt = Database::connection()->prepare('select * from zones where id = :id');
            $stmt->execute(['id' => $zoneId]);
            $row = $stmt->fetch();
            return $row ? [$row] : [];
        }
        return ZoneSchema::active();
    }

    private function find(string $table, int $id): ?array
    {
        $stmt = Database::connection()->prepare('select * from ' . $table . ' where id = :id limit 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '', '-'));
        return $slug === '' ? 'property-' . date('YmdHis') : $slug;
    }
}
