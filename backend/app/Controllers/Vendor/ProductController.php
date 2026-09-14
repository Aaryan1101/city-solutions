<?php

declare(strict_types=1);

namespace App\Controllers\Vendor;

use App\Support\Database;
use App\Support\BrandSchema;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\Upload;
use App\Support\VendorAuth;
use App\Support\VendorSchema;
use App\Support\View;

final class ProductController
{
    public function index(): void
    {
        VendorAuth::requireVendor();
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $db = Database::connection();
        $vendorId = VendorAuth::id();
        $moduleKey = VendorAuth::moduleKey();
        $stmt = $db->prepare('select products.*, categories.name as category_name, subcategories.name as subcategory_name, brands.name as brand_name from products left join categories on categories.id = products.category_id left join subcategories on subcategories.id = products.subcategory_id and subcategories.module_key = products.module_key left join brands on brands.id = products.brand_id where products.vendor_id = :vendor_id and products.module_key = :module_key order by products.id desc');
        $stmt->execute(['vendor_id' => $vendorId, 'module_key' => $moduleKey]);
        $products = $stmt->fetchAll();
        $categoriesStmt = $db->prepare('select * from categories where status = 1 and module_key = :module_key order by name asc');
        $categoriesStmt->execute(['module_key' => $moduleKey]);
        $categories = $categoriesStmt->fetchAll();
        $subcategories = $this->subcategories($moduleKey);
        $brandsStmt = $db->prepare('select * from brands where status = 1 and module_key = :module_key order by name asc');
        $brandsStmt->execute(['module_key' => $moduleKey]);
        $brands = $brandsStmt->fetchAll();

        View::render('vendor/products', [
            'title' => 'Vendor Products',
            'products' => $products,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
        ]);
    }

    public function store(): void
    {
        VendorAuth::requireVendor();
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Response::redirect('/vendor/products');
        }

        $moduleKey = VendorAuth::moduleKey();
        $thumbnail = Upload::image('thumbnail', 'products');
        $stmt = Database::connection()->prepare(
            'insert into products (module_key, vendor_id, brand_id, category_id, subcategory_id, name, slug, description, unit, price, discount_price, stock, sku, thumbnail, status, is_featured, created_at, updated_at)
             values (:module_key, :vendor_id, :brand_id, :category_id, :subcategory_id, :name, :slug, :description, :unit, :price, :discount_price, :stock, :sku, :thumbnail, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'module_key' => $moduleKey,
            'vendor_id' => VendorAuth::id(),
            'brand_id' => $this->brandId($moduleKey),
            'category_id' => $categoryId = $this->categoryId($moduleKey),
            'subcategory_id' => $this->subcategoryId($moduleKey, $categoryId),
            'name' => $name,
            'slug' => $this->slug($name),
            'description' => trim($_POST['description'] ?? ''),
            'unit' => trim($_POST['unit'] ?? 'piece'),
            'price' => (float) ($_POST['price'] ?? 0),
            'discount_price' => ($_POST['discount_price'] ?? '') === '' ? null : (float) $_POST['discount_price'],
            'stock' => (int) ($_POST['stock'] ?? 0),
            'sku' => $this->sku($name, trim($_POST['sku'] ?? '')),
            'thumbnail' => $thumbnail,
        ]);
        $productId = (int) Database::connection()->lastInsertId();
        $this->saveVariants($productId);
        $this->saveImages($productId);

