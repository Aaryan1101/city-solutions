<section class="card">
    <h2>Vendor Orders</h2>
    <table>
        <thead><tr><th>Order</th><th>Module</th><th>Customer</th><th>Item</th><th>Address</th><th>Delivery</th><th>Item Total</th><th>Item Status</th><th>Overall</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['order_number']) ?><br><small><?= htmlspecialchars($item['created_at'] ?? '') ?></small></td>
                <td><span class="pill"><?= htmlspecialchars(($item['module_key'] ?? 'mart') === 'ecommerce' ? 'E-Commerce' : 'Mart') ?></span></td>
                <td><?= htmlspecialchars($item['customer_name']) ?><br><small><?= htmlspecialchars($item['customer_phone']) ?></small></td>
                <td><?= htmlspecialchars($item['product_name']) ?><?php if (!empty($item['variant_name'])): ?><br><small><?= htmlspecialchars($item['variant_name']) ?></small><?php endif; ?><br><small>Qty: <?= (int) $item['quantity'] ?> × ₹<?= number_format((float) $item['price'], 2) ?></small></td>
                <td><?= htmlspecialchars($item['address']) ?></td>
                <td><?= htmlspecialchars($item['delivery_man_name'] ?? 'Unassigned') ?><?php if (!empty($item['delivery_man_phone'])): ?><br><small><?= htmlspecialchars($item['delivery_man_phone']) ?></small><?php endif; ?></td>
                <td>₹<?= number_format((float) $item['total'], 2) ?></td>
                <td>
                    <form method="post" action="/vendor/order-items/<?= $item['item_id'] ?>/status">
                        <select name="status">
                            <?php foreach (['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled'] as $status): ?>
                                <option value="<?= $status ?>" <?= ($item['item_status'] ?? 'pending') === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button style="margin-top:8px;">Update</button>
                    </form>
                </td>
                <td><span class="pill"><?= htmlspecialchars($item['order_status']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
