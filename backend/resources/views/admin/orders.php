<section class="card">
    <h2>Orders</h2>
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Address</th><th>Amount</th><th>Payment</th><th>Delivery</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['order_number']) ?></td>
                <td><?= htmlspecialchars($order['customer_name']) ?><br><small><?= htmlspecialchars($order['customer_phone']) ?></small></td>
                <td><?= htmlspecialchars($order['address']) ?></td>
                <td>₹<?= number_format((float) $order['order_amount'], 2) ?></td>
                <td><?= htmlspecialchars($order['payment_method']) ?><br><small><?= htmlspecialchars($order['payment_status']) ?></small></td>
                <td><?= htmlspecialchars($order['delivery_man_name'] ?? 'Unassigned') ?></td>
                <td><span class="pill"><?= htmlspecialchars($order['display_status'] ?? $order['order_status']) ?></span></td>
                <td><a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/orders') ?>/<?= $order['id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
