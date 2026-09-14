<section class="card">
    <h2>Medical Prescription Review</h2>
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Prescription</th><th>Status</th><th>Review</th></tr></thead>
        <tbody>
        <?php foreach ($prescriptions as $item): ?>
            <tr>
                <td><strong><?= htmlspecialchars($item['order_number']) ?></strong><br><small><?= htmlspecialchars($item['order_status']) ?> / <?= htmlspecialchars($item['payment_status']) ?></small></td>
                <td><?= htmlspecialchars((string) $item['customer_name']) ?><br><small><?= htmlspecialchars((string) $item['customer_phone']) ?></small></td>
                <td>
                    <?php if (!empty($item['reference'])): ?><div><strong>Reference:</strong> <?= htmlspecialchars($item['reference']) ?></div><?php endif; ?>
                    <?php if (!empty($item['note'])): ?><div><?= nl2br(htmlspecialchars($item['note'])) ?></div><?php endif; ?>
                    <?php if (!empty($item['file_path'])): ?><a class="btn secondary" href="<?= htmlspecialchars($item['file_path']) ?>" target="_blank">Open File</a><?php endif; ?>
                    <?php if (empty($item['reference']) && empty($item['note']) && empty($item['file_path'])): ?><span class="pill">No file</span><?php endif; ?>
                    <?php if (!empty($item['safety_flags'])): ?>
                        <div style="margin-top:10px;">
                            <strong>Safety flags</strong>
                            <ul style="margin:6px 0 0 18px; padding:0;">
                                <?php foreach ($item['safety_flags'] as $flag): ?>
                                    <li><?= htmlspecialchars((string) $flag) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </td>
                <td><span class="pill"><?= htmlspecialchars($item['status']) ?></span><br><small><?= htmlspecialchars((string) ($item['reviewed_by'] ?? '')) ?></small></td>
                <td>
                    <form method="post" action="/admin/medical/prescriptions/<?= (int) $item['id'] ?>/status">
                        <select name="status">
                            <?php foreach (['pending', 'approved', 'rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="admin_note" value="<?= htmlspecialchars((string) ($item['admin_note'] ?? '')) ?>" placeholder="Review note">
                        <button>Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
