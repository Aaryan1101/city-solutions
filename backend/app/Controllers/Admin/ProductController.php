<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\BrandSchema;
use App\Support\Database;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\Upload;
use App\Support\VendorSchema;
use App\Support\View;
use App\Support\ZoneSchema;

final class ProductController
{
    public function __construct(private readonly string $moduleKey = 'mart', private readonly string $basePath = '/admin/products')
    {
    }

    public function index(): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $db = Database::connection();
        $productsStmt = $db->prepare('select products.*, categories.name as category_name, subcategories.name as subcategory_name, brands.name as brand_name, vendors.shop_name as vendor_name, zones.name as zone_name from products left join categories on categories.id = products.category_id left join subcategories on subcategories.id = products.subcategory_id and subcategories.module_key = products.module_key left join brands on brands.id = products.brand_id left join vendors on vendors.id = products.vendor_id and vendors.module_key = products.module_key left join zones on zones.id = products.zone_id where products.module_key = :module_key' . Auth::zoneWhere('products') . ' order by products.id desc');
        $productsStmt->execute(Auth::zoneParams(['module_key' => $this->moduleKey]));
        $products = $productsStmt->fetchAll();
        $categoriesStmt = $db->prepare('select * from categories where status = 1 and module_key = :module_key order by name');
        $categoriesStmt->execute(['module_key' => $this->moduleKey]);
        $categories = $categoriesStmt->fetchAll();
        $subcategories = $this->subcategories();
        $brandsStmt = $db->prepare('select * from brands where status = 1 and module_key = :module_key order by name');
        $brandsStmt->execute(['module_key' => $this->moduleKey]);
        $brands = $brandsStmt->fetchAll();
        $vendorsStmt = $db->prepare('select * from vendors where module_key = :module_key and status = \'approved\'' . Auth::zoneWhere('vendors') . ' order by shop_name');
        $vendorsStmt->execute(Auth::zoneParams(['module_key' => $this->moduleKey]));
        $vendors = $vendorsStmt->fetchAll();

        View::render('admin/products', [
            'title' => 'Products',
            'products' => $products,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
            'vendors' => $vendors,
            'zones' => $this->zones(),
            'basePath' => $this->basePath,
            'moduleKey' => $this->moduleKey,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Response::redirect($this->basePath);
        }

        $thumbnail = Upload::image('thumbnail', 'products');
        $stmt = Database::connection()->prepare(
            'insert into products (module_key, zone_id, vendor_id, brand_id, category_id, subcategory_id, name, slug, description, unit, price, discount_price, stock, sku, thumbnail, tax_percent, shipping_cost, barcode, seo_title, seo_description, attributes_json, colors_json, is_digital, digital_file_url, is_flash_deal, flash_deal_ends_at, is_clearance, freshness_note, expiry_date, shelf_life, warranty_note, return_policy, status, is_featured, created_at, updated_at)
             values (:module_key, :zone_id, :vendor_id, :brand_id, :category_id, :subcategory_id, :name, :slug, :description, :unit, :price, :discount_price, :stock, :sku, :thumbnail, :tax_percent, :shipping_cost, :barcode, :seo_title, :seo_description, :attributes_json, :colors_json, :is_digital, :digital_file_url, :is_flash_deal, :flash_deal_ends_at, :is_clearance, :freshness_note, :expiry_date, :shelf_life, :warranty_note, :return_policy, :status, :is_featured, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'module_key' => $this->moduleKey,
            'zone_id' => $this->zoneId(),
            'vendor_id' => $this->vendorId(),
            'brand_id' => ($_POST['brand_id'] ?? '') === '' ? null : (int) $_POST['brand_id'],
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'subcategory_id' => $this->subcategoryId((int) ($_POST['category_id'] ?? 0)),
            'name' => $name,
            'slug' => $this->slug($name),
            'description' => trim($_POST['description'] ?? ''),
            'unit' => trim($_POST['unit'] ?? 'piece'),
            'price' => (float) ($_POST['price'] ?? 0),
            'discount_price' => ($_POST['discount_price'] ?? '') === '' ? null : (float) $_POST['discount_price'],
            'stock' => (int) ($_POST['stock'] ?? 0),
            'sku' => $this->sku($name, trim($_POST['sku'] ?? '')),
            'thumbnail' => $thumbnail,
            'tax_percent' => max(0, (float) ($_POST['tax_percent'] ?? 0)),
            'shipping_cost' => max(0, (float) ($_POST['shipping_cost'] ?? 0)),
            'barcode' => trim($_POST['barcode'] ?? ''),
            'seo_title' => trim($_POST['seo_title'] ?? ''),
            'seo_description' => trim($_POST['seo_description'] ?? ''),
            'attributes_json' => $this->jsonList($_POST['attributes'] ?? ''),
            'colors_json' => $this->jsonList($_POST['colors'] ?? ''),
            'is_digital' => isset($_POST['is_digital']) ? 1 : 0,
            'digital_file_url' => trim($_POST['digital_file_url'] ?? ''),
            'is_flash_deal' => isset($_POST['is_flash_deal']) ? 1 : 0,
            'flash_deal_ends_at' => $this->nullable($_POST['flash_deal_ends_at'] ?? ''),
            'is_clearance' => isset($_POST['is_clearance']) ? 1 : 0,
            'freshness_note' => trim($_POST['freshness_note'] ?? ''),
            'expiry_date' => $this->nullable($_POST['expiry_date'] ?? ''),
            'shelf_life' => trim($_POST['shelf_life'] ?? ''),
            'warranty_note' => trim($_POST['warranty_note'] ?? ''),
            'return_policy' => trim($_POST['return_policy'] ?? ''),
            'status' => (int) ($_POST['status'] ?? 1),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ]);
        $productId = (int) Database::connection()->lastInsertId();
        $this->saveVariants($productId);
        $this->saveImages($productId);

        Response::redirect($this->basePath);
    }

