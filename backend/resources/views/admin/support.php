<section class="card">
    <h2>Support Threads</h2>
    <?php
    $moduleLabels = [
        'mart' => 'Mart',
        'ecommerce' => 'E-Commerce',
        'medical' => 'Medical',
        'services' => 'Services',
        'hotel' => 'Hotel',
        'restaurant' => 'Restaurant',
        'real_estate' => 'Real Estate',
    ];
    ?>
    <table>
        <thead><tr><th>Subject</th><th>Module</th><th>Reference</th><th>Customer</th><th>Vendor</th><th>Status</th><th>Unread</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($threads as $thread): ?>
            <tr>
                <td><?= htmlspecialchars($thread['subject']) ?></td>
                <td><span class="pill"><?= htmlspecialchars($moduleLabels[$thread['module_key'] ?? 'mart'] ?? 'Global') ?></span></td>
                <td>
                    <?php if (!empty($thread['booking_id'])): ?><small>Booking #<?= (int) $thread['booking_id'] ?></small><?php endif; ?>
                    <?php if (!empty($thread['order_id'])): ?><br><small>Order #<?= (int) $thread['order_id'] ?></small><?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) ($thread['guest_id'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string) ($thread['vendor_name'] ?? 'Unassigned')) ?></td>
                <td><span class="pill"><?= htmlspecialchars($thread['status']) ?></span></td>
                <td><?= (int) ($thread['unread_count'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string) ($thread['created_at'] ?? '')) ?></td>
                <td><a class="btn secondary" href="/admin/support/<?= $thread['id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<script>
setInterval(function () {
    if (!document.querySelector('input:focus, textarea:focus, select:focus')) {
        window.location.reload();
    }
}, 15000);
</script>
