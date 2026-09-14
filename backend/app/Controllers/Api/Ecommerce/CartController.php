<?php

declare(strict_types=1);

namespace App\Controllers\Api\Ecommerce;

use App\Support\CouponSchema;
use App\Support\Database;
use App\Support\Env;
use App\Support\ProductExtrasSchema;
use App\Support\Request;
use App\Support\Response;
use App\Support\Settings;
use App\Support\ShippingSchema;

final class CartController
{
    public function __construct(private readonly string $moduleKey = 'ecommerce')
    {
    }

    public function index(): void
    {
        CouponSchema::ensure();
        ProductExtrasSchema::ensure();
        $guestId = $this->guestId();
        $stmt = Database::connection()->prepare('select carts.*, products.name, products.thumbnail, products.unit, products.medicine_type, products.schedule_tag, products.max_qty_per_order, products.max_qty_per_month, products.requires_pharmacist_review, products.requires_age_confirmation, product_variants.name as variant_name, product_variants.unit as variant_unit from carts join products on products.id = carts.product_id left join product_variants on product_variants.id = carts.variant_id where carts.guest_id = :guest_id and carts.module_key = :module_key order by carts.id desc');
        $stmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        Response::json(['data' => array_map([$this, 'formatCartItem'], $stmt->fetchAll()), 'summary' => $this->summary($guestId)]);
    }

