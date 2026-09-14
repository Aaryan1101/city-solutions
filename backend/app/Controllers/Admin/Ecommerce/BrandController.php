<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\BrandSchema;
use App\Support\Database;
use App\Support\Response;
use App\Support\Upload;
use App\Support\View;

final class BrandController
{
    public function __construct(private readonly string $moduleKey = 'ecommerce', private readonly string $basePath = '/admin/ecommerce/brands')
    {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        \App\Support\ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('select * from brands where module_key = :module_key order by sort_order asc, id desc');
        $stmt->execute(['module_key' => $this->moduleKey]);
        $brands = $stmt->fetchAll();
        View::render('admin/brands', ['title' => 'Brands', 'brands' => $brands, 'basePath' => $this->basePath]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Response::redirect($this->basePath);
        }

        $image = Upload::image('image', 'brands');
        $stmt = Database::connection()->prepare(
            'insert into brands (module_key, name, slug, image, status, sort_order, created_at, updated_at) values (:module_key, :name, :slug, :image, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'module_key' => $this->moduleKey,
            'name' => $name,
            'slug' => $this->slug($name),
            'image' => $image,
            'status' => (int) ($_POST['status'] ?? 1),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);

        Response::redirect($this->basePath);
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        $stmt = Database::connection()->prepare('select * from brands where id = :id and module_key = :module_key');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $brand = $stmt->fetch();
        if (!$brand) {
            Response::redirect($this->basePath);
        }

        View::render('admin/brand_edit', [
            'title' => 'Edit Brand',
            'brand' => $brand,
            'basePath' => $this->basePath,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Response::redirect($this->basePath . '/' . $id . '/edit');
        }

        $current = Database::connection()->prepare('select * from brands where id = :id and module_key = :module_key');
        $current->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $brand = $current->fetch();
        if (!$brand) {
            Response::redirect($this->basePath);
        }

        $image = Upload::image('image', 'brands') ?? $brand['image'];
        $stmt = Database::connection()->prepare(
            'update brands set name = :name, slug = :slug, image = :image, status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'slug' => $this->slug($name),
            'image' => $image,
            'status' => (int) ($_POST['status'] ?? 1),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);

        Response::redirect($this->basePath);
    }

    public function delete(int $id): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        $stmt = Database::connection()->prepare('delete from brands where id = :id and module_key = :module_key');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        Response::redirect($this->basePath);
    }

    private function slug(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
    }
}
