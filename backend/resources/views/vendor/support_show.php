<section class="card">
    <h2><?= htmlspecialchars($thread['subject'] ?? 'Support Thread') ?></h2>
    <?php foreach (($messages ?? []) as $message): ?>
        <div style="border-bottom:1px solid var(--line); padding:10px 0;">
            <strong><?= htmlspecialchars($message['sender_type']) ?><?= $message['sender_name'] ? ' - ' . htmlspecialchars($message['sender_name']) : '' ?></strong>
            <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
            <?php if (!empty($message['attachment_url'])): ?><p><a class="btn secondary" href="<?= htmlspecialchars($message['attachment_url']) ?>" target="_blank">Open Attachment</a></p><?php endif; ?>
            <small><?= htmlspecialchars((string) ($message['created_at'] ?? '')) ?></small>
        </div>
    <?php endforeach; ?>
</section>
<script>
setInterval(function () {
    if (!document.querySelector('input:focus, textarea:focus, select:focus')) {
        window.location.reload();
    }
}, 10000);
</script>

<section class="card">
    <h2>Reply</h2>
    <form method="post" action="/vendor/support/<?= $thread['id'] ?>/reply">
        <label>Message</label><textarea name="message" required></textarea>
        <label>Attachment URL</label><input name="attachment_url" placeholder="https://...">
        <div style="height:12px;"></div><button>Send Reply</button>
    </form>
</section>