    public function delete(int $id): void
    {
        Auth::requireAdmin();
        $stmt = Database::connection()->prepare('delete from products where id = :id and module_key = :module_key' . Auth::zoneWhere('products'));
        $stmt->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
        Response::redirect($this->basePath);
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $db = Database::connection();
        $stmt = $db->prepare('select * from products where id = :id and module_key = :module_key' . Auth::zoneWhere('products'));
        $stmt->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
        $product = $stmt->fetch();
        if (!$product) {
            Response::redirect($this->basePath);
        }

        $categoriesStmt = $db->prepare('select * from categories where status = 1 and module_key = :module_key order by name');
        $categoriesStmt->execute(['module_key' => $this->moduleKey]);
        $categories = $categoriesStmt->fetchAll();
        $subcategories = $this->subcategories();
        $brandsStmt = $db->prepare('select * from brands where status = 1 and module_key = :module_key order by name');
        $brandsStmt->execute(['module_key' => $this->moduleKey]);
        $brands = $brandsStmt->fetchAll();
        $vendorsStmt = $db->prepare('select * from vendors where module_key = :module_key and status = \'approved\'' . Auth::zoneWhere('vendors') . ' order by shop_name');
        $vendorsStmt->execute(Auth::zoneParams(['module_key' => $this->moduleKey]));
        $vendors = $vendorsStmt->fetchAll();
        $variants = $db->prepare('select * from product_variants where product_id = :id order by id asc');
        $variants->execute(['id' => $id]);
        $images = $db->prepare('select * from product_images where product_id = :id order by sort_order asc, id asc');
        $images->execute(['id' => $id]);
        View::render('admin/product_edit', [
            'title' => 'Edit Product',
            'product' => $product,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
            'vendors' => $vendors,
            'zones' => $this->zones(),
            'variantsText' => $this->variantsText($variants->fetchAll()),
            'images' => $images->fetchAll(),
            'attributesText' => $this->listText($product['attributes_json'] ?? ''),
            'colorsText' => $this->listText($product['colors_json'] ?? ''),
            'basePath' => $this->basePath,
            'moduleKey' => $this->moduleKey,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Response::redirect($this->basePath . '/' . $id . '/edit');
        }

        $db = Database::connection();
        $current = $db->prepare('select * from products where id = :id and module_key = :module_key' . Auth::zoneWhere('products'));
        $current->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
        $product = $current->fetch();
        if (!$product) {
            Response::redirect($this->basePath);
        }

        $thumbnail = Upload::image('thumbnail', 'products') ?? $product['thumbnail'];
        $stmt = $db->prepare(
            'update products set zone_id = :zone_id, vendor_id = :vendor_id, brand_id = :brand_id, category_id = :category_id, subcategory_id = :subcategory_id, name = :name, slug = :slug, description = :description, unit = :unit, price = :price, discount_price = :discount_price, stock = :stock, sku = :sku, thumbnail = :thumbnail, tax_percent = :tax_percent, shipping_cost = :shipping_cost, barcode = :barcode, seo_title = :seo_title, seo_description = :seo_description, attributes_json = :attributes_json, colors_json = :colors_json, is_digital = :is_digital, digital_file_url = :digital_file_url, is_flash_deal = :is_flash_deal, flash_deal_ends_at = :flash_deal_ends_at, is_clearance = :is_clearance, freshness_note = :freshness_note, expiry_date = :expiry_date, shelf_life = :shelf_life, warranty_note = :warranty_note, return_policy = :return_policy, status = :status, is_featured = :is_featured, updated_at = CURRENT_TIMESTAMP where id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'zone_id' => $this->zoneId(),
            'vendor_id' => $this->vendorId(),
            'brand_id' => ($_POST['brand_id'] ?? '') === '' ? null : (int) $_POST['brand_id'],
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'subcategory_id' => $this->subcategoryId((int) ($_POST['category_id'] ?? 0)),
            'name' => $name,
            'slug' => $this->slug($name),
            'description' => trim($_POST['description'] ?? ''),
            'unit' => trim($_POST['unit'] ?? 'piece'),
            'price' => (float) ($_POST['price'] ?? 0),
            'discount_price' => ($_POST['discount_price'] ?? '') === '' ? null : (float) $_POST['discount_price'],
            'stock' => (int) ($_POST['stock'] ?? 0),
            'sku' => $this->sku($name, trim($_POST['sku'] ?? ''), $product['sku'] ?? ''),
            'thumbnail' => $thumbnail,
            'tax_percent' => max(0, (float) ($_POST['tax_percent'] ?? 0)),
            'shipping_cost' => max(0, (float) ($_POST['shipping_cost'] ?? 0)),
            'barcode' => trim($_POST['barcode'] ?? ''),
            'seo_title' => trim($_POST['seo_title'] ?? ''),
            'seo_description' => trim($_POST['seo_description'] ?? ''),
            'attributes_json' => $this->jsonList($_POST['attributes'] ?? ''),
            'colors_json' => $this->jsonList($_POST['colors'] ?? ''),
            'is_digital' => isset($_POST['is_digital']) ? 1 : 0,
            'digital_file_url' => trim($_POST['digital_file_url'] ?? ''),
            'is_flash_deal' => isset($_POST['is_flash_deal']) ? 1 : 0,
            'flash_deal_ends_at' => $this->nullable($_POST['flash_deal_ends_at'] ?? ''),
            'is_clearance' => isset($_POST['is_clearance']) ? 1 : 0,
            'freshness_note' => trim($_POST['freshness_note'] ?? ''),
            'expiry_date' => $this->nullable($_POST['expiry_date'] ?? ''),
            'shelf_life' => trim($_POST['shelf_life'] ?? ''),
            'warranty_note' => trim($_POST['warranty_note'] ?? ''),
            'return_policy' => trim($_POST['return_policy'] ?? ''),
            'status' => (int) ($_POST['status'] ?? 1),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ]);
        $this->saveVariants($id);
        $this->saveImages($id);

        Response::redirect($this->basePath);
    }

