<section class="card">
    <h2>Add Shipping Method</h2>
    <form method="post" action="/admin/shipping">
        <div class="row">
            <div><label>Name</label><input name="name" required placeholder="Standard Delivery"></div>
            <div><label>Cost</label><input name="cost" type="number" min="0" step="0.01" value="0"></div>
            <div><label>Expected Delivery</label><input name="expected_days" placeholder="Today or tomorrow"></div>
        </div>
        <label>Description</label><textarea name="description"></textarea>
        <div class="row">
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
            <div><label>Status</label><div><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</div></div>
        </div>
        <div style="height:12px;"></div><button>Add Method</button>
    </form>
</section>

<section class="card">
    <h2>Shipping Methods</h2>
    <table>
        <thead><tr><th>Name</th><th>Cost</th><th>Expected</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($methods as $method): ?>
            <tr>
                <form method="post" action="/admin/shipping/<?= $method['id'] ?>">
                    <td><input name="name" value="<?= htmlspecialchars($method['name']) ?>" required><label>Description</label><textarea name="description"><?= htmlspecialchars((string) ($method['description'] ?? '')) ?></textarea></td>
                    <td><input name="cost" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $method['cost']) ?>"></td>
                    <td><input name="expected_days" value="<?= htmlspecialchars((string) ($method['expected_days'] ?? '')) ?>"></td>
                    <td>
                        <input name="sort_order" type="number" value="<?= htmlspecialchars((string) $method['sort_order']) ?>">
                        <label><input style="width:auto;" name="status" type="checkbox" value="1" <?= (int) $method['status'] === 1 ? 'checked' : '' ?>> Active</label>
                    </td>
                    <td><button>Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
