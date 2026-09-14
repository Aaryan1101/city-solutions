<section class="card">
    <h2>Wallet Accounts</h2>
    <table>
        <thead><tr><th>Owner</th><th>Key</th><th>Balance</th><th>Updated</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $account): ?>
            <tr>
                <td><?= htmlspecialchars($account['owner_type']) ?></td>
                <td><?= htmlspecialchars($account['owner_key']) ?></td>
                <td>₹<?= number_format((float) $account['balance'], 2) ?></td>
                <td><?= htmlspecialchars((string) ($account['updated_at'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Withdrawal Requests</h2>
    <table>
        <thead><tr><th>Owner</th><th>Amount</th><th>Bank Details</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($withdrawals as $withdrawal): ?>
            <tr>
                <td>
                    <?php if (($withdrawal['owner_type'] ?? 'vendor') === 'customer'): ?>
                        <?= htmlspecialchars($withdrawal['customer_name'] ?? ('Customer ' . ($withdrawal['owner_key'] ?? ''))) ?><br>
                        <small><?= htmlspecialchars((string) ($withdrawal['customer_phone'] ?? $withdrawal['owner_key'] ?? '')) ?></small>
                    <?php elseif (($withdrawal['owner_type'] ?? 'vendor') === 'hotel_owner'): ?>
                        <?= htmlspecialchars($withdrawal['hotel_owner_name'] ?? ('Hotel Owner ' . ($withdrawal['owner_key'] ?? ''))) ?><br>
                        <small><?= htmlspecialchars((string) ($withdrawal['hotel_owner_phone'] ?? $withdrawal['owner_key'] ?? '')) ?></small>
                    <?php else: ?>
                        <?= htmlspecialchars($withdrawal['shop_name'] ?? ('Vendor #' . $withdrawal['vendor_id'])) ?>
                    <?php endif; ?>
                </td>
                <td>₹<?= number_format((float) $withdrawal['amount'], 2) ?></td>
                <td><?= nl2br(htmlspecialchars((string) ($withdrawal['bank_details'] ?? '-'))) ?><?php if (!empty($withdrawal['note'])): ?><br><small><?= htmlspecialchars($withdrawal['note']) ?></small><?php endif; ?></td>
                <td><span class="pill"><?= htmlspecialchars($withdrawal['status']) ?></span><?php if (!empty($withdrawal['admin_note'])): ?><br><small><?= htmlspecialchars($withdrawal['admin_note']) ?></small><?php endif; ?></td>
                <td>
                    <form method="post" action="/admin/wallets/withdrawals/<?= $withdrawal['id'] ?>/status">
                        <select name="status">
                            <?php foreach (['pending', 'approved', 'paid', 'rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= $withdrawal['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Admin Note</label>
                        <input name="admin_note" value="<?= htmlspecialchars((string) ($withdrawal['admin_note'] ?? '')) ?>">
                        <label>Payment Reference</label>
                        <input name="payment_reference" placeholder="Bank/UPI reference">
                        <div style="height:8px;"></div><button>Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
