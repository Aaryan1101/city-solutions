<section class="card">
    <h2>Complaints</h2>
    <table>
        <thead><tr><th>Complaint</th><th>Route</th><th>Category</th><th>Location</th><th>Description</th><th>Photo</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($complaints as $complaint): ?>
            <tr>
                <td><?= htmlspecialchars($complaint['complaint_number']) ?><br><small><?= htmlspecialchars((string) ($complaint['created_at'] ?? '')) ?></small></td>
                <td>
                    <span class="pill"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($complaint['module_key'] ?? 'global')))) ?></span>
                    <br><small>Severity: <?= htmlspecialchars((string) ($complaint['severity'] ?? 'normal')) ?></small>
                    <?php if (!empty($complaint['customer_name']) || !empty($complaint['customer_phone'])): ?><br><small><?= htmlspecialchars(trim((string) ($complaint['customer_name'] ?? '') . ' ' . (string) ($complaint['customer_phone'] ?? ''))) ?></small><?php endif; ?>
                    <?php foreach (['zone_id' => 'Zone', 'order_id' => 'Order', 'booking_id' => 'Booking', 'vendor_id' => 'Vendor', 'provider_id' => 'Provider', 'hotel_id' => 'Hotel', 'real_estate_property_id' => 'Property', 'real_estate_agent_id' => 'Agent'] as $field => $label): ?>
                        <?php if (!empty($complaint[$field])): ?><br><small><?= $label ?> #<?= (int) $complaint[$field] ?></small><?php endif; ?>
                    <?php endforeach; ?>
                </td>
                <td><?= htmlspecialchars($complaint['category']) ?></td>
                <td><?= htmlspecialchars($complaint['location']) ?></td>
                <td><?= nl2br(htmlspecialchars((string) ($complaint['description'] ?? ''))) ?></td>
                <td>
                    <?php if (!empty($complaint['image_path'])): ?>
                        <a class="btn secondary" href="/<?= htmlspecialchars($complaint['image_path']) ?>" target="_blank">Open</a>
                    <?php else: ?>
                        <small>No photo</small>
                    <?php endif; ?>
                </td>
                <td><span class="pill"><?= htmlspecialchars($complaint['status']) ?></span><br><small><?= htmlspecialchars((string) ($complaint['admin_note'] ?? '')) ?></small></td>
                <td>
                    <form method="post" action="/admin/complaints/<?= $complaint['id'] ?>/status">
                        <select name="status">
                            <?php foreach (['pending', 'in_review', 'resolved', 'rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= $complaint['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="admin_note" placeholder="Admin note"><?= htmlspecialchars((string) ($complaint['admin_note'] ?? '')) ?></textarea>
                        <button>Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
