<section class="card">
    <h2>Add Delivery Man</h2>
    <form method="post" action="/admin/delivery-men">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Phone</label><input name="phone" required></div>
            <div><label>Email</label><input name="email" type="email"></div>
        </div>
        <div class="row">
            <div>
                <label>Zone</label>
                <select name="zone_id">
                    <option value="">All Zones</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>App Password</label><input name="password" type="password" placeholder="Required for worker app login"></div>
            <div><label>Availability</label><input value="Offline until worker logs in" disabled></div>
        </div>
        <div class="row">
            <div><label>Vehicle Type</label><input name="vehicle_type" placeholder="Bike, scooter, van"></div>
            <div><label>Vehicle Number</label><input name="vehicle_number"></div>
            <div><label>Status</label><div><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</div></div>
        </div>
        <div style="height:12px;"></div><button>Add Delivery Man</button>
    </form>
</section>

<section class="card">
    <h2>Delivery Team</h2>
    <table>
        <thead><tr><th>Name</th><th>Contact</th><th>Zone</th><th>Vehicle</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($deliveryMen as $person): ?>
            <tr>
                <td><input form="delivery-person-<?= $person['id'] ?>" name="name" value="<?= htmlspecialchars($person['name']) ?>" required></td>
                <td>
                    <input form="delivery-person-<?= $person['id'] ?>" name="phone" value="<?= htmlspecialchars($person['phone']) ?>" required>
                    <div style="height:6px;"></div>
                    <input form="delivery-person-<?= $person['id'] ?>" name="email" type="email" value="<?= htmlspecialchars($person['email'] ?? '') ?>" placeholder="Email">
                    <div style="height:6px;"></div>
                    <input form="delivery-person-<?= $person['id'] ?>" name="password" type="password" placeholder="New app password">
                </td>
                <td>
                    <select form="delivery-person-<?= $person['id'] ?>" name="zone_id">
                        <option value="">All Zones</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= (int) $zone['id'] ?>" <?= (int) ($person['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="height:6px;"></div>
                    <small><?= htmlspecialchars($person['zone_name'] ?? 'All Zones') ?></small>
                </td>
                <td>
                    <input form="delivery-person-<?= $person['id'] ?>" name="vehicle_type" value="<?= htmlspecialchars($person['vehicle_type'] ?? '') ?>" placeholder="Vehicle type">
                    <div style="height:6px;"></div>
                    <input form="delivery-person-<?= $person['id'] ?>" name="vehicle_number" value="<?= htmlspecialchars($person['vehicle_number'] ?? '') ?>" placeholder="Vehicle number">
                </td>
                <td>
                    <span class="pill"><?= ((int) $person['status']) === 1 ? 'Active' : 'Inactive' ?></span>
                    <span class="pill"><?= htmlspecialchars($person['availability_status'] ?? 'offline') ?></span>
                    <div style="height:8px;"></div>
                    <input form="delivery-person-<?= $person['id'] ?>" style="width:auto;" name="status" type="checkbox" value="1" <?= ((int) $person['status']) === 1 ? 'checked' : '' ?>> Active
                    <?php if (!empty($person['last_seen_at'])): ?><br><small>Seen <?= htmlspecialchars($person['last_seen_at']) ?></small><?php endif; ?>
                </td>
                <td>
                    <form id="delivery-person-<?= $person['id'] ?>" method="post" action="/admin/delivery-men/<?= $person['id'] ?>"></form>
                    <button form="delivery-person-<?= $person['id'] ?>">Save</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
