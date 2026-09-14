<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\Database;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\Upload;
use App\Support\View;

final class CategoryController
{
    public function __construct(private readonly string $moduleKey = 'ecommerce', private readonly string $basePath = '/admin/ecommerce/categories')
    {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('select * from categories where module_key = :module_key order by sort_order asc, id desc');
        $stmt->execute(['module_key' => $this->moduleKey]);
        $categories = $stmt->fetchAll();
        View::render('admin/categories', ['title' => 'Categories', 'categories' => $categories, 'basePath' => $this->basePath]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Response::redirect($this->basePath);
        }

        $image = Upload::image('image', 'categories');
        $stmt = Database::connection()->prepare(
            'insert into categories (module_key, name, slug, image, shipping_cost, status, sort_order, created_at, updated_at) values (:module_key, :name, :slug, :image, :shipping_cost, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'module_key' => $this->moduleKey,
            'name' => $name,
            'slug' => $this->slug($name),
            'image' => $image,
            'shipping_cost' => max(0, (float) ($_POST['shipping_cost'] ?? 0)),
            'status' => (int) ($_POST['status'] ?? 1),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);

        Response::redirect($this->basePath);
    }

    public function delete(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $products = $db->prepare('update products set category_id = null, subcategory_id = null where category_id = :id and module_key = :module_key');
            $products->execute(['id' => $id, 'module_key' => $this->moduleKey]);
            $children = $db->prepare('delete from subcategories where category_id = :id and module_key = :module_key');
            $children->execute(['id' => $id, 'module_key' => $this->moduleKey]);
            $stmt = $db->prepare('delete from categories where id = :id and module_key = :module_key');
            $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }
        Response::redirect($this->basePath);
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('select * from categories where id = :id and module_key = :module_key');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $category = $stmt->fetch();
        if (!$category) {
            Response::redirect($this->basePath);
        }

        View::render('admin/category_edit', [
            'title' => 'Edit Category',
            'category' => $category,
            'basePath' => $this->basePath,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Response::redirect($this->basePath . '/' . $id . '/edit');
        }

        $current = Database::connection()->prepare('select * from categories where id = :id and module_key = :module_key');
        $current->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $category = $current->fetch();
        if (!$category) {
            Response::redirect($this->basePath);
        }

        $image = Upload::image('image', 'categories') ?? $category['image'];
        $stmt = Database::connection()->prepare(
            'update categories set name = :name, slug = :slug, image = :image, shipping_cost = :shipping_cost, status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'slug' => $this->slug($name),
            'image' => $image,
            'shipping_cost' => max(0, (float) ($_POST['shipping_cost'] ?? 0)),
            'status' => (int) ($_POST['status'] ?? 1),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);

        Response::redirect($this->basePath);
    }

    private function slug(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
    }
}
