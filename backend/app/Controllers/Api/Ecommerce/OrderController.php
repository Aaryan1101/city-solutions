<?php

declare(strict_types=1);

namespace App\Controllers\Api\Ecommerce;

use App\Support\CouponSchema;
use App\Support\Database;
use App\Support\DeliverySchema;
use App\Support\NotificationLog;
use App\Support\NotificationSchema;
use App\Support\PaymentMethodCatalog;
use App\Support\PaymentSchema;
use App\Support\OrderStatusHistory;
use App\Support\ProductExtrasSchema;
use App\Support\PrescriptionSchema;
use App\Support\RefundSchema;
use App\Support\Request;
use App\Support\Response;
use App\Support\Settings;
use App\Support\ShippingSchema;
use App\Support\Upload;
use App\Support\VendorSchema;
use App\Support\WalletService;
use App\Support\ZoneSchema;

final class OrderController
{
    public function __construct(private readonly string $moduleKey = 'ecommerce')
    {
    }

    public function index(): void
    {
        CouponSchema::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        OrderStatusHistory::ensure();
        PaymentSchema::ensure();
        ProductExtrasSchema::ensure();
        RefundSchema::ensure();
        VendorSchema::ensure();
        $guestId = trim($_GET['guest_id'] ?? '');
        if ($guestId === '') {
            Response::json(['data' => []]);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'select orders.*, delivery_men.name as delivery_man_name, delivery_men.phone as delivery_man_phone, delivery_men.vehicle_type, delivery_men.vehicle_number
             from orders
             left join delivery_men on delivery_men.id = orders.delivery_man_id
             where orders.guest_id = :guest_id and orders.module_key = :module_key
             order by orders.id desc'
        );
        $stmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);

