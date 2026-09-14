<section class="grid">
    <div class="card stat"><span>Wallet Balance</span><strong>₹<?= number_format((float) $account['balance'], 2) ?></strong></div>
</section>

<section class="card">
    <h2>Request Withdrawal</h2>
    <form method="post" action="/vendor/wallet/withdrawals">
        <div class="row">
            <div><label>Amount</label><input name="amount" type="number" min="0" step="0.01" required></div>
            <div><label>Bank Details</label><textarea name="bank_details" placeholder="Bank name, account number, IFSC"></textarea></div>
            <div><label>Note</label><textarea name="note" placeholder="Optional note"></textarea></div>
        </div>
        <div style="height:12px;"></div><button>Submit Request</button>
    </form>
</section>

<section class="card">
    <h2>Ledger</h2>
    <table>
        <thead><tr><th>Type</th><th>Direction</th><th>Amount</th><th>Description</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($ledger as $entry): ?>
            <tr>
                <td><?= htmlspecialchars($entry['entry_type']) ?></td>
                <td><?= htmlspecialchars($entry['direction']) ?></td>
                <td>₹<?= number_format((float) $entry['amount'], 2) ?></td>
                <td><?= htmlspecialchars((string) ($entry['description'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string) ($entry['created_at'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Settlements</h2>
    <table>
        <thead><tr><th>Amount</th><th>Reference</th><th>Status</th><th>Settled</th><th>Note</th></tr></thead>
        <tbody>
        <?php foreach (($settlements ?? []) as $settlement): ?>
            <tr>
                <td>₹<?= number_format((float) $settlement['amount'], 2) ?></td>
                <td><?= htmlspecialchars((string) ($settlement['payment_reference'] ?? '-')) ?></td>
                <td><span class="pill"><?= htmlspecialchars($settlement['status']) ?></span></td>
                <td><?= htmlspecialchars((string) ($settlement['settled_at'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string) ($settlement['note'] ?? '-')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Withdrawals</h2>
    <table>
        <thead><tr><th>Amount</th><th>Status</th><th>Bank Details</th><th>Admin Note</th></tr></thead>
        <tbody>
        <?php foreach ($withdrawals as $withdrawal): ?>
            <tr>
                <td>₹<?= number_format((float) $withdrawal['amount'], 2) ?></td>
                <td><span class="pill"><?= htmlspecialchars($withdrawal['status']) ?></span></td>
                <td><?= nl2br(htmlspecialchars((string) ($withdrawal['bank_details'] ?? '-'))) ?></td>
                <td><?= htmlspecialchars((string) ($withdrawal['admin_note'] ?? '-')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
