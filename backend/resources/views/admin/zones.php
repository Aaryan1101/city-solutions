<section class="card">
    <h2>Add Zone</h2>
    <form method="post" action="/admin/zones">
        <div class="row">
            <div><label>Name</label><input name="name" required placeholder="Lucknow Central"></div>
            <div><label>City</label><input name="city" placeholder="Lucknow"></div>
            <div><label>State</label><input name="state" placeholder="Uttar Pradesh"></div>
            <div><label>Pincode</label><input name="pincode" placeholder="226001"></div>
            <div><label>More Pincodes</label><input name="pincodes" placeholder="226001, 226002"></div>
            <div><label>Latitude</label><input name="latitude" type="number" step="0.0000001" placeholder="26.8467"></div>
            <div><label>Longitude</label><input name="longitude" type="number" step="0.0000001" placeholder="80.9462"></div>
            <div><label>Radius KM</label><input name="radius_km" type="number" step="0.1" value="5"></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
            <div><label>Status</label><div><input style="width:auto;" type="checkbox" name="status" value="1" checked> Active</div></div>
        </div>
        <button>Add Zone</button>
    </form>
</section>

<section class="card">
    <h2>Zones</h2>
    <table>
        <thead><tr><th>Name</th><th>City</th><th>Pincodes</th><th>Map Radius</th><th>Status</th><th>Sort</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($zones as $zone): ?>
            <tr>
                <form method="post" action="/admin/zones/<?= (int) $zone['id'] ?>/update">
                    <td><input name="name" value="<?= htmlspecialchars($zone['name']) ?>" required></td>
                    <td><input name="city" value="<?= htmlspecialchars($zone['city'] ?? '') ?>"></td>
                    <td>
                        <input name="state" value="<?= htmlspecialchars($zone['state'] ?? '') ?>" placeholder="State">
                        <input name="pincode" value="<?= htmlspecialchars($zone['pincode'] ?? '') ?>" placeholder="Primary pincode">
                        <input name="pincodes" value="<?= htmlspecialchars($zone['pincodes'] ?? '') ?>" placeholder="More pincodes">
                    </td>
                    <td>
                        <input name="latitude" type="number" step="0.0000001" value="<?= htmlspecialchars((string) ($zone['latitude'] ?? '')) ?>" placeholder="Latitude">
                        <input name="longitude" type="number" step="0.0000001" value="<?= htmlspecialchars((string) ($zone['longitude'] ?? '')) ?>" placeholder="Longitude">
                        <input name="radius_km" type="number" step="0.1" value="<?= htmlspecialchars((string) ($zone['radius_km'] ?? 0)) ?>" placeholder="Radius KM">
                    </td>
                    <td><input style="width:auto;" type="checkbox" name="status" value="1" <?= !empty($zone['status']) ? 'checked' : '' ?>></td>
                    <td><input name="sort_order" type="number" value="<?= (int) ($zone['sort_order'] ?? 0) ?>"></td>
                    <td>
                        <button>Save</button>
                        <a class="btn danger" href="/admin/zones/<?= (int) $zone['id'] ?>/delete">Delete</a>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