    public function add(): void
    {
        CouponSchema::ensure();
        ProductExtrasSchema::ensure();
        $body = Request::json();
        $guestId = $this->guestId();
        $productId = (int) ($body['product_id'] ?? 0);
        $variantId = (int) ($body['variant_id'] ?? 0);
        $quantity = max(1, (int) ($body['quantity'] ?? 1));

        $product = $this->product($productId);
        if (!$product) {
            Response::json(['message' => 'Product not found'], 404);
            return;
        }

        $variant = $variantId > 0 ? $this->variant($productId, $variantId) : null;
        if ($variantId > 0 && !$variant) {
            Response::json(['message' => 'Variant not found'], 404);
            return;
        }
        $stock = $variant ? (int) $variant['stock'] : (int) $product['stock'];
        $price = $variant ? ($variant['discount_price'] ?: $variant['price']) : ($product['discount_price'] ?: $product['price']);

        $db = Database::connection();
        $existing = $db->prepare('select * from carts where guest_id = :guest_id and product_id = :product_id and coalesce(variant_id, 0) = :variant_id and module_key = :module_key');
        $existing->execute(['guest_id' => $guestId, 'product_id' => $productId, 'variant_id' => $variantId, 'module_key' => $this->moduleKey]);
        $row = $existing->fetch();
        $currentQuantity = $row ? (int) $row['quantity'] : 0;
        if ($currentQuantity + $quantity > $stock) {
            Response::json(['message' => 'Requested quantity is not available in stock'], 422);
            return;
        }
        if ($this->moduleKey === 'medical') {
            $medicalError = $this->medicalCartError($product, $quantity, $currentQuantity);
            if ($medicalError !== '') {
                Response::json(['message' => $medicalError], 422);
                return;
            }
        }

        if ($row) {
            $stmt = $db->prepare('update carts set quantity = quantity + :quantity, updated_at = CURRENT_TIMESTAMP where id = :id');
            $stmt->execute(['quantity' => $quantity, 'id' => $row['id']]);
        } else {
            $stmt = $db->prepare('insert into carts (module_key, guest_id, product_id, variant_id, quantity, price, created_at, updated_at) values (:module_key, :guest_id, :product_id, :variant_id, :quantity, :price, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
            $stmt->execute([
                'module_key' => $this->moduleKey,
                'guest_id' => $guestId,
                'product_id' => $productId,
                'variant_id' => $variantId > 0 ? $variantId : null,
                'quantity' => $quantity,
                'price' => $price,
            ]);
        }

        $this->index();
    }

    public function update(): void
    {
        CouponSchema::ensure();
        ProductExtrasSchema::ensure();
        $body = Request::json();
        $guestId = $this->guestId();
        $cartId = (int) ($body['cart_id'] ?? 0);
        $quantity = max(1, (int) ($body['quantity'] ?? 1));

        $db = Database::connection();
        $cart = $db->prepare('select carts.*, products.name, products.stock, products.medicine_type, products.max_qty_per_order, product_variants.stock as variant_stock from carts join products on products.id = carts.product_id left join product_variants on product_variants.id = carts.variant_id where carts.id = :id and carts.guest_id = :guest_id and carts.module_key = :module_key');
        $cart->execute(['id' => $cartId, 'guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $row = $cart->fetch();
        if (!$row) {
            Response::json(['message' => 'Cart item not found'], 404);
            return;
        }
        $stock = $row['variant_id'] ? (int) $row['variant_stock'] : (int) $row['stock'];
        if ($quantity > $stock) {
            Response::json(['message' => 'Requested quantity is not available in stock'], 422);
            return;
        }
        if ($this->moduleKey === 'medical') {
            $medicalError = $this->medicalCartError($row, $quantity, 0);
            if ($medicalError !== '') {
                Response::json(['message' => $medicalError], 422);
                return;
            }
        }

        $stmt = $db->prepare('update carts set quantity = :quantity, updated_at = CURRENT_TIMESTAMP where id = :id and guest_id = :guest_id and module_key = :module_key');
        $stmt->execute(['quantity' => $quantity, 'id' => $cartId, 'guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $this->index();
    }

    public function remove(): void
    {
        CouponSchema::ensure();
        ProductExtrasSchema::ensure();
        $guestId = $this->guestId();
        $cartId = (int) ($_GET['cart_id'] ?? 0);
        $stmt = Database::connection()->prepare('delete from carts where id = :id and guest_id = :guest_id and module_key = :module_key');
        $stmt->execute(['id' => $cartId, 'guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $this->index();
    }

    public function removeAll(): void
    {
        CouponSchema::ensure();
        ProductExtrasSchema::ensure();
        $guestId = $this->guestId();
        $stmt = Database::connection()->prepare('delete from carts where guest_id = :guest_id and module_key = :module_key');
        $stmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $coupon = Database::connection()->prepare('delete from cart_coupons where guest_id = :guest_id and module_key = :module_key');
        $coupon->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        Response::json(['data' => [], 'summary' => $this->summary($guestId)]);
    }

    public function applyCoupon(): void
    {
        CouponSchema::ensure();
        $body = Request::json();
        $guestId = $this->guestId();
        $code = strtoupper(trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $body['code'] ?? '')));
        if ($code === '') {
            Response::json(['message' => 'Coupon code is required'], 422);
            return;
        }

        $summary = $this->summary($guestId, null);
        $coupon = $this->coupon($code, (float) $summary['subtotal']);
        if (!$coupon) {
            Response::json(['message' => 'Coupon is not valid for this cart'], 422);
            return;
        }

        $db = Database::connection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $stmt = $db->prepare('replace into cart_coupons (module_key, guest_id, coupon_id, code, created_at, updated_at) values (:module_key, :guest_id, :coupon_id, :code, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
        } else {
            $stmt = $db->prepare('insert or replace into cart_coupons (module_key, guest_id, coupon_id, code, created_at, updated_at) values (:module_key, :guest_id, :coupon_id, :code, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
        }
        $stmt->execute(['module_key' => $this->moduleKey, 'guest_id' => $guestId, 'coupon_id' => $coupon['id'], 'code' => $coupon['code']]);
        $this->index();
    }

    public function removeCoupon(): void
    {
        CouponSchema::ensure();
        $guestId = $this->guestId();
        $stmt = Database::connection()->prepare('delete from cart_coupons where guest_id = :guest_id and module_key = :module_key');
        $stmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $this->index();
    }

    private function guestId(): string
    {
        return trim($_GET['guest_id'] ?? $_SERVER['HTTP_X_GUEST_ID'] ?? 'guest');
    }

    private function product(int $id): ?array
    {
        $stmt = Database::connection()->prepare('select * from products where id = :id and status = 1 and module_key = :module_key');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    private function variant(int $productId, int $variantId): ?array
    {
        $stmt = Database::connection()->prepare('select * from product_variants where id = :id and product_id = :product_id and status = 1 limit 1');
        $stmt->execute(['id' => $variantId, 'product_id' => $productId]);
        $variant = $stmt->fetch();
        return $variant ?: null;
    }

    public function summary(string $guestId, ?array $applied = []): array
    {
        ProductExtrasSchema::ensure();
        $stmt = Database::connection()->prepare(
            'select carts.*, products.tax_percent, products.shipping_cost as product_shipping_cost, categories.shipping_cost as category_shipping_cost
             from carts
             join products on products.id = carts.product_id
             left join categories on categories.id = products.category_id
             where carts.guest_id = :guest_id and carts.module_key = :module_key'
        );
        $stmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $items = $stmt->fetchAll();
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $itemsCount = 0;
        $shippingExtra = 0.0;
        foreach ($items as $item) {
            $line = (float) $item['price'] * (int) $item['quantity'];
            $subtotal += $line;
            $taxTotal += round(($line * (float) ($item['tax_percent'] ?? 0)) / 100, 2);
            $itemsCount += (int) $item['quantity'];
            $shippingExtra += (float) ($item['product_shipping_cost'] ?? 0);
            $shippingExtra += (float) ($item['category_shipping_cost'] ?? 0);
        }
        $shippingMethod = ShippingSchema::method((int) ($_GET['shipping_method_id'] ?? 0), $this->moduleKey);
        $deliveryCharge = $subtotal > 0 ? (float) $shippingMethod['cost'] + $shippingExtra : 0;
        $minimumOrderAmount = Settings::moduleFloat($this->moduleKey, 'minimum_order_amount');
        $coupon = $applied;
        if ($coupon === []) {
            $coupon = $this->appliedCoupon($guestId, $subtotal);
        }
        $discount = $coupon ? $this->discount($coupon, $subtotal) : 0.0;
        return [
            'subtotal' => $subtotal,
            'coupon_code' => $coupon['code'] ?? '',
            'coupon_title' => $coupon['title'] ?? '',
            'coupon_discount' => $discount,
            'tax_total' => $taxTotal,
            'delivery_charge' => $deliveryCharge,
            'shipping_method_id' => (int) $shippingMethod['id'],
            'shipping_method_name' => (string) $shippingMethod['name'],
            'expected_delivery' => (string) ($shippingMethod['expected_days'] ?? ''),
            'total' => max(0, $subtotal - $discount) + $taxTotal + $deliveryCharge,
            'items_count' => $itemsCount,
            'minimum_order_amount' => $minimumOrderAmount,
            'minimum_order_remaining' => max(0, $minimumOrderAmount - $subtotal),
        ];
    }

    public function appliedCoupon(string $guestId, float $subtotal): ?array
    {
        $stmt = Database::connection()->prepare('select coupons.* from cart_coupons join coupons on coupons.id = cart_coupons.coupon_id where cart_coupons.guest_id = :guest_id and cart_coupons.module_key = :module_key limit 1');
        $stmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $coupon = $stmt->fetch();
        if (!$coupon || !$this->validCoupon($coupon, $subtotal)) {
            $remove = Database::connection()->prepare('delete from cart_coupons where guest_id = :guest_id and module_key = :module_key');
            $remove->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
            return null;
        }
        return $coupon;
    }

    public function discount(array $coupon, float $subtotal): float
    {
        $discount = $coupon['discount_type'] === 'percent'
            ? ($subtotal * (float) $coupon['discount_value']) / 100
            : (float) $coupon['discount_value'];
        if (($coupon['maximum_discount'] ?? null) !== null && (float) $coupon['maximum_discount'] > 0) {
            $discount = min($discount, (float) $coupon['maximum_discount']);
        }
        return round(min($subtotal, max(0, $discount)), 2);
    }

    private function coupon(string $code, float $subtotal): ?array
    {
        $stmt = Database::connection()->prepare('select * from coupons where code = :code and module_key = :module_key limit 1');
        $stmt->execute(['code' => $code, 'module_key' => $this->moduleKey]);
        $coupon = $stmt->fetch();
        return $coupon && $this->validCoupon($coupon, $subtotal) ? $coupon : null;
    }

    private function validCoupon(array $coupon, float $subtotal): bool
    {
        if ((int) $coupon['status'] !== 1) {
            return false;
        }
        if ($subtotal <= 0 || $subtotal < (float) $coupon['minimum_order_amount']) {
            return false;
        }
        if (($coupon['usage_limit'] ?? null) !== null && (int) $coupon['usage_limit'] > 0 && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            return false;
        }
        $today = date('Y-m-d');
        if (!empty($coupon['starts_at']) && $coupon['starts_at'] > $today) {
            return false;
        }
        if (!empty($coupon['expires_at']) && $coupon['expires_at'] < $today) {
            return false;
        }
        return true;
    }

    private function formatCartItem(array $row): array
    {
        if (!empty($row['variant_name'])) {
            $row['name'] .= ' - ' . $row['variant_name'];
            $row['unit'] = $row['variant_unit'] ?: $row['unit'];
        }
        if (($row['thumbnail'] ?? null) !== null) {
            $row['thumbnail_full_url'] = $this->assetUrl((string) $row['thumbnail']);
        }
        return $row;
    }

    private function assetUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $configured = rtrim(Env::get('APP_URL', ''), '/');
        if ($configured !== '' && !str_contains($configured, '127.0.0.1') && !str_contains($configured, 'localhost')) {
            return $configured . '/' . ltrim($path, '/');
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        if ($proto === null) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        }

        return rtrim($proto . '://' . $host, '/') . '/' . ltrim($path, '/');
    }

    private function medicalCartError(array $product, int $quantity, int $existingQuantity): string
    {
        $name = (string) ($product['name'] ?? 'Medicine');
        if ((string) ($product['medicine_type'] ?? 'otc') === 'blocked_online') {
            return $name . ' is not available for online ordering. Please contact support or a licensed pharmacy.';
        }

        $maxQty = (int) ($product['max_qty_per_order'] ?? 0);
        if ($maxQty > 0 && ($existingQuantity + $quantity) > $maxQty) {
            return $name . ' has a maximum quantity limit of ' . $maxQty . ' per order';
        }

        return '';
    }
}
