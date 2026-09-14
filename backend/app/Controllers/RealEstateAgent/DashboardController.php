<?php

declare(strict_types=1);

namespace App\Controllers\RealEstateAgent;

use App\Support\Database;
use App\Support\RealEstateAgentAuth;
use App\Support\RealEstateSchema;
use App\Support\Response;
use App\Support\Request;
use App\Support\Upload;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        RealEstateAgentAuth::requireAgent();
        RealEstateSchema::ensure();
        $db = Database::connection();
        $agentId = RealEstateAgentAuth::id();
        $agent = $this->agent();

        $properties = $db->prepare('select * from re_properties where agent_id = :agent_id order by id desc');
        $properties->execute(['agent_id' => $agentId]);

        $projects = $db->prepare('select * from re_projects where builder_id = :agent_id order by id desc');
        $projects->execute(['agent_id' => $agentId]);

        $units = $db->prepare(
            'select re_project_units.*, re_projects.name as project_name
             from re_project_units
             join re_projects on re_projects.id = re_project_units.project_id
             where re_projects.builder_id = :agent_id
             order by re_project_units.sort_order asc, re_project_units.id desc'
        );
        $units->execute(['agent_id' => $agentId]);

        $inquiries = $db->prepare('select re_inquiries.*, re_properties.title as property_title from re_inquiries left join re_properties on re_properties.id = re_inquiries.property_id where re_inquiries.agent_id = :agent_id order by re_inquiries.id desc limit 100');
        $inquiries->execute(['agent_id' => $agentId]);

        $visits = $db->prepare('select re_site_visits.*, re_properties.title as property_title from re_site_visits left join re_properties on re_properties.id = re_site_visits.property_id where re_site_visits.agent_id = :agent_id order by re_site_visits.id desc limit 100');
        $visits->execute(['agent_id' => $agentId]);

        View::render('real_estate_agent/dashboard', [
            'title' => 'Real Estate Agent',
            'agent' => $agent,
            'properties' => $properties->fetchAll(),
            'projects' => $projects->fetchAll(),
            'units' => $units->fetchAll(),
            'inquiries' => $inquiries->fetchAll(),
            'visits' => $visits->fetchAll(),
        ]);
    }

    public function updateProfile(): void
    {
        RealEstateAgentAuth::requireAgent();
        RealEstateSchema::ensure();
        $agent = $this->agent();
        $password = trim((string) Request::input('password'));
        $passwordSql = $password === '' ? '' : ', password = :password';
        $params = [
            'id' => RealEstateAgentAuth::id(),
            'business_name' => trim((string) Request::input('business_name')),
            'owner_name' => trim((string) Request::input('owner_name')),
            'phone' => trim((string) Request::input('phone')) ?: ($agent['phone'] ?? null),
            'email' => trim((string) Request::input('email')) ?: null,
            'bio' => trim((string) Request::input('bio')) ?: null,
        ];
        if ($password !== '') {
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        Database::connection()->prepare(
            'update re_agents set business_name = :business_name, owner_name = :owner_name, phone = :phone,
                email = :email, bio = :bio' . $passwordSql . ', updated_at = CURRENT_TIMESTAMP
             where id = :id'
        )->execute($params);
        $_SESSION['real_estate_agent_name'] = $params['business_name'];
        Response::redirect('/real-estate-agent#profile');
    }

    public function storeProperty(): void
    {
        RealEstateAgentAuth::requireAgent();
        RealEstateSchema::ensure();
        $agent = $this->agent();
        $thumbnail = Upload::image('thumbnail', 'real-estate/properties');
        $stmt = Database::connection()->prepare(
            'insert into re_properties
             (agent_id, zone_id, title, slug, listing_purpose, property_category, price, price_unit, maintenance_charge,
              deposit_amount, city, area, address, latitude, longitude, description, bedrooms, bathrooms, balconies,
              parking, built_up_area, carpet_area, plot_area, area_unit, furnishing, ownership_type, property_age,
              contact_preference, thumbnail, status, availability_status, is_verified, verified_at, is_featured,
              rejection_reason, created_at, updated_at)
             values
             (:agent_id, :zone_id, :title, :slug, :listing_purpose, :property_category, :price, null, 0,
              0, :city, :area, :address, :latitude, :longitude, :description, :bedrooms, :bathrooms, 0,
              :parking, :built_up_area, 0, 0, \'sq ft\', :furnishing, null, null,
              \'inquiry\', :thumbnail, \'pending\', \'available\', 0, null, 0,
              null, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'agent_id' => RealEstateAgentAuth::id(),
            'zone_id' => $agent['zone_id'] ?? null,
            'title' => trim((string) Request::input('title')),
            'slug' => $this->slug(trim((string) Request::input('title'))),
            'listing_purpose' => trim((string) Request::input('listing_purpose', 'sell')),
            'property_category' => trim((string) Request::input('property_category', 'apartment')),
            'price' => max(0, (float) Request::input('price', 0)),
            'city' => trim((string) Request::input('city', 'Lucknow')),
            'area' => trim((string) Request::input('area')) ?: null,
            'address' => trim((string) Request::input('address')),
            'latitude' => trim((string) Request::input('latitude')) === '' ? null : (float) Request::input('latitude'),
            'longitude' => trim((string) Request::input('longitude')) === '' ? null : (float) Request::input('longitude'),
            'description' => trim((string) Request::input('description')) ?: null,
            'bedrooms' => max(0, (int) Request::input('bedrooms', 0)),
            'bathrooms' => max(0, (int) Request::input('bathrooms', 0)),
            'parking' => max(0, (int) Request::input('parking', 0)),
            'built_up_area' => max(0, (float) Request::input('built_up_area', 0)),
            'furnishing' => trim((string) Request::input('furnishing')) ?: null,
            'thumbnail' => $thumbnail,
        ]);
        Response::redirect('/real-estate-agent#properties');
    }

    public function updateProperty(int $id): void
    {
        RealEstateAgentAuth::requireAgent();
        $status = trim((string) Request::input('availability_status', 'available'));
        Database::connection()->prepare(
            'update re_properties set availability_status = :availability_status, updated_at = CURRENT_TIMESTAMP where id = :id and agent_id = :agent_id'
        )->execute(['id' => $id, 'agent_id' => RealEstateAgentAuth::id(), 'availability_status' => $status]);
        Response::redirect('/real-estate-agent#properties');
    }

    public function storeProject(): void
    {
        RealEstateAgentAuth::requireAgent();
        RealEstateSchema::ensure();
        $agent = $this->agent();
        $thumbnail = Upload::image('thumbnail', 'real-estate/projects');
        Database::connection()->prepare(
            'insert into re_projects
             (builder_id, zone_id, name, slug, city, area, address, latitude, longitude, description, launch_date, thumbnail, status, is_featured, created_at, updated_at)
             values
             (:builder_id, :zone_id, :name, :slug, :city, :area, :address, :latitude, :longitude, :description, :launch_date, :thumbnail, \'pending\', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        )->execute([
            'builder_id' => RealEstateAgentAuth::id(),
            'zone_id' => $agent['zone_id'] ?? null,
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
        ]);
        Response::redirect('/real-estate-agent#projects');
    }

    public function storeProjectUnit(): void
    {
        RealEstateAgentAuth::requireAgent();
        RealEstateSchema::ensure();
        $projectId = $this->ownedProjectId();
        if ($projectId <= 0) {
            Response::redirect('/real-estate-agent#project-units');
        }
        $floorPlan = Upload::image('floor_plan_image', 'real-estate/floor-plans');
        Database::connection()->prepare(
            'insert into re_project_units
             (project_id, name, price_from, area, bedrooms, bathrooms, floor_plan_image, status, sort_order, created_at, updated_at)
             values
             (:project_id, :name, :price_from, :area, :bedrooms, :bathrooms, :floor_plan_image, 1, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        )->execute([
            'project_id' => $projectId,
            'name' => trim((string) Request::input('name')),
            'price_from' => max(0, (float) Request::input('price_from', 0)),
            'area' => max(0, (float) Request::input('area', 0)),
            'bedrooms' => max(0, (int) Request::input('bedrooms', 0)),
            'bathrooms' => max(0, (int) Request::input('bathrooms', 0)),
            'floor_plan_image' => $floorPlan,
            'sort_order' => (int) Request::input('sort_order', 0),
        ]);
        Response::redirect('/real-estate-agent#project-units');
    }

    public function inquiryStatus(int $id): void
    {
        RealEstateAgentAuth::requireAgent();
        Database::connection()->prepare(
            'update re_inquiries set status = :status, agent_reply = :agent_reply, replied_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             where id = :id and agent_id = :agent_id'
        )->execute([
            'id' => $id,
            'agent_id' => RealEstateAgentAuth::id(),
            'status' => trim((string) Request::input('status', 'contacted')),
            'agent_reply' => trim((string) Request::input('agent_reply')) ?: null,
        ]);
        Response::redirect('/real-estate-agent#inquiries');
    }

    public function visitStatus(int $id): void
    {
        RealEstateAgentAuth::requireAgent();
        $status = trim((string) Request::input('status', 'confirmed'));
        $timeSql = match ($status) {
            'confirmed' => ', confirmed_at = CURRENT_TIMESTAMP',
            'completed' => ', completed_at = CURRENT_TIMESTAMP',
            'cancelled', 'rejected' => ', cancelled_at = CURRENT_TIMESTAMP',
            default => '',
        };
        Database::connection()->prepare(
            'update re_site_visits set status = :status, admin_note = :admin_note' . $timeSql . ', updated_at = CURRENT_TIMESTAMP
             where id = :id and agent_id = :agent_id'
        )->execute([
            'id' => $id,
            'agent_id' => RealEstateAgentAuth::id(),
            'status' => $status,
            'admin_note' => trim((string) Request::input('admin_note')) ?: null,
        ]);
        Response::redirect('/real-estate-agent#visits');
    }

    private function agent(): array
    {
        $stmt = Database::connection()->prepare('select * from re_agents where id = :id limit 1');
        $stmt->execute(['id' => RealEstateAgentAuth::id()]);
        $agent = $stmt->fetch();
        if (!$agent) {
            Response::redirect('/real-estate-agent/login');
        }
        return $agent;
    }

    private function ownedProjectId(): int
    {
        $projectId = (int) Request::input('project_id', 0);
        $stmt = Database::connection()->prepare('select id from re_projects where id = :id and builder_id = :agent_id limit 1');
        $stmt->execute(['id' => $projectId, 'agent_id' => RealEstateAgentAuth::id()]);
        return $stmt->fetch() ? $projectId : 0;
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '', '-'));
        return $slug === '' ? 'listing-' . date('YmdHis') : $slug;
    }
}
