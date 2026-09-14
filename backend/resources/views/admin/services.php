<section class="card" id="summary">
    <h2>Booking Summary</h2>
    <div class="grid">
        <div class="stat"><strong><?= (int) ($stats['total_bookings'] ?? 0) ?></strong><span>Total bookings</span></div>
        <div class="stat"><strong>₹<?= number_format((float) ($stats['total_amount'] ?? 0), 2) ?></strong><span>Total value</span></div>
        <div class="stat"><strong><?= (int) ($stats['pending_count'] ?? 0) ?></strong><span>Pending</span></div>
        <div class="stat"><strong><?= (int) ($stats['completed_count'] ?? 0) ?></strong><span>Completed</span></div>
        <div class="stat"><strong><?= (int) ($stats['cancellation_requested_count'] ?? 0) ?></strong><span>Cancel requests</span></div>
        <div class="stat"><strong><?= (int) ($stats['cancelled_count'] ?? 0) ?></strong><span>Cancelled</span></div>
    </div>
    <p><a class="btn secondary" href="/admin/services/bookings/export">Export Bookings CSV</a></p>
</section>

<section class="card" id="reports">
    <h2>Provider Commission Report</h2>
    <p>
        <a class="btn secondary" href="/admin/services/reports/providers/export">Export Provider Earnings</a>
        <a class="btn secondary" href="/admin/services/reports/categories/export">Export Category Performance</a>
        <a class="btn secondary" href="/admin/services/reports/status/export">Export Status Report</a>
    </p>
    <table>
        <thead><tr><th>Provider</th><th>Completed Bookings</th><th>Gross</th><th>Commission %</th><th>Commission</th></tr></thead>
        <tbody>
        <?php foreach ($providerReports as $report): ?>
            <tr>
                <td><?= htmlspecialchars($report['name']) ?></td>
                <td><?= (int) $report['booking_count'] ?></td>
                <td>₹<?= number_format((float) $report['gross_amount'], 2) ?></td>
                <td><?= number_format((float) $report['commission_percent'], 2) ?>%</td>
                <td>₹<?= number_format((float) $report['commission_amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <h2>Category Performance</h2>
    <table>
        <thead><tr><th>Category</th><th>Bookings</th><th>Completed</th><th>Cancelled/Requested</th><th>Gross</th></tr></thead>
        <tbody>
        <?php foreach ($categoryReports as $report): ?>
            <tr>
                <td><?= htmlspecialchars($report['category_name']) ?></td>
                <td><?= (int) $report['booking_count'] ?></td>
                <td><?= (int) $report['completed_count'] ?></td>
                <td><?= (int) $report['cancelled_count'] ?></td>
                <td>₹<?= number_format((float) $report['gross_amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <h2>Cancelled / Rescheduled Analytics</h2>
    <table>
        <thead><tr><th>Status</th><th>Count</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($bookingStatusReports as $report): ?>
            <tr>
                <td><span class="pill"><?= htmlspecialchars($report['booking_status']) ?></span></td>
                <td><?= (int) $report['booking_count'] ?></td>
                <td>₹<?= number_format((float) $report['gross_amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="settlements">
    <h2>Provider Settlements</h2>
    <p><a class="btn secondary" href="/admin/services/settlements/export">Export Settlements CSV</a></p>
    <form method="post" action="/admin/services/settlements">
        <div class="row">
            <div><label>Provider</label><select name="provider_id"><?php foreach ($providers as $provider): ?><option value="<?= $provider['id'] ?>"><?= htmlspecialchars($provider['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Period Start</label><input name="period_start" type="date"></div>
            <div><label>Period End</label><input name="period_end" type="date"></div>
        </div>
        <div class="row">
            <div><label>Reference</label><input name="payment_reference"></div>
            <div><label>Status</label><select name="status"><option value="paid">Paid</option><option value="pending">Pending</option></select></div>
            <div></div>
        </div>
        <label>Note</label><textarea name="note"></textarea>
        <div style="height:12px;"></div><button>Create Settlement From Completed Bookings</button>
    </form>
    <table>
        <thead><tr><th>Provider</th><th>Period</th><th>Gross</th><th>Commission</th><th>Payable</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($settlements as $settlement): ?>
            <tr>
                <td><?= htmlspecialchars($settlement['provider_name']) ?></td>
                <td><?= htmlspecialchars((string) ($settlement['period_start'] ?? '')) ?> - <?= htmlspecialchars((string) ($settlement['period_end'] ?? '')) ?></td>
                <td>₹<?= number_format((float) $settlement['gross_amount'], 2) ?></td>
                <td>₹<?= number_format((float) $settlement['commission_amount'], 2) ?></td>
                <td>₹<?= number_format((float) $settlement['payable_amount'], 2) ?></td>
                <td><span class="pill"><?= htmlspecialchars($settlement['status']) ?></span><br><small><?= htmlspecialchars((string) ($settlement['payment_reference'] ?? '')) ?></small></td>
                <td>
                    <form method="post" action="/admin/services/settlements/<?= $settlement['id'] ?>/status">
                        <select name="status">
                            <?php foreach (['pending', 'paid', 'cancelled'] as $status): ?>
                                <option value="<?= $status ?>" <?= $settlement['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="payment_reference" placeholder="Reference" value="<?= htmlspecialchars((string) ($settlement['payment_reference'] ?? '')) ?>">
                        <textarea name="note" placeholder="Note"><?= htmlspecialchars((string) ($settlement['note'] ?? '')) ?></textarea>
                        <button>Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="categories">
    <h2>Service Categories</h2>
    <form method="post" action="/admin/services/categories">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Icon Key</label><input name="icon" placeholder="electrician / plumber"></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
        </div>
        <label>Description</label><textarea name="description"></textarea>
        <label><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</label>
        <div style="height:12px;"></div><button>Add Category</button>
    </form>
    <table>
        <thead><tr><th>Name</th><th>Description</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= htmlspecialchars($category['name']) ?></td>
                <td><?= htmlspecialchars((string) ($category['description'] ?? '')) ?></td>
                <td><span class="pill"><?= !empty($category['status']) ? 'active' : 'inactive' ?></span></td>
                <td>
                    <form method="post" action="/admin/services/categories/<?= $category['id'] ?>/update">
                        <input name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
                        <input name="icon" placeholder="Icon" value="<?= htmlspecialchars((string) ($category['icon'] ?? '')) ?>">
                        <input name="sort_order" type="number" value="<?= (int) $category['sort_order'] ?>">
                        <textarea name="description" placeholder="Description"><?= htmlspecialchars((string) ($category['description'] ?? '')) ?></textarea>
                        <label><input style="width:auto;" name="status" type="checkbox" value="1" <?= !empty($category['status']) ? 'checked' : '' ?>> Active</label>
                        <button>Save</button>
                    </form>
                    <form method="post" action="/admin/services/categories/<?= $category['id'] ?>/archive" style="margin-top:6px;">
                        <button class="danger">Archive</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="providers">
    <h2>Service Providers</h2>
    <form method="post" action="/admin/services/providers">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Phone</label><input name="phone"></div>
            <div><label>Email</label><input name="email"></div>
        </div>
        <div class="row">
            <div><label>Area</label><input name="area" placeholder="Lucknow Central"></div>
            <div><label>Commission %</label><input name="commission_percent" type="number" step="0.01" value="0"></div>
            <div><label>Panel Password</label><input name="password" type="password"></div>
        </div>
        <div class="row">
            <div>
                <label>Zone</label>
                <select name="zone_id">
                    <option value="">All zones</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>
            <div></div>
        </div>
        <label><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</label>
        <div style="height:12px;"></div><button>Add Provider</button>
    </form>
    <table>
        <thead><tr><th>Name</th><th>Contact</th><th>Area</th><th>Zone</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($providers as $provider): ?>
            <tr>
                <td><?= htmlspecialchars($provider['name']) ?></td>
                <td><?= htmlspecialchars((string) ($provider['phone'] ?? '')) ?><br><small><?= htmlspecialchars((string) ($provider['email'] ?? '')) ?></small></td>
                <td><?= htmlspecialchars((string) ($provider['area'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string) ($provider['zone_name'] ?? 'All zones')) ?></td>
                <td><span class="pill"><?= !empty($provider['status']) ? 'active' : 'inactive' ?></span></td>
                <td>
                    <form method="post" action="/admin/services/providers/<?= $provider['id'] ?>/update">
                        <input name="name" value="<?= htmlspecialchars($provider['name']) ?>" required>
                        <input name="phone" placeholder="Phone" value="<?= htmlspecialchars((string) ($provider['phone'] ?? '')) ?>">
                        <input name="email" placeholder="Email" value="<?= htmlspecialchars((string) ($provider['email'] ?? '')) ?>">
                        <input name="area" placeholder="Area" value="<?= htmlspecialchars((string) ($provider['area'] ?? '')) ?>">
                        <select name="zone_id">
                            <option value="">All zones</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= $zone['id'] ?>" <?= (int) ($provider['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="commission_percent" type="number" step="0.01" value="<?= htmlspecialchars((string) ($provider['commission_percent'] ?? 0)) ?>">
                        <input name="password" type="password" placeholder="New password">
                        <label><input style="width:auto;" name="status" type="checkbox" value="1" <?= !empty($provider['status']) ? 'checked' : '' ?>> Active</label>
                        <button>Save</button>
                    </form>
                    <form method="post" action="/admin/services/providers/<?= $provider['id'] ?>/archive" style="margin-top:6px;">
                        <button class="danger">Archive</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="services">
    <h2>Services</h2>
    <form method="post" action="/admin/services">
        <div class="row">
            <div>
                <label>Category</label>
                <select name="category_id">
                    <option value="">None</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Default Provider</label>
                <select name="provider_id">
                    <option value="">No provider</option>
                    <?php foreach ($providers as $provider): ?>
                        <option value="<?= $provider['id'] ?>"><?= htmlspecialchars($provider['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Name</label><input name="name" required></div>
        </div>
        <div class="row">
            <div>
                <label>Zone</label>
                <select name="zone_id">
                    <option value="">All zones</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>
            <div></div>
        </div>
        <div class="row">
            <div><label>Duration Minutes</label><input name="duration_minutes" type="number" value="60"></div>
            <div><label>Warranty Days</label><input name="warranty_days" type="number" min="0" value="0"></div>
            <div></div>
        </div>
        <div class="row">
            <div><label>Price</label><input name="price" type="number" step="0.01" required></div>
            <div><label>Discount Price</label><input name="discount_price" type="number" step="0.01"></div>
            <div></div>
        </div>
        <label>Flags</label><div><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active <input style="width:auto;" name="is_featured" type="checkbox" value="1"> Featured</div>
        <label>Description</label><textarea name="description"></textarea>
        <label>Customer Checklist</label><textarea name="checklist" placeholder="One preparation item per line, shown before booking"></textarea>
        <div style="height:12px;"></div><button>Add Service</button>
    </form>
    <table>
        <thead><tr><th>Service</th><th>Category</th><th>Price</th><th>Zone</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($services as $service): ?>
            <tr>
                <td><?= htmlspecialchars($service['name']) ?></td>
                <td><?= htmlspecialchars((string) ($service['category_name'] ?? '-')) ?><br><small><?= htmlspecialchars((string) ($service['provider_name'] ?? 'No provider')) ?></small></td>
                <td>₹<?= number_format((float) $service['price'], 2) ?><br><small><?= (int) ($service['warranty_days'] ?? 0) ?> day warranty</small></td>
                <td><?= htmlspecialchars((string) ($service['zone_name'] ?? 'All zones')) ?></td>
                <td><span class="pill"><?= !empty($service['status']) ? 'active' : 'inactive' ?></span></td>
                <td>
                    <form method="post" action="/admin/services/<?= $service['id'] ?>/update">
                        <select name="category_id">
                            <option value="">None</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (int) ($service['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="provider_id">
                            <option value="">No provider</option>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?= $provider['id'] ?>" <?= (int) ($service['provider_id'] ?? 0) === (int) $provider['id'] ? 'selected' : '' ?>><?= htmlspecialchars($provider['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="zone_id">
                            <option value="">All zones</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= $zone['id'] ?>" <?= (int) ($service['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="name" value="<?= htmlspecialchars($service['name']) ?>" required>
                        <input name="duration_minutes" type="number" value="<?= (int) $service['duration_minutes'] ?>">
                        <input name="warranty_days" type="number" min="0" value="<?= (int) ($service['warranty_days'] ?? 0) ?>" placeholder="Warranty days">
                        <input name="price" type="number" step="0.01" value="<?= htmlspecialchars((string) $service['price']) ?>">
                        <input name="discount_price" type="number" step="0.01" value="<?= htmlspecialchars((string) ($service['discount_price'] ?? '')) ?>">
                        <textarea name="description" placeholder="Description"><?= htmlspecialchars((string) ($service['description'] ?? '')) ?></textarea>
                        <?php $checklist = json_decode((string) ($service['checklist_json'] ?? ''), true); ?>
                        <textarea name="checklist" placeholder="Checklist, one item per line"><?= htmlspecialchars(implode("\n", is_array($checklist) ? array_map('strval', $checklist) : [])) ?></textarea>
                        <label><input style="width:auto;" name="status" type="checkbox" value="1" <?= !empty($service['status']) ? 'checked' : '' ?>> Active</label>
                        <label><input style="width:auto;" name="is_featured" type="checkbox" value="1" <?= !empty($service['is_featured']) ? 'checked' : '' ?>> Featured</label>
                        <button>Save</button>
                    </form>
                    <form method="post" action="/admin/services/<?= $service['id'] ?>/archive" style="margin-top:6px;">
                        <button class="danger">Archive</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="addons">
    <h2>Service Add-ons</h2>
    <form method="post" action="/admin/services/addons">
        <div class="row">
            <div><label>Service</label><select name="service_id"><?php foreach ($services as $service): ?><option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Name</label><input name="name" required></div>
            <div><label>Price</label><input name="price" type="number" step="0.01" value="0"></div>
        </div>
        <div class="row">
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
            <div><label>Status</label><div><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</div></div>
            <div></div>
        </div>
        <label>Description</label><textarea name="description"></textarea>
        <div style="height:12px;"></div><button>Add Add-on</button>
    </form>
    <table>
        <thead><tr><th>Service</th><th>Add-on</th><th>Price</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($addons as $addon): ?>
            <tr>
                <td><?= htmlspecialchars($addon['service_name']) ?></td>
                <td><?= htmlspecialchars($addon['name']) ?><br><small><?= htmlspecialchars((string) ($addon['description'] ?? '')) ?></small></td>
                <td>₹<?= number_format((float) $addon['price'], 2) ?></td>
                <td><span class="pill"><?= !empty($addon['status']) ? 'active' : 'inactive' ?></span></td>
                <td>
                    <form method="post" action="/admin/services/addons/<?= $addon['id'] ?>/update">
                        <select name="service_id"><?php foreach ($services as $service): ?><option value="<?= $service['id'] ?>" <?= (int) $addon['service_id'] === (int) $service['id'] ? 'selected' : '' ?>><?= htmlspecialchars($service['name']) ?></option><?php endforeach; ?></select>
                        <input name="name" value="<?= htmlspecialchars($addon['name']) ?>" required>
                        <input name="price" type="number" step="0.01" value="<?= htmlspecialchars((string) $addon['price']) ?>">
                        <input name="sort_order" type="number" value="<?= (int) $addon['sort_order'] ?>">
                        <textarea name="description"><?= htmlspecialchars((string) ($addon['description'] ?? '')) ?></textarea>
                        <label><input style="width:auto;" name="status" type="checkbox" value="1" <?= !empty($addon['status']) ? 'checked' : '' ?>> Active</label>
                        <button>Save</button>
                    </form>
                    <form method="post" action="/admin/services/addons/<?= $addon['id'] ?>/archive" style="margin-top:6px;">
                        <button class="danger">Archive</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="slots">
    <h2>Service Slots</h2>
    <form method="post" action="/admin/services/slots">
        <div class="row">
            <div><label>Service</label><select name="service_id"><?php foreach ($services as $service): ?><option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Provider</label><select name="provider_id"><option value="">Service default</option><?php foreach ($providers as $provider): ?><option value="<?= $provider['id'] ?>"><?= htmlspecialchars($provider['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Day</label><select name="day_of_week"><?php foreach ([0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $day => $name): ?><option value="<?= $day ?>"><?= $name ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="row">
            <div><label>Start Time</label><input name="start_time" type="time" value="09:00"></div>
            <div><label>End Time</label><input name="end_time" type="time" value="10:00"></div>
            <div><label>Capacity</label><input name="capacity" type="number" value="1" min="1"></div>
        </div>
        <label><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</label>
        <div style="height:12px;"></div><button>Add Slot</button>
    </form>
    <table>
        <thead><tr><th>Service</th><th>Provider</th><th>Day</th><th>Time</th><th>Capacity</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($slots as $slot): ?>
            <tr>
                <td><?= htmlspecialchars($slot['service_name']) ?></td>
                <td><?= htmlspecialchars((string) ($slot['provider_name'] ?? 'Service default')) ?></td>
                <td><?= htmlspecialchars([0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'][(int) $slot['day_of_week']] ?? 'Sunday') ?></td>
                <td><?= htmlspecialchars($slot['start_time']) ?> - <?= htmlspecialchars($slot['end_time']) ?></td>
                <td><?= (int) $slot['capacity'] ?></td>
                <td><span class="pill"><?= !empty($slot['status']) ? 'active' : 'inactive' ?></span></td>
                <td>
                    <form method="post" action="/admin/services/slots/<?= $slot['id'] ?>/update">
                        <select name="service_id"><?php foreach ($services as $service): ?><option value="<?= $service['id'] ?>" <?= (int) $slot['service_id'] === (int) $service['id'] ? 'selected' : '' ?>><?= htmlspecialchars($service['name']) ?></option><?php endforeach; ?></select>
                        <select name="provider_id"><option value="">Service default</option><?php foreach ($providers as $provider): ?><option value="<?= $provider['id'] ?>" <?= (int) ($slot['provider_id'] ?? 0) === (int) $provider['id'] ? 'selected' : '' ?>><?= htmlspecialchars($provider['name']) ?></option><?php endforeach; ?></select>
                        <select name="day_of_week"><?php foreach ([0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $day => $name): ?><option value="<?= $day ?>" <?= (int) $slot['day_of_week'] === $day ? 'selected' : '' ?>><?= $name ?></option><?php endforeach; ?></select>
                        <input name="start_time" type="time" value="<?= htmlspecialchars($slot['start_time']) ?>">
                        <input name="end_time" type="time" value="<?= htmlspecialchars($slot['end_time']) ?>">
                        <input name="capacity" type="number" value="<?= (int) $slot['capacity'] ?>" min="1">
                        <label><input style="width:auto;" name="status" type="checkbox" value="1" <?= !empty($slot['status']) ? 'checked' : '' ?>> Active</label>
                        <button>Save</button>
                    </form>
                    <form method="post" action="/admin/services/slots/<?= $slot['id'] ?>/archive" style="margin-top:6px;">
                        <button class="danger">Archive</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="blackouts">
    <h2>Blackout / Holiday Dates</h2>
    <form method="post" action="/admin/services/blackouts">
        <div class="row">
            <div><label>Start Date</label><input name="blackout_date" type="date" required></div>
            <div><label>End Date</label><input name="end_date" type="date"></div>
            <div><label>Recurrence</label><select name="recurrence"><option value="none">None</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div>
        </div>
        <div class="row">
            <div><label>Service</label><select name="service_id"><option value="">All services</option><?php foreach ($services as $service): ?><option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Provider</label><select name="provider_id"><option value="">All providers</option><?php foreach ($providers as $provider): ?><option value="<?= $provider['id'] ?>"><?= htmlspecialchars($provider['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Partial Day Time</label><input name="start_time" type="time"> <input name="end_time" type="time"></div>
        </div>
        <label>Reason</label><input name="reason" placeholder="Holiday / provider unavailable">
        <label><input style="width:auto;" name="status" type="checkbox" value="1" checked> Active</label>
        <div style="height:12px;"></div><button>Add Blackout</button>
    </form>
    <table>
        <thead><tr><th>Date / Recurrence</th><th>Scope</th><th>Time</th><th>Reason</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($blackouts as $blackout): ?>
            <tr>
                <td><?= htmlspecialchars($blackout['blackout_date']) ?><?= !empty($blackout['end_date']) ? ' - ' . htmlspecialchars($blackout['end_date']) : '' ?><br><small><?= htmlspecialchars($blackout['recurrence'] ?? 'none') ?></small></td>
                <td><?= htmlspecialchars((string) ($blackout['service_name'] ?? 'All services')) ?><br><small><?= htmlspecialchars((string) ($blackout['provider_name'] ?? 'All providers')) ?></small></td>
                <td><?= htmlspecialchars((string) ($blackout['start_time'] ?? '')) ?><?= !empty($blackout['end_time']) ? ' - ' . htmlspecialchars((string) $blackout['end_time']) : '' ?></td>
                <td><?= htmlspecialchars((string) ($blackout['reason'] ?? '')) ?></td>
                <td><span class="pill"><?= !empty($blackout['status']) ? 'active' : 'inactive' ?></span></td>
                <td>
                    <form method="post" action="/admin/services/blackouts/<?= $blackout['id'] ?>/update">
                        <input name="blackout_date" type="date" value="<?= htmlspecialchars($blackout['blackout_date']) ?>">
                        <input name="end_date" type="date" value="<?= htmlspecialchars((string) ($blackout['end_date'] ?? '')) ?>">
                        <select name="recurrence"><?php foreach (['none', 'weekly', 'monthly', 'yearly'] as $recurrence): ?><option value="<?= $recurrence ?>" <?= ($blackout['recurrence'] ?? 'none') === $recurrence ? 'selected' : '' ?>><?= htmlspecialchars($recurrence) ?></option><?php endforeach; ?></select>
                        <select name="service_id"><option value="">All services</option><?php foreach ($services as $service): ?><option value="<?= $service['id'] ?>" <?= (int) ($blackout['service_id'] ?? 0) === (int) $service['id'] ? 'selected' : '' ?>><?= htmlspecialchars($service['name']) ?></option><?php endforeach; ?></select>
                        <select name="provider_id"><option value="">All providers</option><?php foreach ($providers as $provider): ?><option value="<?= $provider['id'] ?>" <?= (int) ($blackout['provider_id'] ?? 0) === (int) $provider['id'] ? 'selected' : '' ?>><?= htmlspecialchars($provider['name']) ?></option><?php endforeach; ?></select>
                        <input name="start_time" type="time" value="<?= htmlspecialchars((string) ($blackout['start_time'] ?? '')) ?>">
                        <input name="end_time" type="time" value="<?= htmlspecialchars((string) ($blackout['end_time'] ?? '')) ?>">
                        <input name="reason" value="<?= htmlspecialchars((string) ($blackout['reason'] ?? '')) ?>">
                        <label><input style="width:auto;" name="status" type="checkbox" value="1" <?= !empty($blackout['status']) ? 'checked' : '' ?>> Active</label>
                        <button>Save</button>
                    </form>
                    <form method="post" action="/admin/services/blackouts/<?= $blackout['id'] ?>/archive" style="margin-top:6px;">
                        <button class="danger">Archive</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="bookings">
    <h2>Recent Bookings</h2>
    <table>
        <thead><tr><th>Booking</th><th>Service</th><th>Provider</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?= htmlspecialchars($booking['booking_number']) ?></td>
                <td><?= htmlspecialchars((string) ($booking['service_name'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string) ($booking['provider_name'] ?? '-')) ?><br><small><?= htmlspecialchars(trim((string) ($booking['slot_start_time'] ?? '') . '-' . (string) ($booking['slot_end_time'] ?? ''), '-')) ?></small></td>
                <td><?= htmlspecialchars($booking['customer_name']) ?><br><small><?= htmlspecialchars($booking['customer_phone']) ?></small></td>
                <td><?= htmlspecialchars((string) ($booking['preferred_date'] ?? '')) ?> <?= htmlspecialchars((string) ($booking['preferred_time'] ?? '')) ?></td>
                <td>₹<?= number_format((float) $booking['amount'], 2) ?><?php if (!empty($booking['addon_total'])): ?><br><small>Add-ons: ₹<?= number_format((float) $booking['addon_total'], 2) ?></small><?php endif; ?></td>
                <td><span class="pill"><?= htmlspecialchars($booking['booking_status']) ?></span><br><small><?= htmlspecialchars($booking['payment_method']) ?> / <?= htmlspecialchars($booking['payment_status']) ?></small><?php if (!empty($booking['warranty_until'])): ?><br><small>Warranty until <?= htmlspecialchars((string) $booking['warranty_until']) ?></small><?php endif; ?></td>
                <td>
                    <form method="post" action="/admin/services/bookings/<?= $booking['id'] ?>/status">
                        <select name="booking_status">
                            <?php foreach (['pending', 'accepted', 'ongoing', 'completed', 'cancelled', 'cancellation_requested'] as $status): ?>
                                <option value="<?= $status ?>" <?= $booking['booking_status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="admin_note" placeholder="Admin note" value="<?= htmlspecialchars((string) ($booking['admin_note'] ?? '')) ?>">
                        <button>Update</button>
                    </form>
                    <?php if (!empty($booking['payment_transaction_id'])): ?>
                    <form method="post" action="/admin/services/payments/<?= $booking['payment_transaction_id'] ?>/status" style="margin-top:6px;">
                        <select name="status">
                            <?php foreach (['pending_verification', 'verified', 'rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= ($booking['transaction_status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="note" placeholder="Payment note / reference" value="<?= htmlspecialchars((string) ($booking['payment_reference'] ?? '')) ?>">
                        <button>Payment</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
