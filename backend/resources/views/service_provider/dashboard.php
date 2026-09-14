<section class="card">
    <h2>Provider Summary</h2>
    <div class="grid">
        <div class="stat"><strong><?= (int) ($stats['total_bookings'] ?? 0) ?></strong><span>Total bookings</span></div>
        <div class="stat"><strong><?= (int) ($stats['active_bookings'] ?? 0) ?></strong><span>Active</span></div>
        <div class="stat"><strong><?= (int) ($stats['completed_bookings'] ?? 0) ?></strong><span>Completed</span></div>
        <div class="stat"><strong>₹<?= number_format((float) ($stats['payable_amount'] ?? 0), 2) ?></strong><span>Payable after <?= number_format((float) ($stats['commission_percent'] ?? 0), 2) ?>% commission</span></div>
    </div>
</section>

<section class="card">
    <h2>Notifications</h2>
    <table>
        <thead><tr><th>Title</th><th>Message</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($notifications as $notification): ?>
            <tr>
                <td><?= htmlspecialchars($notification['title']) ?></td>
                <td><?= htmlspecialchars((string) ($notification['message'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string) ($notification['created_at'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Profile</h2>
    <form method="post" action="/service-provider/profile">
        <div class="row">
            <div><label>Name</label><input name="name" value="<?= htmlspecialchars((string) ($provider['name'] ?? '')) ?>" required></div>
            <div><label>Phone</label><input name="phone" value="<?= htmlspecialchars((string) ($provider['phone'] ?? '')) ?>" required></div>
            <div><label>Email</label><input name="email" value="<?= htmlspecialchars((string) ($provider['email'] ?? '')) ?>"></div>
        </div>
        <div class="row">
            <div><label>Area</label><input name="area" value="<?= htmlspecialchars((string) ($provider['area'] ?? '')) ?>"></div>
            <div><label>New Password</label><input name="password" type="password" placeholder="Leave blank to keep current"></div>
            <div></div>
        </div>
        <button>Save Profile</button>
    </form>
</section>

<section class="card">
    <h2>Availability Blackouts</h2>
    <form method="post" action="/service-provider/blackouts">
        <div class="row">
            <div><label>Service</label><select name="service_id"><option value="">All my services</option><?php foreach ($services as $service): ?><option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Start Date</label><input name="blackout_date" type="date" required></div>
            <div><label>End Date</label><input name="end_date" type="date"></div>
        </div>
        <div class="row">
            <div><label>Recurrence</label><select name="recurrence"><option value="none">None</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div>
            <div><label>Start Time</label><input name="start_time" type="time"></div>
            <div><label>End Time</label><input name="end_time" type="time"></div>
        </div>
        <label>Reason</label><input name="reason" placeholder="Unavailable / holiday / personal leave">
        <div style="height:12px;"></div><button>Add Blackout</button>
    </form>
    <table>
        <thead><tr><th>Service</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($blackouts as $blackout): ?>
            <tr>
                <td><?= htmlspecialchars((string) ($blackout['service_name'] ?? 'All my services')) ?></td>
                <td><?= htmlspecialchars($blackout['blackout_date']) ?><?= !empty($blackout['end_date']) ? ' - ' . htmlspecialchars($blackout['end_date']) : '' ?><br><small><?= htmlspecialchars($blackout['recurrence'] ?? 'none') ?></small></td>
                <td><?= htmlspecialchars((string) ($blackout['start_time'] ?? '')) ?><?= !empty($blackout['end_time']) ? ' - ' . htmlspecialchars((string) $blackout['end_time']) : '' ?></td>
                <td><?= htmlspecialchars((string) ($blackout['reason'] ?? '')) ?></td>
                <td><span class="pill"><?= !empty($blackout['status']) ? 'active' : 'inactive' ?></span></td>
                <td>
                    <?php if (!empty($blackout['status'])): ?>
                    <form method="post" action="/service-provider/blackouts/<?= $blackout['id'] ?>/archive"><button class="danger">Archive</button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Settlements</h2>
    <table>
        <thead><tr><th>Period</th><th>Gross</th><th>Commission</th><th>Payable</th><th>Status</th><th>Reference</th></tr></thead>
        <tbody>
        <?php foreach ($settlements as $settlement): ?>
            <tr>
                <td><?= htmlspecialchars((string) ($settlement['period_start'] ?? '')) ?> - <?= htmlspecialchars((string) ($settlement['period_end'] ?? '')) ?></td>
                <td>₹<?= number_format((float) $settlement['gross_amount'], 2) ?></td>
                <td>₹<?= number_format((float) $settlement['commission_amount'], 2) ?></td>
                <td>₹<?= number_format((float) $settlement['payable_amount'], 2) ?></td>
                <td><span class="pill"><?= htmlspecialchars($settlement['status']) ?></span></td>
                <td><?= htmlspecialchars((string) ($settlement['payment_reference'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Assigned Bookings</h2>
    <table>
        <thead><tr><th>Booking</th><th>Service</th><th>Customer</th><th>Schedule</th><th>Amount</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?= htmlspecialchars($booking['booking_number']) ?></td>
                <td><?= htmlspecialchars((string) ($booking['service_name'] ?? '-')) ?></td>
                <td><?= htmlspecialchars($booking['customer_name']) ?><br><small><?= htmlspecialchars($booking['customer_phone']) ?></small><br><small><?= htmlspecialchars($booking['address']) ?></small></td>
                <td><?= htmlspecialchars((string) ($booking['preferred_date'] ?? '')) ?><br><small><?= htmlspecialchars((string) ($booking['preferred_time'] ?? '')) ?></small></td>
                <td>₹<?= number_format((float) $booking['amount'], 2) ?><?php if (!empty($booking['addon_total'])): ?><br><small>Add-ons: ₹<?= number_format((float) $booking['addon_total'], 2) ?></small><?php endif; ?></td>
                <td><span class="pill"><?= htmlspecialchars($booking['booking_status']) ?></span></td>
                <td>
                    <?php if (!in_array($booking['booking_status'], ['completed', 'cancelled'], true)): ?>
                    <form method="post" action="/service-provider/bookings/<?= $booking['id'] ?>/status">
                        <select name="booking_status">
                            <?php foreach (['accepted', 'ongoing', 'completed'] as $status): ?>
                                <option value="<?= $status ?>" <?= $booking['booking_status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button>Update</button>
                    </form>
                    <?php else: ?>
                    <small>No action</small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
