<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\Upload;
use App\Support\View;

final class SubcategoryController
{
    public function __construct(
        private readonly string $moduleKey = 'mart',
        private readonly string $basePath = '/admin/subcategories',
        private readonly string $moduleLabel = 'Mart'
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $db = Database::connection();
        $categories = $this->categories();
        $stmt = $db->prepare(
            'select subcategories.*, categories.name as category_name,
                    (select count(*) from products where products.subcategory_id = subcategories.id) as products_count
             from subcategories
             join categories on categories.id = subcategories.category_id and categories.module_key = subcategories.module_key
             where subcategories.module_key = :module_key
             order by categories.sort_order asc, categories.name asc, subcategories.sort_order asc, subcategories.id desc'
        );
        $stmt->execute(['module_key' => $this->moduleKey]);
        View::render('admin/subcategories', [
            'title' => $this->moduleLabel . ' Subcategories',
            'moduleLabel' => $this->moduleLabel,
            'subcategories' => $stmt->fetchAll(),
            'categories' => $categories,
            'basePath' => $this->basePath,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $name = trim((string) ($_POST['name'] ?? ''));
        $categoryId = $this->categoryId();
        if ($name === '' || $categoryId === null) {
            Response::redirect($this->basePath);
        }

        $stmt = Database::connection()->prepare(
            'insert into subcategories (module_key, category_id, name, slug, image, status, sort_order, created_at, updated_at)
             values (:module_key, :category_id, :name, :slug, :image, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'module_key' => $this->moduleKey,
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $this->uniqueSlug($name, $categoryId),
            'image' => Upload::image('image', 'subcategories'),
            'status' => (int) ($_POST['status'] ?? 1),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        Response::redirect($this->basePath);
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $subcategory = $this->find($id);
        if ($subcategory === null) {
            Response::redirect($this->basePath);
        }
        View::render('admin/subcategory_edit', [
            'title' => 'Edit ' . $this->moduleLabel . ' Subcategory',
            'subcategory' => $subcategory,
            'categories' => $this->categories(),
            'basePath' => $this->basePath,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $subcategory = $this->find($id);
        $name = trim((string) ($_POST['name'] ?? ''));
        $categoryId = $this->categoryId();
        if ($subcategory === null || $name === '' || $categoryId === null) {
            Response::redirect($this->basePath);
        }

        $stmt = Database::connection()->prepare(
            'update subcategories set category_id = :category_id, name = :name, slug = :slug,
                    image = :image, status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP
             where id = :id and module_key = :module_key'
        );
        $stmt->execute([
            'id' => $id,
            'module_key' => $this->moduleKey,
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $this->uniqueSlug($name, $categoryId, $id),
            'image' => Upload::image('image', 'subcategories') ?? $subcategory['image'],
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
            $clear = $db->prepare(
                'update products set subcategory_id = null where subcategory_id = :id and module_key = :module_key'
            );
            $clear->execute(['id' => $id, 'module_key' => $this->moduleKey]);
            $delete = $db->prepare('delete from subcategories where id = :id and module_key = :module_key');
            $delete->execute(['id' => $id, 'module_key' => $this->moduleKey]);
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
        Response::redirect($this->basePath);
    }

    private function categories(): array
    {
        $stmt = Database::connection()->prepare(
            'select id, name, status from categories where module_key = :module_key order by sort_order asc, name asc'
        );
        $stmt->execute(['module_key' => $this->moduleKey]);
        return $stmt->fetchAll();
    }

    private function categoryId(): ?int
    {
        $id = (int) ($_POST['category_id'] ?? 0);
        $stmt = Database::connection()->prepare(
            'select id from categories where id = :id and module_key = :module_key limit 1'
        );
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        return $stmt->fetch() ? $id : null;
    }

    private function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'select * from subcategories where id = :id and module_key = :module_key limit 1'
        );
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function uniqueSlug(string $name, int $categoryId, int $ignoreId = 0): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
        $base = ($base === '' ? 'subcategory' : $base) . '-' . $categoryId;
        $slug = $base;
        $suffix = 2;
        do {
            $stmt = Database::connection()->prepare(
                'select id from subcategories where module_key = :module_key and slug = :slug and id != :ignore_id limit 1'
            );
            $stmt->execute(['module_key' => $this->moduleKey, 'slug' => $slug, 'ignore_id' => $ignoreId]);
            if (!$stmt->fetch()) {
                return $slug;
            }
            $slug = $base . '-' . $suffix++;
        } while (true);
    }
}
