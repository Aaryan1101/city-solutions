<div class="grid" id="summary">
    <div class="card stat"><strong><?= count($drivers) ?></strong><span>Taxi Drivers</span></div>
    <div class="card stat"><strong><?= count($rides) ?></strong><span>Recent Rides</span></div>
    <div class="card stat"><strong><?= count(array_filter($rides, static fn ($row) => ($row['ride_status'] ?? '') === 'requested')) ?></strong><span>Requested</span></div>
    <div class="card stat"><strong><?= count(array_filter($rides, static fn ($row) => in_array(($row['ride_status'] ?? ''), ['accepted','arrived','started'], true))) ?></strong><span>Active Trips</span></div>
</div>

<section class="card" id="taxi-settings">
    <div class="section-heading">
        <div><span class="eyebrow">Dispatch configuration</span><h2>Taxi Operations</h2><p>Route quotes, customer payment choices and driver settlement are controlled here.</p></div>
        <span class="pill">Independent Taxi module</span>
    </div>
    <form method="post" action="/admin/taxi/settings">
        <div class="row">
            <div style="grid-column:span 2"><label>Google Routes server API key</label><input name="google_routes_api_key" type="password" value="<?= htmlspecialchars($taxiSettings['google_routes_api_key'] ?? '') ?>" autocomplete="off"><small>Enable Routes API for this server-restricted key. Without it, quotes are marked as geographic fallback estimates.</small></div>
            <div><label>Driver commission %</label><input name="driver_commission_percent" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars($taxiSettings['driver_commission_percent'] ?? '15') ?>"></div>
        </div>
        <div class="row">
            <div><label>Quote validity (minutes)</label><input name="quote_valid_minutes" type="number" min="2" max="15" value="<?= htmlspecialchars($taxiSettings['quote_valid_minutes'] ?? '5') ?>"></div>
            <div><label>Support phone</label><input name="support_phone" value="<?= htmlspecialchars($taxiSettings['support_phone'] ?? '') ?>"></div>
            <div><label>Payment methods</label><label><input type="checkbox" name="cash_enabled" value="1" <?= ($taxiSettings['cash_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Cash</label><label><input type="checkbox" name="wallet_enabled" value="1" <?= ($taxiSettings['wallet_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Wallet</label></div>
            <div><label>Testing fallback</label><label><input type="checkbox" name="allow_fallback_quotes" value="1" <?= ($taxiSettings['allow_fallback_quotes'] ?? '0') === '1' ? 'checked' : '' ?>> Allow approximate geographic quotes</label><small>Keep disabled in production so a Routes outage cannot create inaccurate fares.</small></div>
        </div>
        <button>Save Taxi Settings</button>
    </form>
</section>

<section class="card" id="vehicle-types">
    <h2>Vehicle Types & Fare Rules</h2>
    <p class="muted">These cab types are sent to the customer app. Disable a type to hide it without breaking old rides.</p>
    <form method="post" action="/admin/taxi/vehicle-types">
        <div class="row">
            <div><label>Name</label><input name="name" placeholder="Mini, Sedan, SUV" required></div>
            <div><label>Seats</label><input name="seats" type="number" min="1" value="4" required></div>
            <div><label>Icon Key</label><input name="icon" value="local_taxi"></div>
        </div>
        <div class="row">
            <div><label>Base Fare</label><input name="base_fare" type="number" min="0" step="0.01" value="0"></div>
            <div><label>Per KM Fare</label><input name="per_km_fare" type="number" min="0" step="0.01" value="14"></div>
            <div><label>Per Minute Fare</label><input name="per_minute_fare" type="number" min="0" step="0.01" value="1.5"></div>
        </div>
        <div class="row">
            <div><label>Minimum Fare</label><input name="minimum_fare" type="number" min="0" step="0.01" value="75"></div>
            <div><label>Cancellation Fee</label><input name="cancellation_fee" type="number" min="0" step="0.01" value="35"></div>
            <div><label>Service Fee</label><input name="service_fee" type="number" min="0" step="0.01" value="0"></div>
            <div><label>Waiting Fee / minute</label><input name="waiting_fee_per_minute" type="number" min="0" step="0.01" value="0"></div>
        </div>
        <div class="row">
            <div><label>Included Wait Minutes</label><input name="included_wait_minutes" type="number" min="0" value="3"></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
        </div>
        <div class="row">
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div style="align-self:end;"><button>Add Vehicle Type</button></div>
        </div>
    </form>
    <div style="height:16px;"></div>
    <table>
        <thead><tr><th>Type</th><th>Fare</th><th>Control</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($vehicleTypes as $type): ?>
            <tr>
                <td>
                    <input form="taxi-type-<?= (int) $type['id'] ?>" name="name" value="<?= htmlspecialchars($type['name']) ?>" required>
                    <div style="height:6px;"></div>
                    <input form="taxi-type-<?= (int) $type['id'] ?>" name="icon" value="<?= htmlspecialchars($type['icon'] ?? 'local_taxi') ?>" placeholder="Icon key">
                    <div style="height:6px;"></div>
                    <span class="pill"><?= htmlspecialchars($type['slug'] ?? '') ?></span>
                </td>
                <td>
                    <label>Base</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="base_fare" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $type['base_fare']) ?>">
                    <div style="height:6px;"></div>
                    <label>Per KM</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="per_km_fare" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $type['per_km_fare']) ?>">
                    <div style="height:6px;"></div>
                    <label>Per Minute</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="per_minute_fare" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $type['per_minute_fare']) ?>">
                </td>
                <td>
                    <label>Seats</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="seats" type="number" min="1" value="<?= (int) $type['seats'] ?>">
                    <div style="height:6px;"></div>
                    <label>Minimum</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="minimum_fare" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $type['minimum_fare']) ?>">
                    <div style="height:6px;"></div>
                    <label>Cancel Fee</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="cancellation_fee" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $type['cancellation_fee']) ?>">
                    <div style="height:6px;"></div>
                    <label>Service Fee</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="service_fee" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($type['service_fee'] ?? 0)) ?>">
                    <div style="height:6px;"></div>
                    <label>Wait / min</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="waiting_fee_per_minute" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($type['waiting_fee_per_minute'] ?? 0)) ?>">
                    <div style="height:6px;"></div>
                    <label>Free wait</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="included_wait_minutes" type="number" min="0" value="<?= (int) ($type['included_wait_minutes'] ?? 3) ?>">
                    <div style="height:6px;"></div>
                    <label>Sort</label><input form="taxi-type-<?= (int) $type['id'] ?>" name="sort_order" type="number" value="<?= (int) $type['sort_order'] ?>">
                    <div style="height:6px;"></div>
                    <select form="taxi-type-<?= (int) $type['id'] ?>" name="status">
                        <option value="1" <?= (int) $type['status'] === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= (int) $type['status'] === 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </td>
                <td>
                    <form id="taxi-type-<?= (int) $type['id'] ?>" method="post" action="/admin/taxi/vehicle-types/<?= (int) $type['id'] ?>/update"></form>
                    <button form="taxi-type-<?= (int) $type['id'] ?>">Save</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="drivers">
    <h2>Add Taxi Driver</h2>
    <form method="post" action="/admin/taxi/drivers" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Phone</label><input name="phone" required></div>
            <div><label>Email</label><input name="email" type="email"></div>
        </div>
        <div class="row">
            <div>
                <label>Zone</label>
                <select name="zone_id">
                    <option value="">All Zones</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Vehicle Type</label>
                <select name="vehicle_type_id">
                    <option value="">Select type</option>
                    <?php foreach ($vehicleTypes as $type): ?>
                        <option value="<?= (int) $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Worker App Password</label><input name="password" type="password" required></div>
        </div>
        <div class="row">
            <div><label>Vehicle Name</label><input name="vehicle_name" placeholder="Swift Dzire, Activa"></div>
            <div><label>Vehicle Number</label><input name="vehicle_number"></div>
            <div><label>License Number</label><input name="license_number"></div>
            <div><label>RC Number</label><input name="rc_number"></div>
        </div>
        <div class="row">
            <div><label>License Expiry</label><input name="license_expiry" type="date"></div>
            <div><label>Insurance Expiry</label><input name="insurance_expiry" type="date"></div>
            <div><label>Driver Photo</label><input name="profile_photo" type="file" accept="image/*"></div>
        </div>
        <div class="row">
            <div><label>License Image</label><input name="license_document" type="file" accept="image/*"></div>
            <div><label>RC Image</label><input name="vehicle_document" type="file" accept="image/*"></div>
            <div><label>Insurance Image</label><input name="insurance_document" type="file" accept="image/*"></div>
        </div>
        <div class="row">
            <div>
                <label>Status</label>
                <select name="status">
                    <?php foreach (['approved','pending','suspended'] as $status): ?>
                        <option value="<?= $status ?>"><?= $status ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Admin Note</label><input name="admin_note"></div>
            <div style="align-self:end;"><button>Add Driver</button></div>
        </div>
    </form>
