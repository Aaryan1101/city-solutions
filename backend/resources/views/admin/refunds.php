<section class="card">
    <h2>Refund Requests</h2>
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Item</th><th>Amount</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($refunds as $refund): ?>
            <tr>
                <td><?= htmlspecialchars($refund['order_number']) ?><?php if (!empty($refund['vendor_name'])): ?><br><small><?= htmlspecialchars($refund['vendor_name']) ?></small><?php endif; ?></td>
                <td><?= htmlspecialchars($refund['customer_name'] ?? '') ?><br><small><?= htmlspecialchars($refund['customer_phone'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($refund['product_name'] ?? 'Full order') ?></td>
                <td>₹<?= number_format((float) $refund['amount'], 2) ?></td>
                <td><?= htmlspecialchars($refund['reason']) ?><?php if (!empty($refund['note'])): ?><br><small><?= htmlspecialchars($refund['note']) ?></small><?php endif; ?></td>
                <td><span class="pill"><?= htmlspecialchars($refund['status']) ?></span><?php if (!empty($refund['admin_note'])): ?><br><small><?= htmlspecialchars($refund['admin_note']) ?></small><?php endif; ?></td>
                <td>
                    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/refunds') ?>/<?= $refund['id'] ?>/status">
                        <select name="status">
                            <?php foreach (['pending', 'approved', 'rejected', 'refunded'] as $status): ?>
                                <option value="<?= $status ?>" <?= $refund['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Admin Note</label>
                        <input name="admin_note" value="<?= htmlspecialchars($refund['admin_note'] ?? '') ?>">
                        <div style="height:8px;"></div><button>Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
