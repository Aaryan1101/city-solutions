<section class="card">
    <h2>Assigned Support Threads</h2>
    <table>
        <thead><tr><th>Subject</th><th>Status</th><th>Unread</th><th>Updated</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($threads as $thread): ?>
            <tr>
                <td><?= htmlspecialchars($thread['subject']) ?></td>
                <td><span class="pill"><?= htmlspecialchars($thread['status']) ?></span></td>
                <td><?= (int) ($thread['unread_count'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string) ($thread['updated_at'] ?? $thread['created_at'] ?? '')) ?></td>
                <td><a class="btn secondary" href="/vendor/support/<?= $thread['id'] ?>">Open</a></td>
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
