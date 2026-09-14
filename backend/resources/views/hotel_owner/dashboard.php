<section class="card" id="summary">
    <h2>Hotel Summary</h2>
    <div class="grid">
        <div class="stat"><strong><?= count($hotels) ?></strong><span>Hotels</span></div>
        <div class="stat"><strong><?= count($rooms) ?></strong><span>Rooms</span></div>
        <div class="stat"><strong><?= (int) ($stats['active_bookings'] ?? 0) ?></strong><span>Active bookings</span></div>
        <div class="stat"><strong>₹<?= number_format((float) ($stats['total_amount'] ?? 0), 2) ?></strong><span>Total booked value</span></div>
        <div class="stat"><strong>₹<?= number_format((float) ($walletAccount['balance'] ?? 0), 2) ?></strong><span>Wallet balance</span></div>
    </div>
</section>

<section class="card" id="wallet">
    <h2>Payouts & Wallet</h2>
    <div class="grid">
        <div class="stat"><strong>₹<?= number_format((float) ($walletAccount['balance'] ?? 0), 2) ?></strong><span>Available balance</span></div>
        <div class="stat"><strong><?= (int) ($payoutSummary['completed_count'] ?? 0) ?></strong><span>Completed bookings</span></div>
        <div class="stat"><strong>₹<?= number_format((float) ($payoutSummary['completed_gross'] ?? 0), 2) ?></strong><span>Completed gross</span></div>
        <div class="stat"><strong><?= number_format((float) ($commissionPercent ?? 0), 2) ?>%</strong><span>Platform commission</span></div>
    </div>
    <form method="post" action="/hotel-owner/wallet/withdrawals">
        <div class="row">
            <div><label>Withdrawal Amount</label><input name="amount" type="number" min="0" step="0.01" required></div>
            <div><label>Bank Details</label><textarea name="bank_details" placeholder="Bank name, account number, IFSC"></textarea></div>
            <div><label>Note</label><textarea name="note" placeholder="Optional note"></textarea></div>
        </div>
        <button>Request Withdrawal</button>
    </form>
    <h2>Wallet Ledger</h2>
    <table>
        <thead><tr><th>Type</th><th>Direction</th><th>Amount</th><th>Description</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach (($walletLedger ?? []) as $entry): ?>
            <tr>
                <td><?= htmlspecialchars((string) $entry['entry_type']) ?></td>
                <td><?= htmlspecialchars((string) $entry['direction']) ?></td>
                <td>₹<?= number_format((float) $entry['amount'], 2) ?></td>
                <td><?= htmlspecialchars((string) ($entry['description'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string) ($entry['created_at'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <h2>Withdrawal Requests</h2>
    <table>
        <thead><tr><th>Amount</th><th>Status</th><th>Bank Details</th><th>Admin Note</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach (($withdrawals ?? []) as $withdrawal): ?>
            <tr>
                <td>₹<?= number_format((float) $withdrawal['amount'], 2) ?></td>
                <td><span class="pill"><?= htmlspecialchars((string) $withdrawal['status']) ?></span></td>
                <td><?= nl2br(htmlspecialchars((string) ($withdrawal['bank_details'] ?? '-'))) ?></td>
                <td><?= htmlspecialchars((string) ($withdrawal['admin_note'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string) ($withdrawal['created_at'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="profile">
    <h2>Profile</h2>
    <form method="post" action="/hotel-owner/profile">
        <div class="row">
            <div><label>Name</label><input name="name" value="<?= htmlspecialchars((string) ($owner['name'] ?? '')) ?>" required></div>
            <div><label>Phone</label><input name="phone" value="<?= htmlspecialchars((string) ($owner['phone'] ?? '')) ?>" required></div>
            <div><label>Email</label><input name="email" value="<?= htmlspecialchars((string) ($owner['email'] ?? '')) ?>"></div>
        </div>
        <div class="row">
            <div><label>New Password</label><input name="password" type="password" placeholder="Leave blank to keep current"></div>
            <div></div><div></div>
        </div>
        <button>Save Profile</button>
    </form>
</section>

<section class="card" id="hotels">
    <h2>My Hotels</h2>
    <table>
        <thead><tr><th>Hotel</th><th>Location</th><th>Rating</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($hotels as $hotel): ?>
            <tr>
                <td><strong><?= htmlspecialchars($hotel['name']) ?></strong><br><?= htmlspecialchars((string) ($hotel['description'] ?? '')) ?></td>
                <td><?= htmlspecialchars($hotel['area'] ?? '') ?><br><?= htmlspecialchars($hotel['city']) ?></td>
                <td><?= htmlspecialchars((string) $hotel['star_rating']) ?> star<br><?= htmlspecialchars((string) $hotel['rating']) ?> customer</td>
                <td><span class="pill"><?= (int) $hotel['status'] === 1 ? 'active' : 'inactive' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="rooms">
    <h2>Room Inventory</h2>
    <table>
        <thead><tr><th>Room</th><th>Hotel</th><th>Inventory</th><th>Price</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= htmlspecialchars($room['name']) ?></td>
                <td><?= htmlspecialchars($room['hotel_name']) ?></td>
                <td><?= (int) $room['total_rooms'] ?> rooms</td>
                <td>₹<?= number_format((float) ($room['discount_price'] ?: $room['price_per_night']), 2) ?></td>
                <td><span class="pill"><?= (int) $room['status'] === 1 ? 'active' : 'inactive' ?></span></td>
                <td>
                    <form method="post" action="/hotel-owner/rooms/<?= (int) $room['id'] ?>/update">
                        <div class="row">
                            <div><label>Total Rooms</label><input name="total_rooms" type="number" min="0" value="<?= (int) $room['total_rooms'] ?>"></div>
                            <div><label>Price</label><input name="price_per_night" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $room['price_per_night']) ?>"></div>
                            <div><label>Discount</label><input name="discount_price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($room['discount_price'] ?? '')) ?>"></div>
                        </div>
                        <div class="row">
                            <div><label>Tax %</label><input name="tax_percent" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) $room['tax_percent']) ?>"></div>
                            <div><label>Status</label><div><input style="width:auto;" name="status" type="checkbox" value="1" <?= (int) $room['status'] === 1 ? 'checked' : '' ?>> Active</div></div>
                            <div><button>Save Room</button></div>
                        </div>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="bookings">
    <h2>Bookings</h2>
    <table>
        <thead><tr><th>Booking</th><th>Guest</th><th>Stay</th><th>Amount</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong><br><?= htmlspecialchars($booking['hotel_name']) ?><br><?= htmlspecialchars($booking['room_name']) ?></td>
                <td><?= htmlspecialchars($booking['customer_name']) ?><br><small><?= htmlspecialchars($booking['customer_phone']) ?></small></td>
                <td><?= htmlspecialchars($booking['check_in']) ?> to <?= htmlspecialchars($booking['check_out']) ?><br><small><?= (int) $booking['rooms'] ?> room(s)</small></td>
                <td>₹<?= number_format((float) $booking['grand_total'], 2) ?><br><small><?= htmlspecialchars($booking['payment_status']) ?></small></td>
                <td><span class="pill"><?= htmlspecialchars($booking['booking_status']) ?></span></td>
                <td>
                    <?php if (!in_array($booking['booking_status'], ['completed', 'cancelled', 'rejected'], true)): ?>
                    <form method="post" action="/hotel-owner/bookings/<?= (int) $booking['id'] ?>/status">
                        <select name="booking_status">
                            <?php foreach (['confirmed', 'checked_in', 'completed', 'cancellation_requested'] as $status): ?>
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

<section class="card" id="blackouts">
    <h2>Blackout Dates</h2>
    <form method="post" action="/hotel-owner/blackouts">
        <div class="row">
            <div>
                <label>Hotel</label>
                <select name="hotel_id" required>
                    <?php foreach ($hotels as $hotel): ?>
                        <option value="<?= (int) $hotel['id'] ?>"><?= htmlspecialchars($hotel['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Room Optional</label>
                <select name="room_id">
                    <option value="0">Entire hotel</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int) $room['id'] ?>"><?= htmlspecialchars($room['hotel_name'] . ' - ' . $room['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Title</label><input name="title" value="Blocked dates"></div>
        </div>
        <div class="row">
            <div><label>Starts</label><input name="starts_at" type="date" required></div>
            <div><label>Ends</label><input name="ends_at" type="date"></div>
            <div><label>Repeat</label><select name="repeat_type"><option value="none">None</option><option value="yearly">Yearly</option></select></div>
        </div>
        <label><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</label>
        <button>Add Blackout</button>
    </form>
    <table>
        <thead><tr><th>Title</th><th>Scope</th><th>Dates</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach (($blackouts ?? []) as $blackout): ?>
            <tr>
                <td><?= htmlspecialchars($blackout['title']) ?></td>
                <td><?= htmlspecialchars($blackout['hotel_name']) ?><br><small><?= htmlspecialchars($blackout['room_name'] ?? 'Entire hotel') ?></small></td>
                <td><?= htmlspecialchars($blackout['starts_at']) ?> to <?= htmlspecialchars($blackout['ends_at']) ?><br><small><?= htmlspecialchars($blackout['repeat_type']) ?></small></td>
                <td><span class="pill"><?= (int) $blackout['status'] === 1 ? 'active' : 'inactive' ?></span></td>
                <td>
                    <form method="post" action="/hotel-owner/blackouts/<?= (int) $blackout['id'] ?>/update">
                        <input type="hidden" name="hotel_id" value="<?= (int) $blackout['hotel_id'] ?>">
                        <input type="hidden" name="room_id" value="<?= (int) ($blackout['room_id'] ?? 0) ?>">
                        <input name="title" value="<?= htmlspecialchars($blackout['title']) ?>">
                        <div class="row">
                            <div><input name="starts_at" type="date" value="<?= htmlspecialchars($blackout['starts_at']) ?>"></div>
                            <div><input name="ends_at" type="date" value="<?= htmlspecialchars($blackout['ends_at']) ?>"></div>
                            <div><select name="repeat_type"><option value="none" <?= $blackout['repeat_type'] === 'none' ? 'selected' : '' ?>>None</option><option value="yearly" <?= $blackout['repeat_type'] === 'yearly' ? 'selected' : '' ?>>Yearly</option></select></div>
                        </div>
                        <label><input style="width:auto;" name="status" type="checkbox" value="1" <?= (int) $blackout['status'] === 1 ? 'checked' : '' ?>> Active</label>
                        <button>Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="reports">
    <h2>Reports</h2>
    <table>
        <thead><tr><th>Status</th><th>Bookings</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach (($reports ?? []) as $report): ?>
            <tr>
                <td><span class="pill"><?= htmlspecialchars($report['booking_status']) ?></span></td>
                <td><?= (int) $report['total_bookings'] ?></td>
                <td>₹<?= number_format((float) $report['total_amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
