<section class="card">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px;">
        <h2><?= htmlspecialchars($order['order_number']) ?></h2>
        <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/orders') ?>/<?= $order['id'] ?>/invoice">Invoice</a>
    </div>
    <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?>, <?= htmlspecialchars($order['customer_phone']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
    <p><strong>Total:</strong> ₹<?= number_format((float) $order['order_amount'], 2) ?></p>
    <?php if ((float) ($order['coupon_discount'] ?? 0) > 0): ?>
        <p><strong>Coupon:</strong> <?= htmlspecialchars($order['coupon_code'] ?? '') ?> saved ₹<?= number_format((float) $order['coupon_discount'], 2) ?></p>
    <?php endif; ?>
    <?php if (!empty($order['shipping_method_name']) || (float) ($order['shipping_cost'] ?? 0) > 0): ?>
        <p><strong>Shipping:</strong> <?= htmlspecialchars($order['shipping_method_name'] ?? 'Delivery') ?> - ₹<?= number_format((float) ($order['shipping_cost'] ?? 0), 2) ?><?php if (!empty($order['expected_delivery'])): ?><br><small><?= htmlspecialchars($order['expected_delivery']) ?></small><?php endif; ?></p>
    <?php endif; ?>
    <?php
        $substitutionLabels = [
            'call_before_replace' => 'Call customer before replacing unavailable items',
            'auto_replace' => 'Auto-replace with similar items',
            'no_replacement' => 'Do not replace unavailable items',
        ];
        $substitutionPreference = (string) ($order['substitution_preference'] ?? 'call_before_replace');
    ?>
    <p><strong>Substitution:</strong> <?= htmlspecialchars($substitutionLabels[$substitutionPreference] ?? $substitutionLabels['call_before_replace']) ?></p>
    <p><strong>Delivery:</strong>
        <?php if (!empty($order['delivery_man_name'])): ?>
            <?= htmlspecialchars($order['delivery_man_name']) ?>, <?= htmlspecialchars($order['delivery_man_phone'] ?? '') ?>
            <?php if (!empty($order['vehicle_type']) || !empty($order['vehicle_number'])): ?>
                <br><small><?= htmlspecialchars(trim(($order['vehicle_type'] ?? '') . ' ' . ($order['vehicle_number'] ?? ''))) ?></small>
            <?php endif; ?>
        <?php else: ?>
            Unassigned
        <?php endif; ?>
    </p>
    <p><strong>Display Status:</strong> <span class="pill"><?= htmlspecialchars($order['display_status'] ?? $order['order_status']) ?></span></p>
    <?php if (!empty($order['refund_status'])): ?>
        <p><strong>Refund Status:</strong> <span class="pill"><?= htmlspecialchars($order['refund_status']) ?></span></p>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/orders') ?>/<?= $order['id'] ?>/status">
        <label>Status</label>
        <select name="order_status">
            <?php foreach (['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled'] as $status): ?>
                <option value="<?= $status ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
        </select>
        <div style="height:12px;"></div><button>Update Status</button>
    </form>
</section>

<section class="card">
    <h2>Tracking</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/orders') ?>/<?= $order['id'] ?>/tracking">
        <div class="row">
            <div><label>Provider</label><input name="tracking_provider" value="<?= htmlspecialchars((string) ($order['tracking_provider'] ?? '')) ?>"></div>
            <div><label>Tracking Number</label><input name="tracking_number" value="<?= htmlspecialchars((string) ($order['tracking_number'] ?? '')) ?>"></div>
            <div><label>Expected Delivery</label><input name="expected_delivery" value="<?= htmlspecialchars((string) ($order['expected_delivery'] ?? '')) ?>"></div>
        </div>
        <label>Tracking URL</label><input name="tracking_url" value="<?= htmlspecialchars((string) ($order['tracking_url'] ?? '')) ?>">
        <div style="height:12px;"></div><button>Save Tracking</button>
    </form>
</section>

<section class="card">
    <h2>Delivery Assignment</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/orders') ?>/<?= $order['id'] ?>/delivery">
        <label>Delivery Man</label>
        <select name="delivery_man_id">
            <option value="">Unassigned</option>
            <?php foreach (($deliveryMen ?? []) as $person): ?>
                <option value="<?= $person['id'] ?>" <?= (int) ($order['delivery_man_id'] ?? 0) === (int) $person['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($person['name']) ?> - <?= htmlspecialchars($person['phone']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div style="height:12px;"></div><button>Save Assignment</button>
    </form>
</section>

<section class="card">
    <h2>Status History</h2>
    <table>
        <thead><tr><th>Status</th><th>Source</th><th>Note</th><th>Time</th></tr></thead>
        <tbody>
        <?php foreach (($history ?? []) as $row): ?>
            <tr>
                <td><span class="pill"><?= htmlspecialchars($row['status']) ?></span></td>
                <td><?= htmlspecialchars($row['actor_type']) ?><?php if ($row['actor_name']): ?><br><small><?= htmlspecialchars($row['actor_name']) ?></small><?php endif; ?></td>
                <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Items</h2>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Status</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?><?php if (!empty($item['variant_name'])): ?><br><small><?= htmlspecialchars($item['variant_name']) ?></small><?php endif; ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td>₹<?= number_format((float) $item['price'], 2) ?></td>
                <td><span class="pill"><?= htmlspecialchars($item['status'] ?? 'pending') ?></span></td>
                <td>₹<?= number_format((float) $item['total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
