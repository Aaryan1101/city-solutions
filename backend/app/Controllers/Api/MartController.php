<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\BrandSchema;
use App\Support\Database;
use App\Support\PaymentMethodCatalog;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\Settings;
use App\Support\ShippingSchema;
use App\Support\VendorSchema;
use App\Support\ZoneSchema;

final class MartController
{
    protected function moduleKey(): string
    {
        return 'mart';
    }

    public function config(): void
    {
        Response::json($this->configData());
    }

    private function configData(): array
    {
        $moduleKey = $this->moduleKey();
        ZoneSchema::ensure();
        $supportedLocales = array_values(array_filter(array_map('trim', explode(',', Settings::moduleGet($moduleKey, 'supported_locales', 'en,hi')))));
        $shippingMethods = array_map(static fn (array $method): array => [
            'id' => (int) $method['id'],
            'name' => (string) $method['name'],
            'description' => (string) ($method['description'] ?? ''),
            'cost' => (float) $method['cost'],
            'expected_days' => (string) ($method['expected_days'] ?? ''),
        ], ShippingSchema::activeMethods($moduleKey));
        return [
            'app_name' => Settings::moduleGet($moduleKey, 'app_name', 'City Solutions Mart'),
            'currency' => Settings::moduleGet($moduleKey, 'currency', 'INR'),
            'currency_symbol' => Settings::moduleGet($moduleKey, 'currency_symbol', '₹'),
            'minimum_order_amount' => Settings::moduleFloat($moduleKey, 'minimum_order_amount'),
            'delivery_charge' => Settings::moduleFloat($moduleKey, 'delivery_charge'),
            'cod_enabled' => Settings::moduleBool($moduleKey, 'cod_enabled', true),
            'payment_methods' => PaymentMethodCatalog::enabled($moduleKey),
            'zones' => ZoneSchema::active(),
            'shipping_methods' => $shippingMethods,
            'app_locale' => Settings::moduleGet($moduleKey, 'app_locale', 'en'),
            'supported_locales' => $supportedLocales === [] ? ['en', 'hi'] : $supportedLocales,
            'maintenance_mode' => Settings::moduleBool($moduleKey, 'maintenance_mode'),
            'maintenance_message' => Settings::moduleGet($moduleKey, 'maintenance_message'),
            'latest_app_version' => Settings::moduleGet($moduleKey, 'latest_app_version'),
            'force_update_version' => Settings::moduleGet($moduleKey, 'force_update_version'),
            'firebase_push' => [
                'enabled' => Settings::moduleBool($moduleKey, 'firebase_push_enabled'),
                'project_id' => Settings::moduleGet($moduleKey, 'firebase_project_id'),
                'sender_id' => Settings::moduleGet($moduleKey, 'firebase_sender_id'),
            ],
            'support_chat' => [
                'provider' => Settings::moduleGet($moduleKey, 'support_realtime_provider', 'polling'),
                'poll_interval_seconds' => max(5, (int) Settings::moduleGet($moduleKey, 'support_poll_interval_seconds', '15')),
            ],
            'cms_pages' => [
                [
                    'slug' => 'about-us',
                    'title' => 'About Us',
                    'content' => Settings::moduleGet($moduleKey, 'about_us'),
                ],
                [
                    'slug' => 'terms-conditions',
                    'title' => 'Terms & Conditions',
                    'content' => Settings::moduleGet($moduleKey, 'terms_conditions'),
                ],
                [
                    'slug' => 'privacy-policy',
                    'title' => 'Privacy Policy',
                    'content' => Settings::moduleGet($moduleKey, 'privacy_policy'),
                ],
                [
                    'slug' => 'refund-policy',
                    'title' => 'Refund Policy',
                    'content' => Settings::moduleGet($moduleKey, 'refund_policy'),
                ],
                [
                    'slug' => 'shipping-policy',
                    'title' => 'Shipping Policy',
                    'content' => Settings::moduleGet($moduleKey, 'shipping_policy'),
                ],
                [
                    'slug' => 'support',
                    'title' => 'Support',
                    'content' => Settings::moduleGet($moduleKey, 'support_content'),
                ],
            ],
        ];
    }

