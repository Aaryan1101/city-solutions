<section class="card">
    <h2>Vendor Applications</h2>
    <table>
        <thead><tr><th>Module</th><th>Shop</th><th>Owner</th><th>Contact</th><th>Address</th><th>Zone</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($vendors as $vendor): ?>
            <tr>
                <td><span class="pill"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $vendor['module_key'] ?? 'mart'))) ?></span></td>
                <td><?= htmlspecialchars($vendor['shop_name']) ?></td>
                <td><?= htmlspecialchars($vendor['owner_name']) ?></td>
                <td><?= htmlspecialchars($vendor['phone']) ?><br><small><?= htmlspecialchars($vendor['email'] ?? '') ?></small></td>
                <td><?= htmlspecialchars(trim(($vendor['address'] ?? '') . ' ' . ($vendor['city'] ?? ''))) ?></td>
                <td><?= htmlspecialchars((string) ($vendor['zone_name'] ?? 'All zones')) ?></td>
                <td><span class="pill"><?= htmlspecialchars($vendor['status']) ?></span></td>
                <td>
                    <form method="post" action="/admin/vendors/<?= $vendor['id'] ?>/status">
                        <label>Zone</label>
                        <select name="zone_id">
                            <option value="">All zones</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= $zone['id'] ?>" <?= (int) ($vendor['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Status</label>
                        <select name="status">
                            <?php foreach (['pending', 'approved', 'suspended', 'rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= $vendor['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Note</label>
                        <input name="admin_note" value="<?= htmlspecialchars($vendor['admin_note'] ?? '') ?>">
                        <div style="height:8px;"></div><button>Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
