<section class="card">
    <h2><?= htmlspecialchars($thread['subject'] ?? 'Support Thread') ?></h2>
    <p>
        <span class="pill"><?= htmlspecialchars(($thread['module_key'] ?? 'mart') === 'services' ? 'Services' : (($thread['module_key'] ?? 'mart') === 'ecommerce' ? 'E-Commerce' : 'Mart')) ?></span>
        <?php if (!empty($thread['booking_id'])): ?><span class="pill">Booking #<?= (int) $thread['booking_id'] ?></span><?php endif; ?>
        <?php if (!empty($thread['order_id'])): ?><span class="pill">Order #<?= (int) $thread['order_id'] ?></span><?php endif; ?>
    </p>
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
    <h2>Vendor Assignment</h2>
    <form method="post" action="/admin/support/<?= $thread['id'] ?>/assign-vendor">
        <label>Assigned Vendor</label>
        <select name="vendor_id">
            <option value="0">Unassigned</option>
            <?php foreach (($vendors ?? []) as $vendor): ?>
                <option value="<?= $vendor['id'] ?>" <?= (int) ($thread['vendor_id'] ?? 0) === (int) $vendor['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($vendor['shop_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div style="height:12px;"></div><button>Save Vendor Assignment</button>
    </form>
</section>

<section class="card">
    <h2>Reply</h2>
    <form method="post" action="/admin/support/<?= $thread['id'] ?>/reply">
        <label>Status</label>
        <select name="status">
            <?php foreach (['open', 'pending_customer', 'closed'] as $status): ?>
                <option value="<?= $status ?>" <?= ($thread['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Message</label><textarea name="message" required></textarea>
        <label>Attachment URL</label><input name="attachment_url" placeholder="https://...">
        <div style="height:12px;"></div><button>Send Reply</button>
    </form>
</section>
