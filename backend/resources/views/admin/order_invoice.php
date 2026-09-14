<section class="card">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px;">
        <div>
            <h2>Invoice</h2>
            <p><strong>Order:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
            <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?>, <?= htmlspecialchars($order['customer_phone']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
        </div>
        <div style="text-align:right;">
            <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/orders') ?>/<?= $order['id'] ?>">Back</a>
            <button type="button" onclick="window.print()">Print</button>
        </div>
    </div>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?><?php if (!empty($item['variant_name'])): ?><br><small><?= htmlspecialchars($item['variant_name']) ?></small><?php endif; ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td>₹<?= number_format((float) $item['price'], 2) ?></td>
                <td>₹<?= number_format((float) $item['total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ((float) ($order['coupon_discount'] ?? 0) > 0): ?>
        <p><strong>Coupon Discount:</strong> ₹<?= number_format((float) $order['coupon_discount'], 2) ?></p>
    <?php endif; ?>
    <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?> / <?= htmlspecialchars($order['payment_status']) ?></p>
    <p><strong>Order Status:</strong> <?= htmlspecialchars($order['order_status']) ?></p>
    <p><strong>Grand Total:</strong> ₹<?= number_format((float) $order['order_amount'], 2) ?></p>
</section>
