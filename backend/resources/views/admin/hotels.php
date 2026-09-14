<div class="grid" id="summary">
    <div class="card stat"><strong><?= count($hotels) ?></strong><span>Hotels</span></div>
    <div class="card stat"><strong><?= count($rooms) ?></strong><span>Room Types</span></div>
    <div class="card stat"><strong><?= count($bookings) ?></strong><span>Recent Bookings</span></div>
    <div class="card stat"><strong><?= count($owners) ?></strong><span>Owners</span></div>
    <div class="card stat"><strong><?= count($blackouts ?? []) ?></strong><span>Blackouts</span></div>
</div>

<div class="card" id="categories">
    <h2>Hotel Categories</h2>
    <form method="post" action="/admin/hotels/categories" class="row">
        <div><label>Name</label><input name="name" required placeholder="Luxury Hotel"></div>
        <div><label>Description</label><input name="description" placeholder="Premium stays"></div>
        <div><label>Sort</label><input name="sort_order" type="number" value="0"></div>
        <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        <div style="align-self:end"><button>Add Category</button></div>
    </form>
    <table>
        <thead><tr><th>Name</th><th>Description</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <form method="post" action="/admin/hotels/categories/<?= (int) $category['id'] ?>/update">
                    <td><input name="name" value="<?= htmlspecialchars($category['name']) ?>"></td>
                    <td><input name="description" value="<?= htmlspecialchars($category['description'] ?? '') ?>"></td>
                    <td><select name="status"><option value="1" <?= (int) $category['status'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int) $category['status'] === 0 ? 'selected' : '' ?>>Inactive</option></select></td>
                    <td><input type="hidden" name="sort_order" value="<?= (int) $category['sort_order'] ?>"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="owners">
    <h2>Hotel Owners</h2>
    <form method="post" action="/admin/hotels/owners" class="row">
        <div><label>Name</label><input name="name" required></div>
        <div><label>Phone</label><input name="phone"></div>
        <div><label>Email</label><input name="email" type="email"></div>
        <div><label>Password</label><input name="password" type="password"></div>
        <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        <div style="align-self:end"><button>Add Owner</button></div>
    </form>
    <table>
        <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($owners as $owner): ?>
            <tr>
                <form method="post" action="/admin/hotels/owners/<?= (int) $owner['id'] ?>/update">
                    <td><input name="name" value="<?= htmlspecialchars($owner['name']) ?>"></td>
                    <td><input name="phone" value="<?= htmlspecialchars($owner['phone'] ?? '') ?>"></td>
                    <td><input name="email" value="<?= htmlspecialchars($owner['email'] ?? '') ?>"></td>
                    <td><select name="status"><option value="1" <?= (int) $owner['status'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int) $owner['status'] === 0 ? 'selected' : '' ?>>Inactive</option></select></td>
                    <td><input name="password" type="password" placeholder="New password"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="hotels">
    <h2>Hotels</h2>
    <form method="post" action="/admin/hotels/hotels" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Category</label><select name="category_id"><option value="">None</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Owner</label><select name="owner_id"><option value="">Admin Managed</option><?php foreach ($owners as $owner): ?><option value="<?= (int) $owner['id'] ?>"><?= htmlspecialchars($owner['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Zone</label><select name="zone_id"><option value="">All Zones</option><?php foreach (($zones ?? []) as $zone): ?><option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>City</label><input name="city" required value="Lucknow"></div>
            <div><label>Area</label><input name="area"></div>
            <div><label>Star Rating</label><input name="star_rating" type="number" step="0.1" value="4.0"></div>
            <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div><label>Featured</label><select name="is_featured"><option value="0">No</option><option value="1">Yes</option></select></div>
            <div><label>Thumbnail</label><input name="thumbnail" type="file" accept="image/*"></div>
        </div>
        <label>Address</label><input name="address" required>
        <label>Description</label><textarea name="description"></textarea>
        <label>Amenities, one per line</label><textarea name="amenities" placeholder="WiFi&#10;Breakfast&#10;Parking"></textarea>
        <label>Gallery</label><input name="gallery[]" type="file" accept="image/*" multiple>
        <button style="margin-top:12px">Add Hotel</button>
    </form>
    <table>
        <thead><tr><th>Hotel</th><th>Zone</th><th>City</th><th>Owner</th><th>Rating</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($hotels as $hotel): ?>
            <tr>
                <td><strong><?= htmlspecialchars($hotel['name']) ?></strong><br><span class="pill"><?= htmlspecialchars($hotel['category_name'] ?? 'Uncategorized') ?></span></td>
                <td><?= htmlspecialchars($hotel['zone_name'] ?? 'All Zones') ?></td>
                <td><?= htmlspecialchars($hotel['city']) ?><br><?= htmlspecialchars($hotel['area'] ?? '') ?></td>
                <td><?= htmlspecialchars($hotel['owner_name'] ?? 'Admin Managed') ?></td>
                <td><?= htmlspecialchars((string) $hotel['star_rating']) ?> star<br><?= htmlspecialchars((string) $hotel['rating']) ?> customer</td>
                <td><span class="pill"><?= (int) $hotel['status'] === 1 ? 'active' : 'inactive' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="rooms">
    <h2>Room Types</h2>
    <form method="post" action="/admin/hotels/rooms" enctype="multipart/form-data">
        <div class="row">
            <div><label>Hotel</label><select name="hotel_id" required><?php foreach ($hotels as $hotel): ?><option value="<?= (int) $hotel['id'] ?>"><?= htmlspecialchars($hotel['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Room Name</label><input name="name" required placeholder="Deluxe King"></div>
            <div><label>Total Rooms</label><input name="total_rooms" type="number" value="5"></div>
            <div><label>Adults</label><input name="capacity_adults" type="number" value="2"></div>
            <div><label>Children</label><input name="capacity_children" type="number" value="0"></div>
            <div><label>Price/Night</label><input name="price_per_night" type="number" step="0.01" required></div>
            <div><label>Discount Price</label><input name="discount_price" type="number" step="0.01"></div>
            <div><label>Tax %</label><input name="tax_percent" type="number" step="0.01" value="12"></div>
            <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div><label>Image</label><input name="thumbnail" type="file" accept="image/*"></div>
        </div>
        <label>Description</label><textarea name="description"></textarea>
        <label>Room amenities, one per line</label><textarea name="room_amenities" placeholder="King bed&#10;Air conditioning&#10;City view"></textarea>
        <button style="margin-top:12px">Add Room</button>
    </form>
    <table>
        <thead><tr><th>Room</th><th>Hotel</th><th>Capacity</th><th>Inventory</th><th>Price</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><strong><?= htmlspecialchars($room['name']) ?></strong><br><?= htmlspecialchars($room['description'] ?? '') ?></td>
                <td><?= htmlspecialchars($room['hotel_name']) ?></td>
                <td><?= (int) $room['capacity_adults'] ?> adults, <?= (int) $room['capacity_children'] ?> children</td>
                <td><?= (int) $room['total_rooms'] ?> rooms</td>
                <td>₹<?= htmlspecialchars((string) ($room['discount_price'] ?: $room['price_per_night'])) ?><br><?= htmlspecialchars((string) $room['tax_percent']) ?>% tax</td>
                <td><span class="pill"><?= (int) $room['status'] === 1 ? 'active' : 'inactive' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="blackouts">
    <h2>Availability Blackouts</h2>
    <form method="post" action="/admin/hotels/blackouts" class="row">
        <div><label>Title</label><input name="title" value="Blocked dates" required></div>
        <div><label>Hotel</label><select name="hotel_id"><option value="">All Hotels</option><?php foreach ($hotels as $hotel): ?><option value="<?= (int) $hotel['id'] ?>"><?= htmlspecialchars($hotel['name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Room</label><select name="room_id"><option value="">All Rooms</option><?php foreach ($rooms as $room): ?><option value="<?= (int) $room['id'] ?>"><?= htmlspecialchars($room['hotel_name'] . ' - ' . $room['name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Start</label><input name="starts_at" type="date" required></div>
        <div><label>End</label><input name="ends_at" type="date" required></div>
        <div><label>Repeat</label><select name="repeat_type"><option value="none">None</option><option value="yearly">Yearly</option></select></div>
        <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        <div style="align-self:end"><button>Add Blackout</button></div>
    </form>
    <table>
        <thead><tr><th>Scope</th><th>Title</th><th>Dates</th><th>Repeat</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach (($blackouts ?? []) as $blackout): ?>
            <tr>
                <form method="post" action="/admin/hotels/blackouts/<?= (int) $blackout['id'] ?>/update">
                    <td>
                        <select name="hotel_id"><option value="">All Hotels</option><?php foreach ($hotels as $hotel): ?><option value="<?= (int) $hotel['id'] ?>" <?= (int) ($blackout['hotel_id'] ?? 0) === (int) $hotel['id'] ? 'selected' : '' ?>><?= htmlspecialchars($hotel['name']) ?></option><?php endforeach; ?></select>
                        <select name="room_id"><option value="">All Rooms</option><?php foreach ($rooms as $room): ?><option value="<?= (int) $room['id'] ?>" <?= (int) ($blackout['room_id'] ?? 0) === (int) $room['id'] ? 'selected' : '' ?>><?= htmlspecialchars($room['hotel_name'] . ' - ' . $room['name']) ?></option><?php endforeach; ?></select>
                    </td>
                    <td><input name="title" value="<?= htmlspecialchars($blackout['title']) ?>"></td>
                    <td><input name="starts_at" type="date" value="<?= htmlspecialchars($blackout['starts_at']) ?>"><input name="ends_at" type="date" value="<?= htmlspecialchars($blackout['ends_at']) ?>"></td>
                    <td><select name="repeat_type"><option value="none" <?= $blackout['repeat_type'] === 'none' ? 'selected' : '' ?>>None</option><option value="yearly" <?= $blackout['repeat_type'] === 'yearly' ? 'selected' : '' ?>>Yearly</option></select></td>
                    <td><select name="status"><option value="1" <?= (int) $blackout['status'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int) $blackout['status'] === 0 ? 'selected' : '' ?>>Inactive</option></select></td>
                    <td><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="bookings">
    <h2>Hotel Bookings</h2>
    <table>
        <thead><tr><th>Booking</th><th>Guest</th><th>Stay</th><th>Amount</th><th>Payment</th><th>Refund</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <form method="post" action="/admin/hotels/bookings/<?= (int) $booking['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong><br><?= htmlspecialchars($booking['hotel_name']) ?><br><?= htmlspecialchars($booking['room_name']) ?></td>
                    <td><?= htmlspecialchars($booking['customer_name']) ?><br><?= htmlspecialchars($booking['customer_phone']) ?></td>
                    <td><?= htmlspecialchars($booking['check_in']) ?> to <?= htmlspecialchars($booking['check_out']) ?><br><?= (int) $booking['rooms'] ?> room(s), <?= (int) $booking['nights'] ?> night(s)</td>
                    <td>₹<?= htmlspecialchars((string) $booking['grand_total']) ?></td>
                    <td>
                        <select name="payment_status">
                            <?php foreach (['unpaid','pending_verification','paid','failed','refunded'] as $status): ?>
                                <option value="<?= $status ?>" <?= $booking['payment_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <span class="pill"><?= htmlspecialchars($booking['refund_status'] ?? 'none') ?></span>
                        <?php if ((float) ($booking['refund_amount'] ?? 0) > 0): ?><br>₹<?= number_format((float) $booking['refund_amount'], 2) ?><?php endif; ?>
                        <?php if (!empty($booking['refund_note'])): ?><br><small><?= htmlspecialchars($booking['refund_note']) ?></small><?php endif; ?>
                    </td>
                    <td>
                        <select name="booking_status">
                            <?php foreach (['pending','confirmed','checked_in','completed','cancellation_requested','cancelled','rejected'] as $status): ?>
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