    public function home(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $db = Database::connection();
        $banners = $db->prepare('select * from banners where status = 1 and module_key = :module_key order by sort_order asc, id desc');
        $banners->execute(['module_key' => $this->moduleKey()]);
        $brands = $db->prepare('select * from brands where status = 1 and module_key = :module_key order by sort_order asc, id desc limit 20');
        $brands->execute(['module_key' => $this->moduleKey()]);
        Response::json([
            'config' => $this->configData(),
            'banners' => array_map([$this, 'withImageUrl'], $banners->fetchAll()),
            'categories' => $this->categoriesData(),
            'brands' => array_map([$this, 'withImageUrl'], $brands->fetchAll()),
            'vendors' => $db->query('select id, zone_id, shop_name, owner_name, phone, email, address, city, description, is_temporarily_closed, vacation_starts_at, vacation_ends_at, vacation_note from vendors where module_key = \'' . $this->moduleKey() . '\' and status = \'approved\'' . ZoneSchema::inlineSql('vendors') . ' order by id desc limit 20')->fetchAll(),
            'featured_products' => $this->productRows('products.is_featured = 1', 'products.id desc', 30),
            'flash_deal_products' => $this->productRows('products.is_flash_deal = 1 and (products.flash_deal_ends_at is null or products.flash_deal_ends_at >= CURRENT_TIMESTAMP)', 'products.flash_deal_ends_at asc, products.id desc', 20),
            'clearance_products' => $this->productRows('products.is_clearance = 1', 'products.discount_price asc, products.id desc', 20),
            'top_rated_products' => $this->topRatedRows(),
            'best_selling_products' => $this->bestSellingRows(),
            'latest_products' => $this->productRows('', 'products.id desc', 30),
        ]);
    }

    public function banners(): void
    {
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('select * from banners where status = 1 and module_key = :module_key order by sort_order asc, id desc');
        $stmt->execute(['module_key' => $this->moduleKey()]);
        $rows = $stmt->fetchAll();
        Response::json(['data' => array_map([$this, 'withImageUrl'], $rows)]);
    }

    public function categories(): void
    {
        ProductExtrasSchema::ensure();
        Response::json(['data' => $this->categoriesData()]);
    }

