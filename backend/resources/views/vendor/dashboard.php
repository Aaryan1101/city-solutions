<section class="grid">
    <div class="card stat"><span>Products</span><strong><?= $stats['products'] ?></strong></div>
    <div class="card stat"><span>Active</span><strong><?= $stats['active_products'] ?></strong></div>
    <div class="card stat"><span>Orders</span><strong><?= $stats['orders'] ?></strong></div>
    <div class="card stat"><span>Sales</span><strong>₹<?= number_format((float) $stats['sales'], 0) ?></strong></div>
    <div class="card stat"><span>Commission <?= number_format((float) $stats['commission_percent'], 2) ?>%</span><strong>₹<?= number_format((float) $stats['commission'], 0) ?></strong></div>
    <div class="card stat"><span>Payable</span><strong>₹<?= number_format((float) $stats['payable'], 0) ?></strong></div>
    <div class="card stat"><span>Wallet Balance</span><strong>₹<?= number_format((float) $stats['wallet_balance'], 0) ?></strong></div>
</section>

<section class="card">
    <h2>Exports</h2>
    <a class="btn secondary" href="/vendor/reports/orders.csv">Download Orders CSV</a>
    <a class="btn secondary" href="/vendor/reports/settlements.csv">Download Settlements CSV</a>
    <a class="btn secondary" href="/vendor/reports/products.csv">Download Products CSV</a>
    <a class="btn secondary" href="/vendor/reports/ledger.csv">Download Wallet Ledger CSV</a>
    <a class="btn secondary" href="/vendor/reports/summary.csv">Download Sales Summary CSV</a>
</section>

<section class="card">
    <h2>Shop Profile & Vacation</h2>
    <form method="post" action="/vendor/profile">
        <div class="row">
            <div><label>Shop Name</label><input name="shop_name" value="<?= htmlspecialchars($vendor['shop_name'] ?? '') ?>" required></div>
            <div><label>Owner Name</label><input name="owner_name" value="<?= htmlspecialchars($vendor['owner_name'] ?? '') ?>" required></div>
            <div><label>Email</label><input name="email" value="<?= htmlspecialchars($vendor['email'] ?? '') ?>"></div>
        </div>
        <div class="row">
            <div><label>City</label><input name="city" value="<?= htmlspecialchars($vendor['city'] ?? '') ?>"></div>
            <div><label>Vacation Starts</label><input name="vacation_starts_at" type="datetime-local" value="<?= htmlspecialchars(!empty($vendor['vacation_starts_at']) ? str_replace(' ', 'T', substr((string) $vendor['vacation_starts_at'], 0, 16)) : '') ?>"></div>
            <div><label>Vacation Ends</label><input name="vacation_ends_at" type="datetime-local" value="<?= htmlspecialchars(!empty($vendor['vacation_ends_at']) ? str_replace(' ', 'T', substr((string) $vendor['vacation_ends_at'], 0, 16)) : '') ?>"></div>
        </div>
        <label>Address</label><textarea name="address"><?= htmlspecialchars($vendor['address'] ?? '') ?></textarea>
        <label>Description</label><textarea name="description"><?= htmlspecialchars($vendor['description'] ?? '') ?></textarea>
        <label>Vacation Note</label><textarea name="vacation_note"><?= htmlspecialchars($vendor['vacation_note'] ?? '') ?></textarea>
        <label><input style="width:auto;" name="is_temporarily_closed" type="checkbox" value="1" <?= !empty($vendor['is_temporarily_closed']) ? 'checked' : '' ?>> Temporarily closed</label>
        <div style="height:12px;"></div><button>Save Shop Profile</button>
    </form>
</section>

<section class="card">
    <h2>Recent Orders</h2>
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['order_number']) ?></td>
                <td><?= htmlspecialchars($order['customer_name']) ?><br><small><?= htmlspecialchars($order['customer_phone']) ?></small></td>
                <td>₹<?= number_format((float) $order['order_amount'], 2) ?></td>
                <td><span class="pill"><?= htmlspecialchars($order['order_status']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
