<section class="card">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:center;">
        <h2>Admin Notifications</h2>
        <form method="post" action="/admin/notifications/read"><button>Mark All Read</button></form>
    </div>
    <table>
        <thead><tr><th>Title</th><th>Message</th><th>Order</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>
        <?php foreach ($notifications as $notification): ?>
            <tr>
                <td><?= htmlspecialchars($notification['title']) ?></td>
                <td><?= htmlspecialchars($notification['message'] ?? '') ?></td>
                <td>
                    <?php if (!empty($notification['order_id'])): ?>
                        <a class="btn secondary" href="/admin/orders/<?= $notification['order_id'] ?>">View Order</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><span class="pill"><?= empty($notification['read_at']) ? 'Unread' : 'Read' ?></span></td>
                <td><?= htmlspecialchars($notification['created_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