        Response::redirect('/vendor/products');
    }

    public function edit(int $id): void
    {
        VendorAuth::requireVendor();
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $db = Database::connection();
        $product = $this->findVendorProduct($id);
        if (!$product) {
            Response::redirect('/vendor/products');
        }

        $moduleKey = VendorAuth::moduleKey();
        $categoriesStmt = $db->prepare('select * from categories where status = 1 and module_key = :module_key order by name asc');
        $categoriesStmt->execute(['module_key' => $moduleKey]);
        $categories = $categoriesStmt->fetchAll();
        $subcategories = $this->subcategories($moduleKey);
        $brandsStmt = $db->prepare('select * from brands where status = 1 and module_key = :module_key order by name asc');
        $brandsStmt->execute(['module_key' => $moduleKey]);
        $brands = $brandsStmt->fetchAll();
        $variants = $db->prepare('select * from product_variants where product_id = :id order by id asc');
        $variants->execute(['id' => $id]);
        $images = $db->prepare('select * from product_images where product_id = :id order by sort_order asc, id asc');
        $images->execute(['id' => $id]);
        View::render('vendor/product_edit', [
            'title' => 'Edit Product',
            'product' => $product,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
            'variantsText' => $this->variantsText($variants->fetchAll()),
            'images' => $images->fetchAll(),
        ]);
    }

    public function update(int $id): void
    {
        VendorAuth::requireVendor();
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Response::redirect('/vendor/products/' . $id . '/edit');
        }

        $db = Database::connection();
        $product = $this->findVendorProduct($id);
        if (!$product) {
            Response::redirect('/vendor/products');
        }

        $moduleKey = VendorAuth::moduleKey();
        $thumbnail = Upload::image('thumbnail', 'products') ?? $product['thumbnail'];
        $stmt = $db->prepare(
            'update products set module_key = :module_key, brand_id = :brand_id, category_id = :category_id, subcategory_id = :subcategory_id, name = :name, slug = :slug, description = :description, unit = :unit, price = :price, discount_price = :discount_price, stock = :stock, sku = :sku, thumbnail = :thumbnail, status = 0, is_featured = 0, updated_at = CURRENT_TIMESTAMP where id = :id and vendor_id = :vendor_id'
        );
        $stmt->execute([
            'id' => $id,
            'module_key' => $moduleKey,
            'vendor_id' => VendorAuth::id(),
            'brand_id' => $this->brandId($moduleKey),
            'category_id' => $categoryId = $this->categoryId($moduleKey),
            'subcategory_id' => $this->subcategoryId($moduleKey, $categoryId),
            'name' => $name,
            'slug' => $this->slug($name),
            'description' => trim($_POST['description'] ?? ''),
            'unit' => trim($_POST['unit'] ?? 'piece'),
            'price' => (float) ($_POST['price'] ?? 0),
            'discount_price' => ($_POST['discount_price'] ?? '') === '' ? null : (float) $_POST['discount_price'],
            'stock' => (int) ($_POST['stock'] ?? 0),
            'sku' => $this->sku($name, trim($_POST['sku'] ?? ''), $product['sku'] ?? ''),
            'thumbnail' => $thumbnail,
        ]);
        $this->saveVariants($id);
        $this->saveImages($id);

        Response::redirect('/vendor/products');
    }

    public function delete(int $id): void
    {
        VendorAuth::requireVendor();
        VendorSchema::ensure();
        $stmt = Database::connection()->prepare('delete from products where id = :id and vendor_id = :vendor_id and module_key = :module_key');
        $stmt->execute(['id' => $id, 'vendor_id' => VendorAuth::id(), 'module_key' => VendorAuth::moduleKey()]);
        Response::redirect('/vendor/products');
    }

    private function slug(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
    }

    private function categoryId(string $moduleKey): int
    {
        $id = (int) ($_POST['category_id'] ?? 0);
        $stmt = Database::connection()->prepare('select id from categories where id = :id and module_key = :module_key and status = 1 limit 1');
        $stmt->execute(['id' => $id, 'module_key' => $moduleKey]);
        return $stmt->fetch() ? $id : 0;
    }

    private function subcategories(string $moduleKey): array
    {
        $stmt = Database::connection()->prepare('select id, category_id, name from subcategories where module_key = :module_key and status = 1 order by sort_order asc, name asc');
        $stmt->execute(['module_key' => $moduleKey]);
        return $stmt->fetchAll();
    }

    private function subcategoryId(string $moduleKey, int $categoryId): ?int
    {
        $id = (int) ($_POST['subcategory_id'] ?? 0);
        if ($id <= 0 || $categoryId <= 0) {
            return null;
        }
        $stmt = Database::connection()->prepare('select id from subcategories where id = :id and category_id = :category_id and module_key = :module_key and status = 1 limit 1');
        $stmt->execute(['id' => $id, 'category_id' => $categoryId, 'module_key' => $moduleKey]);
        return $stmt->fetch() ? $id : null;
    }

    private function brandId(string $moduleKey): ?int
    {
        $id = (int) ($_POST['brand_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $stmt = Database::connection()->prepare('select id from brands where id = :id and module_key = :module_key and status = 1 limit 1');
        $stmt->execute(['id' => $id, 'module_key' => $moduleKey]);
        return $stmt->fetch() ? $id : null;
    }

    private function findVendorProduct(int $id): ?array
    {
        $stmt = Database::connection()->prepare('select * from products where id = :id and vendor_id = :vendor_id and module_key = :module_key limit 1');
        $stmt->execute(['id' => $id, 'vendor_id' => VendorAuth::id(), 'module_key' => VendorAuth::moduleKey()]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    private function sku(string $name, string $input, string $current = ''): string
    {
        if ($input !== '') {
            return strtoupper(preg_replace('/[^a-zA-Z0-9._-]/', '-', $input));
        }
        if ($current !== '') {
            return $current;
        }

        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 3)) ?: 'PRD';
        return 'VEN-' . VendorAuth::id() . '-' . $prefix . '-' . date('ymdHis') . '-' . random_int(100, 999);
    }

    private function saveImages(int $productId): void
    {
        $paths = Upload::images('images', 'products');
        if ($paths === []) {
            return;
        }
        $stmt = Database::connection()->prepare('insert into product_images (product_id, image, sort_order, created_at) values (:product_id, :image, :sort_order, CURRENT_TIMESTAMP)');
        foreach ($paths as $index => $path) {
            $stmt->execute(['product_id' => $productId, 'image' => $path, 'sort_order' => $index]);
        }
    }

    private function saveVariants(int $productId): void
    {
        $db = Database::connection();
        $db->prepare('delete from product_variants where product_id = :product_id')->execute(['product_id' => $productId]);
        $lines = preg_split('/\r\n|\r|\n/', trim($_POST['variants'] ?? '')) ?: [];
        $stmt = $db->prepare(
            'insert into product_variants (product_id, name, unit, price, discount_price, stock, sku, status, created_at, updated_at)
             values (:product_id, :name, :unit, :price, :discount_price, :stock, :sku, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (($parts[0] ?? '') === '') {
                continue;
            }
            $stmt->execute([
                'product_id' => $productId,
                'name' => $parts[0],
                'unit' => $parts[1] ?? 'piece',
                'price' => (float) ($parts[2] ?? 0),
                'discount_price' => ($parts[3] ?? '') === '' ? null : (float) $parts[3],
                'stock' => (int) ($parts[4] ?? 0),
                'sku' => $parts[5] ?? null,
            ]);
        }
    }

    private function variantsText(array $variants): string
    {
        return implode("\n", array_map(static fn (array $variant): string => implode('|', [
            $variant['name'],
            $variant['unit'],
            $variant['price'],
            $variant['discount_price'] ?? '',
            $variant['stock'],
            $variant['sku'] ?? '',
        ]), $variants));
    }
}
