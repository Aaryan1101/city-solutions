<section class="card">
    <h2>Refund Requests</h2>
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Item</th><th>Amount</th><th>Reason</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($refunds as $refund): ?>
            <tr>
                <td><?= htmlspecialchars($refund['order_number']) ?></td>
                <td><?= htmlspecialchars($refund['customer_name'] ?? '') ?><br><small><?= htmlspecialchars($refund['customer_phone'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($refund['product_name'] ?? 'Full order') ?></td>
                <td>₹<?= number_format((float) $refund['amount'], 2) ?></td>
                <td><?= htmlspecialchars($refund['reason']) ?><?php if (!empty($refund['note'])): ?><br><small><?= htmlspecialchars($refund['note']) ?></small><?php endif; ?></td>
                <td><span class="pill"><?= htmlspecialchars($refund['status']) ?></span><?php if (!empty($refund['admin_note'])): ?><br><small><?= htmlspecialchars($refund['admin_note']) ?></small><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
