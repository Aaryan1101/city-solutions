<section class="card">
    <h2>Payment Reconciliation</h2>
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Method</th><th>Amount</th><th>Reference</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= htmlspecialchars($payment['order_number']) ?></td>
                <td><?= htmlspecialchars($payment['customer_name'] ?? '') ?><br><small><?= htmlspecialchars($payment['customer_phone'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($payment['payment_method']) ?><br><small>Order: <?= htmlspecialchars($payment['payment_status']) ?></small></td>
                <td>₹<?= number_format((float) $payment['amount'], 2) ?></td>
                <td><?= htmlspecialchars($payment['reference'] ?? '-') ?><?php if (!empty($payment['note'])): ?><br><small><?= htmlspecialchars($payment['note']) ?></small><?php endif; ?></td>
                <td>
                    <span class="pill"><?= htmlspecialchars($payment['status']) ?></span>
                    <?php if (!empty($payment['admin_note'])): ?><br><small><?= nl2br(htmlspecialchars($payment['admin_note'])) ?></small><?php endif; ?>
                    <?php if (!empty($payment['gateway_response'])): ?><br><small>Gateway response saved</small><?php endif; ?>
                </td>
                <td>
                    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/payments') ?>/<?= $payment['id'] ?>/status">
                        <select name="status">
                            <?php foreach (['pending', 'paid', 'rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= $payment['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Admin Note</label>
                        <input name="admin_note" value="<?= htmlspecialchars($payment['admin_note'] ?? '') ?>">
                        <div style="height:8px;"></div>
                        <button name="gateway_action" value="">Save</button>
                        <button class="btn secondary" name="gateway_action" value="status">Verify Gateway</button>
                        <button class="btn secondary" name="gateway_action" value="capture">Capture Gateway</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
