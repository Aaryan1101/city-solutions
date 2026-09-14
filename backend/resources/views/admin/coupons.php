<section class="card">
    <h2>Add Coupon</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/coupons') ?>">
        <div class="row">
            <div><label>Code</label><input name="code" placeholder="SAVE50" required></div>
            <div><label>Title</label><input name="title" placeholder="Launch offer" required></div>
            <div>
                <label>Discount Type</label>
                <select name="discount_type">
                    <option value="flat">Flat Amount</option>
                    <option value="percent">Percent</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div><label>Discount Value</label><input name="discount_value" type="number" step="0.01" required></div>
            <div><label>Minimum Order</label><input name="minimum_order_amount" type="number" step="0.01" value="0"></div>
            <div><label>Maximum Discount</label><input name="maximum_discount" type="number" step="0.01"></div>
        </div>
        <div class="row">
            <div><label>Usage Limit</label><input name="usage_limit" type="number"></div>
            <div><label>Starts At</label><input name="starts_at" type="date"></div>
            <div><label>Expires At</label><input name="expires_at" type="date"></div>
        </div>
        <label><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</label>
        <div style="height:12px;"></div><button>Add Coupon</button>
    </form>
</section>

<section class="card">
    <h2>Coupons</h2>
    <table>
        <thead><tr><th>Code</th><th>Discount</th><th>Rules</th><th>Used</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($coupons as $coupon): ?>
            <tr>
                <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/coupons') ?>/<?= $coupon['id'] ?>/edit">
                    <td>
                        <input name="code" value="<?= htmlspecialchars($coupon['code']) ?>">
                        <label>Title</label><input name="title" value="<?= htmlspecialchars($coupon['title']) ?>">
                    </td>
                    <td>
                        <select name="discount_type">
                            <option value="flat" <?= $coupon['discount_type'] === 'flat' ? 'selected' : '' ?>>Flat</option>
                            <option value="percent" <?= $coupon['discount_type'] === 'percent' ? 'selected' : '' ?>>Percent</option>
                        </select>
                        <label>Value</label><input name="discount_value" type="number" step="0.01" value="<?= htmlspecialchars((string) $coupon['discount_value']) ?>">
                    </td>
                    <td>
                        <label>Minimum</label><input name="minimum_order_amount" type="number" step="0.01" value="<?= htmlspecialchars((string) $coupon['minimum_order_amount']) ?>">
                        <label>Maximum</label><input name="maximum_discount" type="number" step="0.01" value="<?= htmlspecialchars((string) ($coupon['maximum_discount'] ?? '')) ?>">
                        <label>Usage Limit</label><input name="usage_limit" type="number" value="<?= htmlspecialchars((string) ($coupon['usage_limit'] ?? '')) ?>">
                        <label>Starts</label><input name="starts_at" type="date" value="<?= htmlspecialchars((string) ($coupon['starts_at'] ?? '')) ?>">
                        <label>Expires</label><input name="expires_at" type="date" value="<?= htmlspecialchars((string) ($coupon['expires_at'] ?? '')) ?>">
                    </td>
                    <td><?= (int) $coupon['used_count'] ?></td>
                    <td><label><input style="width:auto;" name="status" type="checkbox" value="1" <?= (int) $coupon['status'] === 1 ? 'checked' : '' ?>> Active</label></td>
                    <td>
                        <button>Save</button>
                        <a class="btn danger" href="<?= htmlspecialchars($basePath ?? '/admin/coupons') ?>/<?= $coupon['id'] ?>/delete">Delete</a>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