    private function slug(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
    }

    private function subcategories(): array
    {
        $stmt = Database::connection()->prepare('select id, category_id, name from subcategories where module_key = :module_key and status = 1 order by sort_order asc, name asc');
        $stmt->execute(['module_key' => $this->moduleKey]);
        return $stmt->fetchAll();
    }

    private function subcategoryId(int $categoryId): ?int
    {
        $id = (int) ($_POST['subcategory_id'] ?? 0);
        if ($id <= 0 || $categoryId <= 0) {
            return null;
        }
        $stmt = Database::connection()->prepare('select id from subcategories where id = :id and category_id = :category_id and module_key = :module_key and status = 1 limit 1');
        $stmt->execute(['id' => $id, 'category_id' => $categoryId, 'module_key' => $this->moduleKey]);
        return $stmt->fetch() ? $id : null;
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
        return 'CSM-' . $prefix . '-' . date('ymdHis') . '-' . random_int(100, 999);
    }

    private function vendorId(): ?int
    {
        $id = (int) ($_POST['vendor_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $stmt = Database::connection()->prepare('select id from vendors where id = :id and module_key = :module_key and status = \'approved\'' . Auth::zoneWhere('vendors') . ' limit 1');
        $stmt->execute(Auth::zoneParams(['id' => $id, 'module_key' => $this->moduleKey]));
        return $stmt->fetch() ? $id : null;
    }

    private function zoneId(): ?int
    {
        if (Auth::isZoneScoped()) {
            return Auth::zoneId();
        }
        $id = (int) ($_POST['zone_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function zones(): array
    {
        if (!Auth::isZoneScoped()) {
            return ZoneSchema::active();
        }
        $stmt = Database::connection()->prepare('select * from zones where id = :id and status = 1 limit 1');
        $stmt->execute(['id' => Auth::zoneId()]);
        $zone = $stmt->fetch();
        return $zone ? [$zone] : [];
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

    private function jsonList(string $input): string
    {
        $parts = preg_split('/\r\n|\r|\n|,/', $input) ?: [];
        $items = array_values(array_filter(array_map(static fn (string $item): string => trim($item), $parts), static fn (string $item): bool => $item !== ''));
        return json_encode($items, JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    private function listText(string $json): string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return '';
        }

        return implode("\n", array_map(static fn ($item): string => (string) $item, $decoded));
    }

    private function nullable(string $value): ?string
    {
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }
}
