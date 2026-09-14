<section class="grid">
    <div class="card stat"><span>Orders</span><strong><?= (int) ($sales['orders_count'] ?? 0) ?></strong></div>
    <div class="card stat"><span>Total Sales</span><strong>₹<?= number_format((float) ($sales['total_sales'] ?? 0), 0) ?></strong></div>
    <div class="card stat"><span>Coupon Savings</span><strong>₹<?= number_format((float) ($sales['coupon_discount'] ?? 0), 0) ?></strong></div>
    <div class="card stat"><span>Commission</span><strong><?= number_format((float) $commissionPercent, 2) ?>%</strong></div>
</section>

<section class="card">
    <h2>Exports</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="<?= htmlspecialchars($exportBase ?? '/admin/reports/export') ?>/sales">Download Sales CSV</a>
        <a class="btn secondary" href="<?= htmlspecialchars($exportBase ?? '/admin/reports/export') ?>/vendors">Download Vendor CSV</a>
        <a class="btn secondary" href="<?= htmlspecialchars($exportBase ?? '/admin/reports/export') ?>/low-stock">Download Low Stock CSV</a>
    </div>
</section>

<section class="card">
    <h2>Vendor Earnings</h2>
    <table>
        <thead><tr><th>Vendor</th><th>Orders</th><th>Sales</th><th>Commission</th><th>Payable</th></tr></thead>
        <tbody>
        <?php foreach ($vendors as $vendor): ?>
            <tr>
                <td><?= htmlspecialchars($vendor['shop_name']) ?></td>
                <td><?= (int) $vendor['orders_count'] ?></td>
                <td>₹<?= number_format((float) $vendor['sales'], 2) ?></td>
                <td>₹<?= number_format((float) $vendor['commission'], 2) ?></td>
                <td><strong>₹<?= number_format((float) $vendor['payable'], 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Low Stock</h2>
    <table>
        <thead><tr><th>Product</th><th>Vendor</th><th>Category</th><th>Stock</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($lowStock as $product): ?>
            <tr>
                <td><?= htmlspecialchars($product['name']) ?><br><small><?= htmlspecialchars($product['sku'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($product['vendor_name'] ?? 'Admin Store') ?></td>
                <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                <td><strong><?= (int) $product['stock'] ?></strong></td>
                <td><span class="pill"><?= $product['status'] ? 'Active' : 'Inactive/Pending' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