        $orders = $stmt->fetchAll();
        Response::json(['data' => $this->decorateOrders($orders)]);
    }

    public function place(): void
    {
        $body = Request::json();
        CouponSchema::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        OrderStatusHistory::ensure();
        PaymentSchema::ensure();
        ProductExtrasSchema::ensure();
        PrescriptionSchema::ensure();
        ShippingSchema::ensure();
        VendorSchema::ensure();
        ZoneSchema::ensure();
        $guestId = trim($body['guest_id'] ?? $_GET['guest_id'] ?? '');
        if ($guestId === '') {
            Response::json(['message' => 'Guest id is required'], 422);
            return;
        }
        $zoneId = max(0, (int) ($body['zone_id'] ?? $_GET['zone_id'] ?? 0));
        $db = Database::connection();

        $cartStmt = $db->prepare('select carts.*, products.name, products.thumbnail, products.stock, products.status, products.zone_id, products.vendor_id, products.tax_percent, products.shipping_cost as product_shipping_cost, products.is_digital, products.digital_file_url, products.attributes_json, products.medicine_type, products.schedule_tag, products.max_qty_per_order, products.max_qty_per_month, products.requires_pharmacist_review, products.requires_age_confirmation, categories.shipping_cost as category_shipping_cost, product_variants.name as variant_name, product_variants.stock as variant_stock from carts join products on products.id = carts.product_id left join categories on categories.id = products.category_id left join product_variants on product_variants.id = carts.variant_id where carts.guest_id = :guest_id and carts.module_key = :module_key');
        $cartStmt->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
        $cart = $cartStmt->fetchAll();
        if ($cart === []) {
            Response::json(['message' => 'Cart is empty'], 422);
            return;
        }
        foreach ($cart as $item) {
            if ($zoneId > 0 && !empty($item['zone_id']) && (int) $item['zone_id'] !== $zoneId) {
                Response::json(['message' => $item['name'] . ' is not serviceable in selected zone'], 422);
                return;
            }
            $stock = $item['variant_id'] ? (int) $item['variant_stock'] : (int) $item['stock'];
            if ((int) $item['status'] !== 1 || (int) $item['quantity'] > $stock) {
                Response::json(['message' => $item['name'] . ' is not available in requested quantity'], 422);
                return;
            }
        }
        $needsPrescription = $this->moduleKey === 'medical' && $this->requiresPrescription($cart);
        if ($needsPrescription) {
            $prescriptionReference = trim((string) ($body['prescription_reference'] ?? $body['prescription_note'] ?? ''));
            $prescriptionFile = trim((string) ($body['prescription_file_base64'] ?? ''));
            if ($prescriptionReference === '' && $prescriptionFile === '') {
                Response::json(['message' => 'Prescription file or note/reference is required for one or more medicines in this order'], 422);
                return;
            }
            if ($prescriptionFile !== '' && !Upload::isValidBase64Document($prescriptionFile)) {
                Response::json(['message' => 'Prescription file must be an image or PDF up to 5 MB'], 422);
                return;
            }
        }

        $subtotal = 0.0;
        $taxTotal = 0.0;
        $shippingExtra = 0.0;
        foreach ($cart as $item) {
            $line = (float) $item['price'] * (int) $item['quantity'];
            $subtotal += $line;
            $taxTotal += round(($line * (float) ($item['tax_percent'] ?? 0)) / 100, 2);
            $shippingExtra += (float) ($item['product_shipping_cost'] ?? 0);
            $shippingExtra += (float) ($item['category_shipping_cost'] ?? 0);
        }
        $minimumOrderAmount = Settings::moduleFloat($this->moduleKey, 'minimum_order_amount');
        if ($subtotal < $minimumOrderAmount) {
            Response::json(['message' => 'Minimum order amount is ₹' . number_format($minimumOrderAmount, 2)], 422);
            return;
        }
        $paymentMethod = trim((string) ($body['payment_method'] ?? 'cash_on_delivery'));
        if (!PaymentMethodCatalog::isEnabled($paymentMethod, $this->moduleKey)) {
            Response::json(['message' => 'Selected payment method is not available'], 422);
            return;
        }
        $paymentReference = trim((string) ($body['payment_reference'] ?? ''));
        $paymentNote = trim((string) ($body['payment_note'] ?? ''));
        if (PaymentMethodCatalog::requiresReference($paymentMethod) && $paymentReference === '') {
            Response::json(['message' => 'Payment reference is required for this method'], 422);
            return;
        }
        $cartController = new CartController($this->moduleKey);
        $coupon = $cartController->appliedCoupon($guestId, $subtotal);
        $couponDiscount = $coupon ? $cartController->discount($coupon, $subtotal) : 0.0;
        $shippingMethod = ShippingSchema::method((int) ($body['shipping_method_id'] ?? 0), $this->moduleKey);
        $deliveryCharge = (float) $shippingMethod['cost'] + $shippingExtra;
        $total = max(0, $subtotal - $couponDiscount) + $taxTotal + $deliveryCharge;
        $vendorIds = array_values(array_unique(array_filter(array_map(static fn (array $item): int => (int) ($item['vendor_id'] ?? 0), $cart))));
        $orderVendorId = count($vendorIds) === 1 ? $vendorIds[0] : null;
        $customerId = (int) ($body['customer_id'] ?? 0);
        $customerToken = trim($body['customer_token'] ?? '');
        if ($customerToken !== '') {
            $customerStmt = $db->prepare('select id from customers where auth_token = :auth_token and status = 1 limit 1');
            $customerStmt->execute(['auth_token' => hash('sha256', $customerToken)]);
            $customer = $customerStmt->fetch();
            $customerId = $customer ? (int) $customer['id'] : 0;
        }
        if ($paymentMethod === 'wallet') {
            if ($customerId <= 0) {
                Response::json(['message' => 'Login is required to pay with wallet'], 422);
                return;
            }
            if (!WalletService::canDebit('customer', 'customer-' . $customerId, $total)) {
                Response::json(['message' => 'Insufficient wallet balance'], 422);
                return;
            }
        }
        $addressId = (int) ($body['address_id'] ?? 0);
        $customerName = trim($body['customer_name'] ?? '');
        $customerPhone = trim($body['customer_phone'] ?? '');
        $customerEmail = trim($body['customer_email'] ?? '');
        $address = trim($body['address'] ?? '');
        $substitutionPreference = $this->substitutionPreference($body);
        if ($customerId > 0 && $addressId > 0) {
            $addressStmt = $db->prepare('select customer_addresses.*, customers.email from customer_addresses join customers on customers.id = customer_addresses.customer_id where customer_addresses.id = :address_id and customer_addresses.customer_id = :customer_id limit 1');
            $addressStmt->execute(['address_id' => $addressId, 'customer_id' => $customerId]);
            $savedAddress = $addressStmt->fetch();
            if ($savedAddress) {
                $customerName = $savedAddress['contact_name'] ?: $customerName;
                $customerPhone = $savedAddress['contact_phone'] ?: $customerPhone;
                $customerEmail = $savedAddress['email'] ?: $customerEmail;
                $parts = array_filter([
                    $savedAddress['address'],
                    $savedAddress['city'],
                    $savedAddress['state'],
                    $savedAddress['pincode'],
                ]);
                $address = implode(', ', $parts);
            }
        }
        if ($this->moduleKey === 'medical') {
            foreach ($cart as $item) {
                $medicineError = $this->medicalItemError($item, $body, $guestId, $customerPhone);
                if ($medicineError !== '') {
                    Response::json(['message' => $medicineError], 422);
                    return;
                }
            }
        }

        $orderNumber = 'CSM' . date('ymdHis') . random_int(10, 99);
        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
            'insert into orders (module_key, zone_id, vendor_id, order_number, guest_id, customer_name, customer_phone, customer_email, address, order_amount, coupon_code, coupon_discount, tax_total, shipping_method_id, shipping_method_name, shipping_cost, expected_delivery, payment_method, payment_status, order_status, order_note, substitution_preference, created_at, updated_at)
             values (:module_key, :zone_id, :vendor_id, :order_number, :guest_id, :customer_name, :customer_phone, :customer_email, :address, :order_amount, :coupon_code, :coupon_discount, :tax_total, :shipping_method_id, :shipping_method_name, :shipping_cost, :expected_delivery, :payment_method, :payment_status, :order_status, :order_note, :substitution_preference, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
            'module_key' => $this->moduleKey,
            'zone_id' => $zoneId > 0 ? $zoneId : null,
            'vendor_id' => $orderVendorId,
            'order_number' => $orderNumber,
            'guest_id' => $guestId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'address' => $address,
            'order_amount' => $total,
            'coupon_code' => $coupon['code'] ?? null,
            'coupon_discount' => $couponDiscount,
            'tax_total' => $taxTotal,
            'shipping_method_id' => ((int) $shippingMethod['id']) > 0 ? (int) $shippingMethod['id'] : null,
            'shipping_method_name' => $shippingMethod['name'],
            'shipping_cost' => $deliveryCharge,
            'expected_delivery' => $shippingMethod['expected_days'] ?? null,
            'payment_method' => $paymentMethod,
            'payment_status' => PaymentMethodCatalog::paymentStatus($paymentMethod),
            'order_status' => $needsPrescription ? 'prescription_pending' : 'pending',
            'order_note' => $this->orderNote($body),
            'substitution_preference' => $substitutionPreference,
            ]);
            $orderId = (int) $db->lastInsertId();
            OrderStatusHistory::record($orderId, null, $needsPrescription ? 'prescription_pending' : 'pending', 'customer', $customerName, $needsPrescription ? 'Order placed, prescription review pending' : 'Order placed');
            if ($needsPrescription) {
                $filePath = Upload::base64Document(trim((string) ($body['prescription_file_base64'] ?? '')), 'prescriptions', 'order-' . $orderId);
                $prescription = $db->prepare(
                'insert into medical_prescriptions (order_id, guest_id, customer_name, customer_phone, reference, file_path, note, status, created_at, updated_at)
                 values (:order_id, :guest_id, :customer_name, :customer_phone, :reference, :file_path, :note, \'pending\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $prescription->execute([
                'order_id' => $orderId,
                'guest_id' => $guestId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'reference' => trim((string) ($body['prescription_reference'] ?? '')) ?: null,
                'file_path' => $filePath,
                'note' => trim((string) ($body['prescription_note'] ?? '')) ?: null,
                ]);
            }
            NotificationLog::record('admin', null, null, 'New ' . $this->moduleKey . ' order', $orderNumber . ' placed by ' . $customerName, $orderId, $this->moduleKey);
            NotificationLog::record('customer', null, $guestId, 'Order placed', 'Your order ' . $orderNumber . ' was placed successfully.', $orderId, $this->moduleKey);

            $paymentStmt = $db->prepare(
            'insert into payment_transactions (module_key, order_id, guest_id, customer_name, customer_phone, payment_method, amount, reference, note, status, created_at, updated_at)
             values (:module_key, :order_id, :guest_id, :customer_name, :customer_phone, :payment_method, :amount, :reference, :note, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $paymentStmt->execute([
            'module_key' => $this->moduleKey,
            'order_id' => $orderId,
            'guest_id' => $guestId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'payment_method' => $paymentMethod,
            'amount' => $total,
            'reference' => $paymentReference === '' ? null : $paymentReference,
            'note' => $paymentNote === '' ? null : $paymentNote,
            'status' => $paymentMethod === 'wallet' ? 'paid' : 'pending',
            ]);
            if ($paymentMethod === 'wallet') {
                WalletService::debit(
                'customer',
                'customer-' . $customerId,
                $total,
                'order_payment',
                'order:' . $orderId,
                'Wallet payment for ' . $orderNumber
                );
            }

            $itemStmt = $db->prepare(
            'insert into order_items (order_id, vendor_id, product_id, variant_id, variant_name, product_name, quantity, price, total, status, created_at, updated_at)
             values (:order_id, :vendor_id, :product_id, :variant_id, :variant_name, :product_name, :quantity, :price, :total, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            foreach ($cart as $item) {
                $itemStmt->execute([
                'order_id' => $orderId,
                'vendor_id' => $item['vendor_id'],
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'variant_name' => $item['variant_name'],
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => (float) $item['price'] * (int) $item['quantity'],
                'status' => 'pending',
                ]);
                OrderStatusHistory::record($orderId, (int) $db->lastInsertId(), 'pending', 'customer', $customerName, 'Item placed');
                if (!empty($item['vendor_id'])) {
                    NotificationLog::record('vendor', (int) $item['vendor_id'], null, 'New order item', $item['name'] . ' was ordered in ' . $orderNumber, $orderId, $this->moduleKey);
                }

                if ($item['variant_id']) {
                    $stockStmt = $db->prepare('update product_variants set stock = stock - :quantity, updated_at = CURRENT_TIMESTAMP where id = :id and stock >= :quantity');
                    $stockStmt->execute(['quantity' => $item['quantity'], 'id' => $item['variant_id']]);
                } else {
                    $stockStmt = $db->prepare('update products set stock = stock - :quantity, updated_at = CURRENT_TIMESTAMP where id = :id and stock >= :quantity');
                    $stockStmt->execute(['quantity' => $item['quantity'], 'id' => $item['product_id']]);
                }
                if ($stockStmt->rowCount() === 0) {
                    $db->rollBack();
                    Response::json(['message' => $item['name'] . ' stock changed while placing order. Please refresh cart.'], 409);
                    return;
                }
            }

            $clear = $db->prepare('delete from carts where guest_id = :guest_id and module_key = :module_key');
            $clear->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
            $clearCoupon = $db->prepare('delete from cart_coupons where guest_id = :guest_id and module_key = :module_key');
            $clearCoupon->execute(['guest_id' => $guestId, 'module_key' => $this->moduleKey]);
            if ($coupon) {
                $used = $db->prepare('update coupons set used_count = used_count + 1, updated_at = CURRENT_TIMESTAMP where id = :id');
                $used->execute(['id' => $coupon['id']]);
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($exception->getMessage() === 'Insufficient wallet balance') {
                Response::json(['message' => 'Insufficient wallet balance'], 422);
                return;
            }
            Response::json(['message' => 'Order could not be placed. Please try again.'], 500);
            return;
        }

        Response::json(['message' => 'Order placed', 'order_id' => $orderId, 'order_number' => $orderNumber]);
    }

    public function track(): void
    {
        $guestId = trim($_GET['guest_id'] ?? '');
        $customerToken = trim($_GET['customer_token'] ?? '');
        CouponSchema::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        OrderStatusHistory::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $orderNumber = trim($_GET['order_number'] ?? $_GET['order_id'] ?? '');
        $stmt = Database::connection()->prepare(
            'select orders.*, delivery_men.name as delivery_man_name, delivery_men.phone as delivery_man_phone, delivery_men.vehicle_type, delivery_men.vehicle_number
             from orders
             left join delivery_men on delivery_men.id = orders.delivery_man_id
             where orders.module_key = :module_key and (orders.order_number = :order_number or orders.id = :id)
             limit 1'
        );
        $stmt->execute(['module_key' => $this->moduleKey, 'order_number' => $orderNumber, 'id' => (int) $orderNumber]);
        $order = $stmt->fetch();
        if (!$order) {
            Response::json(['message' => 'Order not found'], 404);
            return;
        }
        if (!$this->canAccessOrder($order, $guestId, $customerToken)) {
            Response::json(['message' => 'Order access denied'], 403);
            return;
        }

        Response::json(['data' => $this->decorateOrder($order)]);
    }

    public function show(int $id): void
    {
        $guestId = trim($_GET['guest_id'] ?? '');
        $customerToken = trim($_GET['customer_token'] ?? '');
        CouponSchema::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        OrderStatusHistory::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $db = Database::connection();
        $stmt = $db->prepare(
            'select orders.*, delivery_men.name as delivery_man_name, delivery_men.phone as delivery_man_phone, delivery_men.vehicle_type, delivery_men.vehicle_number
             from orders
             left join delivery_men on delivery_men.id = orders.delivery_man_id
             where orders.id = :id and orders.module_key = :module_key'
        );
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $order = $stmt->fetch();
        if (!$order) {
            Response::json(['message' => 'Order not found'], 404);
            return;
        }
        if (!$this->canAccessOrder($order, $guestId, $customerToken)) {
            Response::json(['message' => 'Order access denied'], 403);
            return;
        }

        $items = $db->prepare(
            'select order_items.*, products.is_digital, products.digital_file_url
             from order_items
             left join products on products.id = order_items.product_id
             where order_items.order_id = :id'
        );
        $items->execute(['id' => $id]);
        $orderItems = array_map(
            fn (array $item): array => $this->decorateDigitalItem($item, $order),
            $items->fetchAll()
        );
        $history = $db->prepare('select * from order_status_history where order_id = :id order by id asc');
        $history->execute(['id' => $id]);
        $refunds = $db->prepare('select refund_requests.*, order_items.product_name from refund_requests left join order_items on order_items.id = refund_requests.order_item_id where refund_requests.order_id = :id order by refund_requests.id desc');
        $refunds->execute(['id' => $id]);
        Response::json([
            'data' => $this->decorateOrder($order),
            'items' => $orderItems,
            'history' => $history->fetchAll(),
            'refunds' => $refunds->fetchAll(),
        ]);
    }

    public function cancel(int $id): void
    {
        $body = Request::json();
        CouponSchema::ensure();
        DeliverySchema::ensure();
        NotificationSchema::ensure();
        OrderStatusHistory::ensure();
        ProductExtrasSchema::ensure();
        VendorSchema::ensure();
        $guestId = trim($body['guest_id'] ?? $_GET['guest_id'] ?? '');
        $customerToken = trim($body['customer_token'] ?? '');
        $db = Database::connection();

        $stmt = $db->prepare('select * from orders where id = :id and module_key = :module_key limit 1');
        $stmt->execute(['id' => $id, 'module_key' => $this->moduleKey]);
        $order = $stmt->fetch();
        if (!$order) {
            Response::json(['message' => 'Order not found'], 404);
            return;
        }

        if (!$this->canAccessOrder($order, $guestId, $customerToken)) {
            Response::json(['message' => 'Order access denied'], 403);
            return;
        }

        if (!in_array($order['order_status'], ['pending', 'confirmed'], true)) {
            Response::json(['message' => 'Order can only be cancelled before processing'], 422);
            return;
        }

        $items = $db->prepare('select * from order_items where order_id = :id');
        $items->execute(['id' => $id]);
        $orderItems = $items->fetchAll();
        foreach ($orderItems as $item) {
            if (!in_array($item['status'] ?? 'pending', ['pending', 'confirmed'], true)) {
                Response::json(['message' => 'Order can only be cancelled before processing'], 422);
                return;
            }
        }

        try {
            $db->beginTransaction();
            foreach ($orderItems as $item) {
                $stock = $db->prepare('update products set stock = stock + :quantity, updated_at = CURRENT_TIMESTAMP where id = :id');
                if (!empty($item['variant_id'])) {
                    $stock = $db->prepare('update product_variants set stock = stock + :quantity, updated_at = CURRENT_TIMESTAMP where id = :id');
                    $stock->execute(['quantity' => (int) $item['quantity'], 'id' => (int) $item['variant_id']]);
                } else {
                    $stock->execute(['quantity' => (int) $item['quantity'], 'id' => (int) $item['product_id']]);
                }
            }
            $itemStatus = $db->prepare('update order_items set status = :status, updated_at = CURRENT_TIMESTAMP where order_id = :id');
            $itemStatus->execute(['status' => 'cancelled', 'id' => $id]);
            foreach ($orderItems as $item) {
                OrderStatusHistory::record($id, (int) $item['id'], 'cancelled', 'customer', (string) $order['customer_name'], 'Customer cancelled item');
            }
            $orderStatus = $db->prepare('update orders set order_status = :status, updated_at = CURRENT_TIMESTAMP where id = :id');
            $orderStatus->execute(['status' => 'cancelled', 'id' => $id]);
            OrderStatusHistory::record($id, null, 'cancelled', 'customer', (string) $order['customer_name'], 'Customer cancelled order');
            NotificationLog::record('admin', null, null, 'Order cancelled', (string) $order['order_number'] . ' was cancelled by the customer.', $id, $this->moduleKey);
            NotificationLog::record('customer', null, (string) $order['guest_id'], 'Order cancelled', 'Your order ' . $order['order_number'] . ' was cancelled.', $id, $this->moduleKey);
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::json(['message' => 'Order could not be cancelled. Please try again.'], 500);
            return;
        }

        Response::json(['message' => 'Order cancelled']);
    }

    private function canAccessOrder(array $order, string $guestId, string $customerToken): bool
    {
        if ($guestId !== '' && hash_equals((string) $order['guest_id'], $guestId)) {
            return true;
        }
        if ($customerToken === '') {
            return false;
        }

        $stmt = Database::connection()->prepare('select id from customers where auth_token = :auth_token and phone = :phone and status = 1 limit 1');
        $stmt->execute([
            'auth_token' => hash('sha256', $customerToken),
            'phone' => (string) $order['customer_phone'],
        ]);
        return (bool) $stmt->fetch();
    }

    private function decorateOrders(array $orders): array
    {
        if ($orders === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn (array $order): int => (int) $order['id'], $orders)));
        $refundMap = $this->refundMap($ids);

        return array_map(fn (array $order): array => $this->decorateOrder($order, $refundMap[(int) $order['id']] ?? null), $orders);
    }

    private function requiresPrescription(array $cart): bool
    {
        foreach ($cart as $item) {
            if (in_array((string) ($item['medicine_type'] ?? 'otc'), ['prescription_required', 'restricted'], true)) {
                return true;
            }
            if ((int) ($item['requires_pharmacist_review'] ?? 0) === 1) {
                return true;
            }
            $attributes = json_decode((string) ($item['attributes_json'] ?? '[]'), true);
            if (!is_array($attributes)) {
                continue;
            }
            foreach ($attributes as $attribute) {
                $value = strtolower((string) $attribute);
                if (str_contains($value, 'prescription') && (str_contains($value, 'required') || str_contains($value, 'yes'))) {
                    return true;
                }
            }
        }
        return false;
    }

    private function medicalItemError(array $item, array $body, string $guestId, string $customerPhone): string
    {
        $name = (string) ($item['name'] ?? 'Medicine');
        $medicineType = (string) ($item['medicine_type'] ?? 'otc');
        if ($medicineType === 'blocked_online') {
            return $name . ' is not available for online ordering. Please contact support or a licensed pharmacy.';
        }

        $maxQty = (int) ($item['max_qty_per_order'] ?? 0);
        if ($maxQty > 0 && (int) ($item['quantity'] ?? 0) > $maxQty) {
            return $name . ' has a maximum quantity limit of ' . $maxQty . ' per order';
        }
        $maxMonthlyQty = (int) ($item['max_qty_per_month'] ?? 0);
        if ($maxMonthlyQty > 0) {
            $usedQty = $this->monthlyMedicalQuantity(
                (int) ($item['product_id'] ?? 0),
                $guestId,
                $customerPhone
            );
            if ($usedQty + (int) ($item['quantity'] ?? 0) > $maxMonthlyQty) {
                return $name . ' has a monthly quantity limit of ' . $maxMonthlyQty;
            }
        }

        if ((int) ($item['requires_age_confirmation'] ?? 0) === 1) {
            $confirmed = $body['age_confirmed'] ?? false;
            if (!($confirmed === true || $confirmed === 1 || $confirmed === '1' || $confirmed === 'true')) {
                return 'Age confirmation is required for ' . $name;
            }
        }

        return '';
    }

    private function monthlyMedicalQuantity(int $productId, string $guestId, string $customerPhone): int
    {
        if ($productId <= 0) {
            return 0;
        }
        $db = Database::connection();
        $conditions = ['orders.module_key = \'medical\'', 'order_items.product_id = :product_id', 'orders.created_at >= :month_start', 'orders.order_status not in (\'cancelled\', \'refunded\', \'failed\', \'prescription_rejected\')'];
        $params = [
            'product_id' => $productId,
            'month_start' => date('Y-m-01 00:00:00'),
        ];
        if ($customerPhone !== '') {
            $conditions[] = 'orders.customer_phone = :customer_phone';
            $params['customer_phone'] = $customerPhone;
        } else {
            $conditions[] = 'orders.guest_id = :guest_id';
            $params['guest_id'] = $guestId;
        }

        $stmt = $db->prepare(
            'select coalesce(sum(order_items.quantity), 0) as total_quantity
             from order_items
             join orders on orders.id = order_items.order_id
             where ' . implode(' and ', $conditions)
        );
        $stmt->execute($params);
        return (int) ($stmt->fetch()['total_quantity'] ?? 0);
    }

    private function orderNote(array $body): string
    {
        $note = trim((string) ($body['order_note'] ?? ''));
        if ($this->moduleKey !== 'medical') {
            return $note;
        }
        $prescription = trim((string) ($body['prescription_reference'] ?? $body['prescription_note'] ?? ''));
        if ($prescription === '') {
            return $note;
        }
        return trim($note . "\nPrescription: " . $prescription);
    }

    private function substitutionPreference(array $body): string
    {
        $preference = trim((string) ($body['substitution_preference'] ?? 'call_before_replace'));
        return in_array($preference, ['call_before_replace', 'auto_replace', 'no_replacement'], true)
            ? $preference
            : 'call_before_replace';
    }

    private function decorateOrder(array $order, ?array $refund = null): array
    {
        $refund ??= $this->refundMap([(int) $order['id']])[(int) $order['id']] ?? null;
        if ($refund) {
            $status = match ((string) $refund['status']) {
                'pending' => 'refund_pending',
                'approved' => 'refund_approved',
                'rejected' => 'refund_rejected',
                'refunded' => 'refunded',
                default => (string) $order['order_status'],
            };
            $order['refund_status'] = (string) $refund['status'];
            $order['display_status'] = $status;
        } else {
            $order['refund_status'] = null;
            $order['display_status'] = (string) $order['order_status'];
        }

        return $order;
    }

    private function decorateDigitalItem(array $item, array $order): array
    {
        $canDownload = (int) ($item['is_digital'] ?? 0) === 1
            && in_array((string) ($order['payment_status'] ?? ''), ['paid', 'verified'], true);
        $item['digital_file_url'] = $canDownload ? (string) ($item['digital_file_url'] ?? '') : '';
        return $item;
    }

    private function refundMap(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = Database::connection()->prepare(
            'select rr.order_id, rr.status, rr.updated_at
             from refund_requests rr
             join (
                select order_id, max(id) as latest_id
                from refund_requests
                where order_id in (' . $placeholders . ')
                group by order_id
             ) latest on latest.latest_id = rr.id'
        );
        $stmt->execute($orderIds);
        $map = [];
        foreach ($stmt->fetchAll() as $refund) {
            $map[(int) $refund['order_id']] = $refund;
        }

        return $map;
    }
}
