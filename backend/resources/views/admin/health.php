<?php
$groups = [];
$total = 0;
$actionNeeded = 0;
foreach ($checks as $check) {
    $groups[$check['group']][] = $check;
    $total++;
    if (($check['status'] ?? '') !== 'ok') {
        $actionNeeded++;
    }
}
?>
<section class="card">
    <h2>Production Readiness Summary</h2>
    <div class="grid">
        <div class="stat"><strong><?= (int) $total ?></strong><span>Total checks</span></div>
        <div class="stat"><strong><?= (int) ($total - $actionNeeded) ?></strong><span>Ready</span></div>
        <div class="stat"><strong><?= (int) $actionNeeded ?></strong><span>Needs action</span></div>
    </div>
    <p style="color:var(--muted);">Use this page before launch after uploading backend files and importing any required demo/seed data.</p>
    <p style="color:var(--muted);">
        Payment webhooks must send <code>X-City-Signature</code> or <code>X-Webhook-Signature</code> as HMAC-SHA256 of the raw JSON body using the module webhook secret. The signature can be raw hex or <code>sha256=&lt;hex&gt;</code>.
        Orders accept <code>order_id</code>/<code>order_number</code>; bookings accept <code>booking_id</code>/<code>booking_number</code>.
        Success statuses are <code>paid</code>, <code>captured</code>, <code>success</code>, <code>succeeded</code>, or <code>verified</code>; refund status is <code>refunded</code>.
    </p>
</section>
<?php foreach ($groups as $group => $items): ?>
<section class="card">
    <h2><?= htmlspecialchars($group) ?></h2>
    <table>
        <thead><tr><th>Check</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                    <?php if (!empty($item['meta'])): ?>
                        <br><small>
                            <?php foreach ($item['meta'] as $key => $value): ?>
                                <?= htmlspecialchars((string) $key) ?>: <?= htmlspecialchars((string) $value) ?>
                            <?php endforeach; ?>
                        </small>
                    <?php endif; ?>
                </td>
                <td><span class="pill"><?= htmlspecialchars($item['status']) ?></span></td>
                <td><?= $item['fix'] === '' ? 'Ready' : htmlspecialchars($item['fix']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endforeach; ?>