    public function subcategories(): void
    {
        ProductExtrasSchema::ensure();
        $sql = 'select * from subcategories where status = 1 and module_key = :module_key';
        $params = ['module_key' => $this->moduleKey()];
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        if ($categoryId > 0) {
            $sql .= ' and category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        $stmt = Database::connection()->prepare($sql . ' order by sort_order asc, id desc');
        $stmt->execute($params);
        Response::json(['data' => array_map([$this, 'withImageUrl'], $stmt->fetchAll())]);
    }

    public function brands(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare('select * from brands where status = 1 and module_key = :module_key order by sort_order asc, id desc limit 20');
        $stmt->execute(['module_key' => $this->moduleKey()]);
        $rows = $stmt->fetchAll();
        Response::json(['data' => array_map([$this, 'withImageUrl'], $rows)]);
    }

    public function vendors(): void
    {
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $rows = Database::connection()->query('select id, zone_id, shop_name, owner_name, phone, email, address, city, description, is_temporarily_closed, vacation_starts_at, vacation_ends_at, vacation_note from vendors where module_key = \'' . $this->moduleKey() . '\' and status = \'approved\'' . ZoneSchema::inlineSql('vendors') . ' order by id desc limit 20')->fetchAll();
        Response::json(['data' => $rows]);
    }

    public function products(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $offset = max(1, (int) ($_GET['offset'] ?? 1));
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
        $skip = ($offset - 1) * $limit;

        [$filterSql, $filterParams] = $this->productFilters();
        $stmt = Database::connection()->prepare($this->productSelect(ltrim($filterSql, ' and')) . ' order by ' . $this->productSort() . ' limit :limit offset :skip');
        foreach ($filterParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('skip', $skip, \PDO::PARAM_INT);
        $stmt->execute();

        Response::json(['data' => array_map([$this, 'formatProduct'], $stmt->fetchAll()), 'offset' => $offset, 'limit' => $limit]);
    }

    public function featuredProducts(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $rows = Database::connection()->query($this->productSelect('products.is_featured = 1') . ' order by products.id desc limit 30')->fetchAll();
        Response::json(['data' => array_map([$this, 'formatProduct'], $rows)]);
    }

    public function flashDeals(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $rows = Database::connection()->query(
            $this->productSelect(
                'products.is_flash_deal = 1 and (products.flash_deal_ends_at is null or products.flash_deal_ends_at >= CURRENT_TIMESTAMP)'
            ) . ' order by products.flash_deal_ends_at asc, products.id desc limit 20'
        )->fetchAll();
        Response::json(['data' => array_map([$this, 'formatProduct'], $rows)]);
    }

    public function clearanceProducts(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $rows = Database::connection()->query(
            $this->productSelect('products.is_clearance = 1') . ' order by products.discount_price asc, products.id desc limit 20'
        )->fetchAll();
        Response::json(['data' => array_map([$this, 'formatProduct'], $rows)]);
    }

    public function topRatedProducts(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $rows = Database::connection()->query(
            'select products.*, brands.name as brand_name, vendors.shop_name as vendor_name,
                    coalesce(avg(product_reviews.rating), 0) as avg_rating,
                    count(product_reviews.id) as reviews_count
             from products
             left join brands on brands.id = products.brand_id
             left join vendors on vendors.id = products.vendor_id
             left join product_reviews on product_reviews.product_id = products.id and product_reviews.status = 1
             where products.status = 1 and products.module_key = \'' . $this->moduleKey() . '\' and (products.vendor_id is null or (vendors.module_key = \'' . $this->moduleKey() . '\' and vendors.status = \'approved\'))
             group by products.id, brands.name, vendors.shop_name
             having reviews_count > 0
             order by avg_rating desc, reviews_count desc, products.id desc
             limit 20'
        )->fetchAll();
        Response::json(['data' => array_map([$this, 'formatProduct'], $rows)]);
    }

    public function bestSellingProducts(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $rows = Database::connection()->query(
            'select products.*, brands.name as brand_name, vendors.shop_name as vendor_name,
                    coalesce(sum(order_items.quantity), 0) as sold_quantity
             from products
             left join brands on brands.id = products.brand_id
             left join vendors on vendors.id = products.vendor_id
             left join order_items on order_items.product_id = products.id
             left join orders on orders.id = order_items.order_id and orders.order_status != \'cancelled\'
             where products.status = 1 and products.module_key = \'' . $this->moduleKey() . '\' and (products.vendor_id is null or (vendors.module_key = \'' . $this->moduleKey() . '\' and vendors.status = \'approved\'))
             group by products.id, brands.name, vendors.shop_name
             having sold_quantity > 0
             order by sold_quantity desc, products.id desc
             limit 20'
        )->fetchAll();
        Response::json(['data' => array_map([$this, 'formatProduct'], $rows)]);
    }

    public function latestProducts(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $rows = Database::connection()->query($this->productSelect() . ' order by products.id desc limit 30')->fetchAll();
        Response::json(['data' => array_map([$this, 'formatProduct'], $rows)]);
    }

    public function searchProducts(): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        [$filterSql, $filterParams] = $this->productFilters();
        $query = '%' . trim($_GET['query'] ?? '') . '%';
        $stmt = Database::connection()->prepare($this->productSelect('products.name like :query' . $filterSql) . ' order by ' . $this->productSort() . ' limit 50');
        $stmt->execute(['query' => $query] + $filterParams);
        Response::json(['data' => array_map([$this, 'formatProduct'], $stmt->fetchAll())]);
    }

    public function product(int $id): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $stmt = Database::connection()->prepare($this->productSelect('products.id = :id'));
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        if (!$product) {
            Response::json(['message' => 'Product not found'], 404);
            return;
        }

        Response::json(['data' => $this->formatProduct($product)]);
    }

