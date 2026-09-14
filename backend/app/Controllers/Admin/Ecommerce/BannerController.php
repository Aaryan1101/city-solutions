<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Ecommerce;

use App\Support\Auth;
use App\Support\Database;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\Upload;
use App\Support\View;

final class BannerController
{
    public function __construct(
        private readonly string $moduleKey = 'ecommerce',
        private readonly string $basePath = '/admin/ecommerce/banners',
        private readonly string $moduleLabel = 'Banners'
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('select * from banners where module_key = :module_key order by sort_order asc, id desc');
        $stmt->execute(['module_key' => $this->moduleKey]);
        View::render('admin/banners', ['title' => $this->moduleLabel, 'banners' => $stmt->fetchAll(), 'basePath' => $this->basePath]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $title = trim($_POST['title'] ?? '');
        $image = Upload::image('image', 'banners');

        $stmt = Database::connection()->prepare(
            'insert into banners (module_key, title, image, link_type, link_value, status, sort_order, created_at, updated_at) values (:module_key, :title, :image, :link_type, :link_value, :status, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'module_key' => $this->moduleKey,
            'title' => $title,
            'image' => $image,
            'link_type' => trim($_POST['link_type'] ?? 'none'),
            'link_value' => trim($_POST['link_value'] ?? ''),
            'status' => (int) ($_POST['status'] ?? 1),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);

        Response::redirect($this->basePath);
    }

    public function delete(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('delete from banners where id = :id and module_key = :module_key');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        Response::redirect($this->basePath);
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('select * from banners where id = :id and module_key = :module_key');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $banner = $stmt->fetch();
        if (!$banner) {
            Response::redirect($this->basePath);
        }

        View::render('admin/banner_edit', [
            'title' => 'Edit Banner',
            'banner' => $banner,
            'basePath' => $this->basePath,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $current = Database::connection()->prepare('select * from banners where id = :id and module_key = :module_key');
        $current->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $banner = $current->fetch();
        if (!$banner) {
            Response::redirect($this->basePath);
        }

        $image = Upload::image('image', 'banners') ?? $banner['image'];
        $stmt = Database::connection()->prepare(
            'update banners set title = :title, image = :image, link_type = :link_type, link_value = :link_value, status = :status, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP where id = :id and module_key = :module_key'
        );
        $stmt->execute([
            'id' => $id,
            'module_key' => $this->moduleKey,
            'title' => trim($_POST['title'] ?? ''),
            'image' => $image,
            'link_type' => trim($_POST['link_type'] ?? 'none'),
            'link_value' => trim($_POST['link_value'] ?? ''),
            'status' => (int) ($_POST['status'] ?? 1),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);

        Response::redirect($this->basePath);
    }
}
