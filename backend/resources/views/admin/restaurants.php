<div class="grid" id="summary">
    <div class="card stat"><strong><?= count($restaurants) ?></strong><span>Restaurants</span></div>
    <div class="card stat"><strong><?= count($bookings) ?></strong><span>Recent Bookings</span></div>
    <div class="card stat"><strong><?= count($foodItems ?? []) ?></strong><span>Food Items</span></div>
    <div class="card stat"><strong><?= count($foodOrders ?? []) ?></strong><span>Food Orders</span></div>
    <div class="card stat"><strong><?= count($waitlists) ?></strong><span>Waitlist Requests</span></div>
</div>

<div class="card" id="restaurants">
    <h2>Restaurants</h2>
    <p style="color:var(--muted);">Restaurant inventory is currently seeded and API-driven. Use this page for operational visibility while full restaurant CRUD is expanded.</p>
    <table>
        <thead><tr><th>Restaurant</th><th>Zone</th><th>Area</th><th>Cuisine</th><th>Cost</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($restaurants as $restaurant): ?>
            <tr>
                <td><strong><?= htmlspecialchars($restaurant['name']) ?></strong><br><span class="pill"><?= htmlspecialchars($restaurant['category_name'] ?? 'Uncategorized') ?></span></td>
                <td><?= htmlspecialchars($restaurant['zone_name'] ?? 'All Zones') ?></td>
                <td><?= htmlspecialchars($restaurant['city'] ?? '') ?><br><?= htmlspecialchars($restaurant['area'] ?? '') ?></td>
                <td><?= htmlspecialchars($restaurant['cuisine'] ?? '') ?></td>
                <td>₹<?= number_format((float) ($restaurant['average_cost'] ?? 0), 2) ?></td>
                <td><span class="pill"><?= (int) ($restaurant['status'] ?? 0) === 1 ? 'active' : 'inactive' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="food-items">
    <h2>Food Delivery Items</h2>
    <p style="color:var(--muted);">These dishes power the Restaurant food delivery flow in the customer app.</p>
    <form method="post" action="/admin/restaurants/food-items" class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));align-items:end;">
        <label>Restaurant
            <select name="restaurant_id" required>
                <?php foreach ($restaurants as $restaurant): ?>
                    <option value="<?= (int) $restaurant['id'] ?>"><?= htmlspecialchars($restaurant['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Food Category
            <select name="category_id">
                <option value="">None</option>
                <?php foreach (($foodCategories ?? []) as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Name <input name="name" required placeholder="Paneer Tikka"></label>
        <label>Price <input name="price" type="number" step="0.01" min="0" required></label>
        <label>Discount Price <input name="discount_price" type="number" step="0.01" min="0"></label>
        <label>Prep Minutes <input name="prep_time_minutes" type="number" min="5" value="25"></label>
        <label>Stock <input name="stock" type="number" min="0" value="100"></label>
        <label>Image URL/path <input name="image" placeholder="/public/uploads/..."></label>
        <label>Description <input name="description" placeholder="Short dish description"></label>
        <label><input type="checkbox" name="is_veg" value="1"> Veg</label>
        <label><input type="checkbox" name="is_featured" value="1" checked> Featured</label>
        <label><input type="checkbox" name="status" value="1" checked> Active</label>
        <button class="btn">Add Food Item</button>
    </form>
    <table>
        <thead><tr><th>Dish</th><th>Restaurant</th><th>Category</th><th>Price</th><th>Prep</th><th>Stock</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach (($foodItems ?? []) as $item): ?>
            <tr>
                <td><strong><?= htmlspecialchars($item['name']) ?></strong><br><small><?= htmlspecialchars($item['description'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($item['restaurant_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                <td>₹<?= number_format((float) ($item['discount_price'] ?: $item['price']), 2) ?><?php if (!empty($item['discount_price'])): ?><br><small>MRP ₹<?= number_format((float) $item['price'], 2) ?></small><?php endif; ?></td>
                <td><?= (int) ($item['prep_time_minutes'] ?? 0) ?> min</td>
                <td><?= (int) ($item['stock'] ?? 0) ?></td>
                <td><span class="pill"><?= (int) ($item['status'] ?? 0) === 1 ? 'active' : 'inactive' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="food-orders">
    <h2>Food Delivery Orders</h2>
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Restaurant</th><th>Address</th><th>Amount</th><th>Payment</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach (($foodOrders ?? []) as $order): ?>
            <tr>
                <form method="post" action="/admin/restaurants/food-orders/<?= (int) $order['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($order['order_number']) ?></strong><br><small><?= htmlspecialchars($order['created_at'] ?? '') ?></small></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?><br><?= htmlspecialchars($order['customer_phone']) ?></td>
                    <td><?= htmlspecialchars($order['restaurant_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($order['delivery_address'] ?? '') ?></td>
                    <td>₹<?= number_format((float) ($order['total'] ?? 0), 2) ?><br><small>Delivery ₹<?= number_format((float) ($order['delivery_fee'] ?? 0), 2) ?></small></td>
                    <td>
                        <?= htmlspecialchars($order['payment_method']) ?><br>
                        <select name="payment_status">
                            <?php foreach (['unpaid','pending_verification','paid','failed','refunded'] as $status): ?>
                                <option value="<?= $status ?>" <?= $order['payment_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="order_status">
                            <?php foreach (['pending','confirmed','preparing','out_for_delivery','delivered','cancelled','rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="bookings">
    <h2>Restaurant Bookings</h2>
    <table>
        <thead><tr><th>Booking</th><th>Customer</th><th>Slot</th><th>Payment</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <form method="post" action="/admin/restaurants/bookings/<?= (int) $booking['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong><br><?= htmlspecialchars($booking['restaurant_name']) ?></td>
                    <td><?= htmlspecialchars($booking['customer_name']) ?><br><?= htmlspecialchars($booking['customer_phone']) ?><?php if (!empty($booking['customer_email'])): ?><br><?= htmlspecialchars($booking['customer_email']) ?><?php endif; ?></td>
                    <td><?= htmlspecialchars($booking['booking_date']) ?> at <?= htmlspecialchars($booking['booking_time']) ?><br><?= (int) $booking['party_size'] ?> guest(s)</td>
                    <td>
                        <?= htmlspecialchars($booking['payment_method']) ?><br>
                        <?php if (!empty($booking['payment_reference'])): ?><small>Ref: <?= htmlspecialchars($booking['payment_reference']) ?></small><br><?php endif; ?>
                        <?php if (!empty($booking['transaction_status'])): ?><small>Txn: <?= htmlspecialchars($booking['transaction_status']) ?><?= !empty($booking['payment_reconciled_at']) ? ' / ' . htmlspecialchars($booking['payment_reconciled_at']) : '' ?></small><br><?php endif; ?>
                        <select name="payment_status">
                            <?php foreach (['unpaid','pending_verification','paid','failed','refunded'] as $status): ?>
                                <option value="<?= $status ?>" <?= $booking['payment_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="booking_status">
                            <?php foreach (['pending','confirmed','seated','completed','cancel_requested','cancelled','rejected','no_show'] as $status): ?>
                                <option value="<?= $status ?>" <?= $booking['booking_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input name="admin_note" value="<?= htmlspecialchars($booking['admin_note'] ?? '') ?>" placeholder="Admin note"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="waitlist">
    <h2>Waitlist Requests</h2>
    <table>
        <thead><tr><th>Restaurant</th><th>Customer</th><th>Requested Slot</th><th>Request</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($waitlists as $request): ?>
            <tr>
                <form method="post" action="/admin/restaurants/waitlist/<?= (int) $request['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($request['restaurant_name']) ?></strong><br><span class="pill">#<?= (int) $request['id'] ?></span></td>
                    <td><?= htmlspecialchars($request['customer_name']) ?><br><?= htmlspecialchars($request['customer_phone']) ?><?php if (!empty($request['customer_email'])): ?><br><?= htmlspecialchars($request['customer_email']) ?><?php endif; ?></td>
                    <td><?= htmlspecialchars($request['booking_date']) ?> at <?= htmlspecialchars($request['booking_time']) ?><br><?= (int) $request['party_size'] ?> guest(s)</td>
                    <td><?= htmlspecialchars($request['special_request'] ?? '') ?><br><small><?= htmlspecialchars($request['created_at'] ?? '') ?></small></td>
                    <td>
                        <select name="status">
                            <?php foreach (['pending','contacted','converted','expired','cancelled'] as $status): ?>
                                <option value="<?= $status ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input name="admin_note" value="<?= htmlspecialchars($request['admin_note'] ?? '') ?>" placeholder="Admin note"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