    public function categoryProducts(int $categoryId): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        [$filterSql, $filterParams] = $this->productFilters();
        $stmt = Database::connection()->prepare($this->productSelect('products.category_id = :category_id' . $filterSql) . ' order by products.id desc');
        $stmt->execute(['category_id' => $categoryId] + $filterParams);
        Response::json(['data' => array_map([$this, 'formatProduct'], $stmt->fetchAll())]);
    }

    public function subcategoryProducts(int $subcategoryId): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $subcategory = Database::connection()->prepare('select id from subcategories where id = :id and status = 1 and module_key = :module_key limit 1');
        $subcategory->execute(['id' => $subcategoryId, 'module_key' => $this->moduleKey()]);
        if (!$subcategory->fetch()) {
            Response::json(['message' => 'Subcategory not found'], 404);
            return;
        }
        [$filterSql, $filterParams] = $this->productFilters();
        $stmt = Database::connection()->prepare($this->productSelect('products.subcategory_id = :subcategory_id' . $filterSql) . ' order by products.id desc');
        $stmt->execute(['subcategory_id' => $subcategoryId] + $filterParams);
        Response::json(['data' => array_map([$this, 'formatProduct'], $stmt->fetchAll())]);
    }

    public function brandProducts(int $brandId): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $brand = Database::connection()->prepare('select id from brands where id = :id and status = 1 and module_key = :module_key limit 1');
        $brand->execute(['id' => $brandId, 'module_key' => $this->moduleKey()]);
        if (!$brand->fetch()) {
            Response::json(['message' => 'Brand not found'], 404);
            return;
        }

        [$filterSql, $filterParams] = $this->productFilters();
        $stmt = Database::connection()->prepare($this->productSelect('products.brand_id = :brand_id' . $filterSql) . ' order by products.id desc');
        $stmt->execute(['brand_id' => $brandId] + $filterParams);
        Response::json(['data' => array_map([$this, 'formatProduct'], $stmt->fetchAll())]);
    }

    public function vendor(int $id): void
    {
        VendorSchema::ensure();
        $stmt = Database::connection()->prepare('select id, shop_name, owner_name, phone, email, address, city, description, is_temporarily_closed, vacation_starts_at, vacation_ends_at, vacation_note from vendors where id = :id and module_key = :module_key and status = \'approved\' limit 1');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey()]);
        $vendor = $stmt->fetch();
        if (!$vendor) {
            Response::json(['message' => 'Vendor not found'], 404);
            return;
        }

        Response::json(['data' => $vendor]);
    }

    public function vendorProducts(int $id): void
    {
        BrandSchema::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $vendor = Database::connection()->prepare('select id from vendors where id = :id and module_key = :module_key and status = \'approved\' and coalesce(is_temporarily_closed, 0) = 0 limit 1');
        $vendor->execute(['id' => $id, 'module_key' => $this->moduleKey()]);
        if (!$vendor->fetch()) {
            Response::json(['message' => 'Vendor not found'], 404);
            return;
        }

        [$filterSql, $filterParams] = $this->productFilters();
        $stmt = Database::connection()->prepare($this->productSelect('products.vendor_id = :vendor_id' . $filterSql) . ' order by products.id desc');
        $stmt->execute(['vendor_id' => $id] + $filterParams);
        Response::json(['data' => array_map([$this, 'formatProduct'], $stmt->fetchAll())]);
    }

    private function withImageUrl(array $row): array
    {
        if (($row['image'] ?? null) !== null) {
            $row['image_full_url'] = $this->assetUrl((string) $row['image']);
        }
        return $row;
    }

    private function formatProduct(array $row): array
    {
        $row['price'] = (float) $row['price'];
        $row['discount_price'] = $row['discount_price'] === null ? null : (float) $row['discount_price'];
        $row['stock'] = (int) $row['stock'];
        $row['is_featured'] = (bool) $row['is_featured'];
        $row['brand_id'] = (int) ($row['brand_id'] ?? 0);
        $row['brand_name'] = $row['brand_name'] ?? null;
        $row['category_id'] = (int) ($row['category_id'] ?? 0);
        $row['category_name'] = $row['category_name'] ?? null;
        $row['subcategory_id'] = (int) ($row['subcategory_id'] ?? 0);
        $row['subcategory_name'] = $row['subcategory_name'] ?? null;
        $row['vendor_id'] = (int) ($row['vendor_id'] ?? 0);
        $row['vendor_name'] = $row['vendor_name'] ?? null;
        $row['tax_percent'] = (float) ($row['tax_percent'] ?? 0);
        $row['barcode'] = $row['barcode'] ?? '';
        $row['seo_title'] = $row['seo_title'] ?? '';
        $row['seo_description'] = $row['seo_description'] ?? '';
        $row['attributes'] = $this->jsonList($row['attributes_json'] ?? '');
        $row['colors'] = $this->jsonList($row['colors_json'] ?? '');
        $row['is_digital'] = !empty($row['is_digital']);
        $row['digital_file_url'] = $row['digital_file_url'] ?? '';
        $row['freshness_note'] = $row['freshness_note'] ?? '';
        $row['expiry_date'] = $row['expiry_date'] ?? '';
        $row['shelf_life'] = $row['shelf_life'] ?? '';
        $row['warranty_note'] = $row['warranty_note'] ?? '';
        $row['return_policy'] = $row['return_policy'] ?? '';
        if (($row['thumbnail'] ?? null) !== null) {
            $row['thumbnail_full_url'] = $this->assetUrl((string) $row['thumbnail']);
        }
        $row['images'] = $this->productImages((int) $row['id'], $row['thumbnail'] ?? null);
        $row['variants'] = $this->productVariants((int) $row['id']);
        return $row;
    }

    private function productImages(int $productId, ?string $thumbnail): array
    {
        $images = [];
        if ($thumbnail !== null && $thumbnail !== '') {
            $images[] = $this->assetUrl($thumbnail);
        }
        $stmt = Database::connection()->prepare('select image from product_images where product_id = :product_id order by sort_order asc, id asc');
        $stmt->execute(['product_id' => $productId]);
        foreach ($stmt->fetchAll() as $row) {
            $images[] = $this->assetUrl((string) $row['image']);
        }
        return array_values(array_unique($images));
    }

    private function productVariants(int $productId): array
    {
        $stmt = Database::connection()->prepare('select * from product_variants where product_id = :product_id and status = 1 order by id asc');
        $stmt->execute(['product_id' => $productId]);
        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['price'] = (float) $row['price'];
            $row['discount_price'] = $row['discount_price'] === null ? null : (float) $row['discount_price'];
            $row['stock'] = (int) $row['stock'];
            return $row;
        }, $stmt->fetchAll());
    }

    private function productSelect(string $extraWhere = ''): string
    {
        ZoneSchema::ensure();
        $where = 'products.status = 1 and products.module_key = \'' . $this->moduleKey() . '\' and (products.vendor_id is null or (vendors.module_key = \'' . $this->moduleKey() . '\' and vendors.status = \'approved\' and coalesce(vendors.is_temporarily_closed, 0) = 0))' . ZoneSchema::inlineSql('products');
        if ($extraWhere !== '') {
            $where .= ' and ' . $extraWhere;
        }

        return 'select products.*, categories.name as category_name, subcategories.name as subcategory_name, brands.name as brand_name, vendors.shop_name as vendor_name from products left join categories on categories.id = products.category_id left join subcategories on subcategories.id = products.subcategory_id and subcategories.module_key = products.module_key left join brands on brands.id = products.brand_id left join vendors on vendors.id = products.vendor_id where ' . $where;
    }

    private function categoriesData(): array
    {
        $db = Database::connection();
        $categories = $db->prepare('select * from categories where status = 1 and module_key = :module_key order by sort_order asc, id desc');
        $categories->execute(['module_key' => $this->moduleKey()]);
        $subcategories = $db->prepare('select * from subcategories where status = 1 and module_key = :module_key order by sort_order asc, id desc');
        $subcategories->execute(['module_key' => $this->moduleKey()]);
        $byCategory = [];
        foreach ($subcategories->fetchAll() as $subcategory) {
            $byCategory[(int) $subcategory['category_id']][] = $this->withImageUrl($subcategory);
        }
        return array_map(function (array $category) use ($byCategory): array {
            $category = $this->withImageUrl($category);
            $category['subcategories'] = $byCategory[(int) $category['id']] ?? [];
            return $category;
        }, $categories->fetchAll());
    }

    private function productRows(string $where, string $order, int $limit): array
    {
        $rows = Database::connection()->query(
            $this->productSelect($where) . ' order by ' . $order . ' limit ' . $limit
        )->fetchAll();
        return array_map([$this, 'formatProduct'], $rows);
    }

    private function topRatedRows(): array
    {
        $rows = Database::connection()->query(
            'select products.*, brands.name as brand_name, vendors.shop_name as vendor_name,
                    coalesce(avg(product_reviews.rating), 0) as avg_rating,
                    count(product_reviews.id) as reviews_count
             from products
             left join brands on brands.id = products.brand_id
             left join vendors on vendors.id = products.vendor_id
             left join product_reviews on product_reviews.product_id = products.id and product_reviews.status = 1
             where products.status = 1 and products.module_key = \'' . $this->moduleKey() . '\' and (products.vendor_id is null or (vendors.module_key = \'' . $this->moduleKey() . '\' and vendors.status = \'approved\'))
             group by products.id, brands.name, vendors.shop_name
             having reviews_count > 0
             order by avg_rating desc, reviews_count desc, products.id desc
             limit 20'
        )->fetchAll();
        return array_map([$this, 'formatProduct'], $rows);
    }

    private function bestSellingRows(): array
    {
        $rows = Database::connection()->query(
            'select products.*, brands.name as brand_name, vendors.shop_name as vendor_name,
                    coalesce(sum(order_items.quantity), 0) as sold_quantity
             from products
             left join brands on brands.id = products.brand_id
             left join vendors on vendors.id = products.vendor_id
             left join order_items on order_items.product_id = products.id
             left join orders on orders.id = order_items.order_id and orders.order_status != \'cancelled\'
             where products.status = 1 and products.module_key = \'' . $this->moduleKey() . '\' and (products.vendor_id is null or (vendors.module_key = \'' . $this->moduleKey() . '\' and vendors.status = \'approved\'))
             group by products.id, brands.name, vendors.shop_name
             having sold_quantity > 0
             order by sold_quantity desc, products.id desc
             limit 20'
        )->fetchAll();
        return array_map([$this, 'formatProduct'], $rows);
    }

    private function productFilters(): array
    {
        $sql = '';
        $params = [];
        $color = trim($_GET['color'] ?? '');
        if ($color !== '') {
            $sql .= ' and products.colors_json like :color';
            $params['color'] = '%' . $color . '%';
        }
        $attribute = trim($_GET['attribute'] ?? '');
        if ($attribute !== '') {
            $sql .= ' and products.attributes_json like :attribute';
            $params['attribute'] = '%' . $attribute . '%';
        }
        foreach (['brand_id', 'vendor_id', 'category_id'] as $field) {
            $value = (int) ($_GET[$field] ?? 0);
            if ($value > 0) {
                $sql .= ' and products.' . $field . ' = :' . $field;
                $params[$field] = $value;
            }
        }
        $minPrice = trim($_GET['min_price'] ?? '');
        if ($minPrice !== '') {
            $sql .= ' and coalesce(products.discount_price, products.price) >= :min_price';
            $params['min_price'] = max(0, (float) $minPrice);
        }
        $maxPrice = trim($_GET['max_price'] ?? '');
        if ($maxPrice !== '') {
            $sql .= ' and coalesce(products.discount_price, products.price) <= :max_price';
            $params['max_price'] = max(0, (float) $maxPrice);
        }
        if (isset($_GET['digital'])) {
            $sql .= ' and products.is_digital = :is_digital';
            $params['is_digital'] = (int) filter_var($_GET['digital'], FILTER_VALIDATE_BOOLEAN);
        }
        return [$sql, $params];
    }

    private function productSort(): string
    {
        return match (trim($_GET['sort'] ?? 'latest')) {
            'price_low' => 'coalesce(products.discount_price, products.price) asc, products.id desc',
            'price_high' => 'coalesce(products.discount_price, products.price) desc, products.id desc',
            'name' => 'products.name asc, products.id desc',
            'stock' => 'products.stock desc, products.id desc',
            default => 'products.id desc',
        };
    }

    private function jsonList(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_map(static fn ($item): string => (string) $item, $decoded));
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
        $configured = rtrim(\App\Support\Env::get('APP_URL', ''), '/');
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
