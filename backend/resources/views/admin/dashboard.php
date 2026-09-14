<section class="dashboard-hero">
    <div class="hero-panel">
        <h2>City operations at a glance</h2>
        <p>Review pending work, monitor marketplace health, and move quickly into orders, vendors, refunds, payments, and production checks.</p>
        <div class="hero-actions">
            <a class="btn" href="/admin/orders">Review Orders</a>
            <a class="btn secondary" href="/admin/vendors">Vendor Queue</a>
            <?php if (!empty($isSuperAdmin)): ?>
            <a class="btn secondary" href="/admin/health">Production Health</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="focus-panel">
        <h2>Today’s Focus</h2>
        <div class="focus-list">
            <a class="focus-item" href="/admin/orders"><span>Recent orders</span><span class="pill"><?= (int) $stats['orders'] ?></span></a>
            <a class="focus-item" href="/admin/products"><span>Low stock</span><span class="pill"><?= (int) $stats['low_stock'] ?></span></a>
            <a class="focus-item" href="/admin/vendors"><span>Pending vendors</span><span class="pill"><?= (int) $stats['pending_vendors'] ?></span></a>
        </div>
    </div>
</section>

<section class="grid">
    <div class="card stat"><span>Categories</span><strong><?= $stats['categories'] ?></strong></div>
    <div class="card stat"><span>Products</span><strong><?= $stats['products'] ?></strong></div>
    <div class="card stat"><span>Orders</span><strong><?= $stats['orders'] ?></strong></div>
    <div class="card stat"><span>Low Stock</span><strong><?= $stats['low_stock'] ?></strong></div>
    <div class="card stat"><span>Pending Vendors</span><strong><?= $stats['pending_vendors'] ?></strong></div>
</section>

<section class="card">
    <h2>Recent Orders</h2>
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['order_number']) ?></td>
                <td><?= htmlspecialchars($order['customer_name']) ?><br><small><?= htmlspecialchars($order['customer_phone']) ?></small></td>
                <td>₹<?= number_format((float) $order['order_amount'], 2) ?></td>
                <td><span class="pill"><?= htmlspecialchars($order['order_status']) ?></span></td>
                <td><a class="btn secondary" href="/admin/orders/<?= $order['id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
