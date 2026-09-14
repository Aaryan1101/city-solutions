<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Support\Auth;
use App\Support\Database;
use App\Support\OrderStatusHistory;
use App\Support\PaymentSchema;
use App\Support\ProductExtrasSchema;
use App\Support\Response;
use App\Support\View;

final class PosController
{
    public function index(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        $products = Database::connection()->query(
            'select id, name, price, discount_price, stock, tax_percent from products where status = 1 order by name asc'
        )->fetchAll();
        View::render('admin/pos', ['title' => 'POS', 'products' => $products]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        ProductExtrasSchema::ensure();
        PaymentSchema::ensure();
        OrderStatusHistory::ensure();
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $db = Database::connection();
        $stmt = $db->prepare('select * from products where id = :id and status = 1 limit 1');
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();
        if (!$product || (int) $product['stock'] < $quantity) {
            Response::redirect('/admin/pos');
        }

        $price = (float) ($product['discount_price'] ?: $product['price']);
        $subtotal = $price * $quantity;
        $taxTotal = round(($subtotal * (float) ($product['tax_percent'] ?? 0)) / 100, 2);
        $total = $subtotal + $taxTotal;
        $orderNumber = 'POS' . date('ymdHis') . random_int(10, 99);
        try {
            $db->beginTransaction();
            $order = $db->prepare(
                'insert into orders (order_number, guest_id, customer_name, customer_phone, address, order_amount, tax_total, payment_method, payment_status, order_status, order_note, created_at, updated_at)
                 values (:order_number, :guest_id, :customer_name, :customer_phone, :address, :order_amount, :tax_total, \'pos_cash\', \'paid\', \'delivered\', :order_note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $order->execute([
                'order_number' => $orderNumber,
                'guest_id' => 'pos',
                'customer_name' => trim($_POST['customer_name'] ?? 'Walk-in Customer'),
                'customer_phone' => trim($_POST['customer_phone'] ?? ''),
                'address' => 'POS Counter',
                'order_amount' => $total,
                'tax_total' => $taxTotal,
                'order_note' => trim($_POST['order_note'] ?? 'POS order'),
            ]);
            $orderId = (int) $db->lastInsertId();
            $item = $db->prepare(
                'insert into order_items (order_id, vendor_id, product_id, product_name, quantity, price, total, status, created_at, updated_at)
                 values (:order_id, :vendor_id, :product_id, :product_name, :quantity, :price, :total, \'delivered\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $item->execute([
                'order_id' => $orderId,
                'vendor_id' => $product['vendor_id'] ?? null,
                'product_id' => $productId,
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'price' => $price,
                'total' => $subtotal,
            ]);
            $stock = $db->prepare('update products set stock = stock - :quantity, updated_at = CURRENT_TIMESTAMP where id = :id and stock >= :quantity');
            $stock->execute(['quantity' => $quantity, 'id' => $productId]);
            if ($stock->rowCount() === 0) {
                $db->rollBack();
                Response::redirect('/admin/pos');
            }
            OrderStatusHistory::record($orderId, null, 'delivered', 'admin', 'POS', 'POS order completed');
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::redirect('/admin/pos');
        }

        Response::redirect('/admin/orders/' . $orderId);
    }
}