</section>

<section class="card">
    <h2>Taxi Drivers</h2>
    <table>
        <thead><tr><th>Driver</th><th>Zone</th><th>Vehicle</th><th>Live Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($drivers as $driver): ?>
            <tr>
                <td>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="name" value="<?= htmlspecialchars($driver['name']) ?>" required>
                    <div style="height:6px;"></div>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="phone" value="<?= htmlspecialchars($driver['phone']) ?>" required>
                    <div style="height:6px;"></div>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="email" type="email" value="<?= htmlspecialchars($driver['email'] ?? '') ?>" placeholder="Email">
                    <div style="height:6px;"></div>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="password" type="password" placeholder="New app password">
                </td>
                <td>
                    <select form="taxi-driver-<?= (int) $driver['id'] ?>" name="zone_id">
                        <option value="">All Zones</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= (int) $zone['id'] ?>" <?= (int) ($driver['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br><small><?= htmlspecialchars($driver['zone_name'] ?? 'All Zones') ?></small>
                </td>
                <td>
                    <select form="taxi-driver-<?= (int) $driver['id'] ?>" name="vehicle_type_id">
                        <option value="">Select type</option>
                        <?php foreach ($vehicleTypes as $type): ?>
                            <option value="<?= (int) $type['id'] ?>" <?= (int) ($driver['vehicle_type_id'] ?? 0) === (int) $type['id'] ? 'selected' : '' ?>><?= htmlspecialchars($type['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="height:6px;"></div>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="vehicle_name" value="<?= htmlspecialchars($driver['vehicle_name'] ?? '') ?>" placeholder="Vehicle">
                    <div style="height:6px;"></div>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="vehicle_number" value="<?= htmlspecialchars($driver['vehicle_number'] ?? '') ?>" placeholder="Number">
                    <div style="height:6px;"></div>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="license_number" value="<?= htmlspecialchars($driver['license_number'] ?? '') ?>" placeholder="License">
                    <div style="height:6px;"></div>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="rc_number" value="<?= htmlspecialchars($driver['rc_number'] ?? '') ?>" placeholder="RC number">
                    <div style="height:6px;"></div>
                    <label>License expiry</label><input form="taxi-driver-<?= (int) $driver['id'] ?>" name="license_expiry" type="date" value="<?= htmlspecialchars($driver['license_expiry'] ?? '') ?>">
                    <div style="height:6px;"></div>
                    <label>Insurance expiry</label><input form="taxi-driver-<?= (int) $driver['id'] ?>" name="insurance_expiry" type="date" value="<?= htmlspecialchars($driver['insurance_expiry'] ?? '') ?>">
                </td>
                <td>
                    <span class="pill"><?= htmlspecialchars($driver['status']) ?></span>
                    <span class="pill"><?= htmlspecialchars($driver['availability_status']) ?></span>
                    <?php if (!empty($driver['last_seen_at'])): ?><br><small>Seen <?= htmlspecialchars($driver['last_seen_at']) ?></small><?php endif; ?>
                    <?php if (!empty($driver['current_latitude'])): ?><br><small><?= htmlspecialchars($driver['current_latitude']) ?>, <?= htmlspecialchars($driver['current_longitude']) ?></small><?php endif; ?>
                    <div style="height:8px;"></div>
                    <select form="taxi-driver-<?= (int) $driver['id'] ?>" name="status">
                        <?php foreach (['approved','pending','suspended'] as $status): ?>
                            <option value="<?= $status ?>" <?= $driver['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="height:6px;"></div>
                    <input form="taxi-driver-<?= (int) $driver['id'] ?>" name="admin_note" value="<?= htmlspecialchars($driver['admin_note'] ?? '') ?>" placeholder="Admin note">
                    <div style="height:8px;"></div>
                    <label>Driver photo</label><input form="taxi-driver-<?= (int) $driver['id'] ?>" name="profile_photo" type="file" accept="image/*">
                    <label>License image</label><input form="taxi-driver-<?= (int) $driver['id'] ?>" name="license_document" type="file" accept="image/*">
                    <label>RC image</label><input form="taxi-driver-<?= (int) $driver['id'] ?>" name="vehicle_document" type="file" accept="image/*">
                    <label>Insurance image</label><input form="taxi-driver-<?= (int) $driver['id'] ?>" name="insurance_document" type="file" accept="image/*">
                </td>
                <td>
                    <form id="taxi-driver-<?= (int) $driver['id'] ?>" method="post" action="/admin/taxi/drivers/<?= (int) $driver['id'] ?>/update" enctype="multipart/form-data"></form>
                    <small><?= !empty($driver['license_document']) && !empty($driver['vehicle_document']) && !empty($driver['insurance_document']) ? 'Documents uploaded' : 'KYC documents incomplete' ?></small><br>
                    <button form="taxi-driver-<?= (int) $driver['id'] ?>">Save</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="rides">
    <h2>Taxi Rides</h2>
    <table>
        <thead><tr><th>Ride</th><th>Customer</th><th>Route</th><th>Driver</th><th>Fare</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($rides as $ride): ?>
            <tr>
                <td><strong><?= htmlspecialchars($ride['ride_number']) ?></strong><br><span class="pill"><?= htmlspecialchars($ride['vehicle_type_name'] ?? 'Cab') ?></span><br><small><?= htmlspecialchars($ride['created_at'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($ride['customer_name']) ?><br><?= htmlspecialchars($ride['customer_phone']) ?></td>
                <td><strong>Pickup</strong><br><?= htmlspecialchars($ride['pickup_address']) ?><br><strong>Drop</strong><br><?= htmlspecialchars($ride['drop_address']) ?></td>
                <td>
                    <form method="post" action="/admin/taxi/rides/<?= (int) $ride['id'] ?>/assign">
                        <select name="driver_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= (int) $driver['id'] ?>" <?= (int) ($ride['driver_id'] ?? 0) === (int) $driver['id'] ? 'selected' : '' ?>><?= htmlspecialchars($driver['name']) ?><?= !empty($driver['vehicle_number']) ? ' / ' . htmlspecialchars($driver['vehicle_number']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn secondary">Assign</button>
                    </form>
                </td>
                <td>₹<?= number_format((float) ($ride['final_fare'] ?? 0), 2) ?><br><small><?= (float) $ride['distance_km'] ?> km / <?= (int) $ride['duration_minutes'] ?> min</small></td>
                <td><span class="pill"><?= htmlspecialchars($ride['ride_status']) ?></span><br><span class="pill"><?= htmlspecialchars($ride['payment_status']) ?></span></td>
                <td>
                    <form method="post" action="/admin/taxi/rides/<?= (int) $ride['id'] ?>/status">
                        <select name="ride_status">
                            <?php foreach (['requested','accepted','arrived','started','completed','cancelled','rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= $ride['ride_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="payment_status">
                            <?php foreach (['unpaid','pending','paid','failed','refunded','partially_refunded'] as $status): ?>
                                <option value="<?= $status ?>" <?= $ride['payment_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="final_fare" value="<?= htmlspecialchars((string) ($ride['final_fare'] ?? 0)) ?>" placeholder="Final fare">
                        <input name="cancellation_reason" value="<?= htmlspecialchars($ride['cancellation_reason'] ?? '') ?>" placeholder="Cancel reason">
                        <input name="admin_note" placeholder="Status note">
                        <button class="btn secondary">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
