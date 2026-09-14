<div class="grid" id="summary">
    <div class="card stat"><strong><?= count($properties) ?></strong><span>Properties</span></div>
    <div class="card stat"><strong><?= count($projects ?? []) ?></strong><span>Projects</span></div>
    <div class="card stat"><strong><?= count($units ?? []) ?></strong><span>Project Units</span></div>
    <div class="card stat"><strong><?= count($agents) ?></strong><span>Agents / Builders</span></div>
    <div class="card stat"><strong><?= count($inquiries) ?></strong><span>Recent Inquiries</span></div>
    <div class="card stat"><strong><?= count($visits) ?></strong><span>Site Visits</span></div>
    <div class="card stat"><strong><?= count($complaints ?? []) ?></strong><span>Listing Reports</span></div>
</div>

<div class="card" id="complaints">
    <h2>Listing Reports</h2>
    <table>
        <thead><tr><th>Report</th><th>Property</th><th>Customer</th><th>Message</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach (($complaints ?? []) as $complaint): ?>
            <tr>
                <form method="post" action="/admin/real-estate/complaints/<?= (int) $complaint['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($complaint['complaint_number']) ?></strong><br><?= htmlspecialchars($complaint['created_at'] ?? '') ?></td>
                    <td><?= htmlspecialchars($complaint['property_title'] ?? 'Property') ?><br><span class="pill"><?= htmlspecialchars($complaint['agent_name'] ?? 'Admin') ?></span></td>
                    <td><?= htmlspecialchars($complaint['customer_name'] ?? 'Customer') ?><br><?= htmlspecialchars($complaint['customer_phone'] ?? '') ?></td>
                    <td><?= nl2br(htmlspecialchars($complaint['message'] ?? '')) ?><?php if (!empty($complaint['admin_note'])): ?><br><small><strong>Note:</strong> <?= htmlspecialchars($complaint['admin_note']) ?></small><?php endif; ?></td>
                    <td><select name="status"><?php foreach (['pending','reviewing','resolved','dismissed'] as $status): ?><option value="<?= $status ?>" <?= $complaint['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></td>
                    <td><input name="admin_note" value="<?= htmlspecialchars($complaint['admin_note'] ?? '') ?>" placeholder="Admin note"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="agents">
    <h2>Agents / Builders</h2>
    <form method="post" action="/admin/real-estate/agents" enctype="multipart/form-data">
        <div class="row">
            <div><label>Business Name</label><input name="business_name" required placeholder="City Realty"></div>
            <div><label>Owner Name</label><input name="owner_name" required></div>
            <div><label>Zone</label><select name="zone_id"><option value="">All Zones</option><?php foreach (($zones ?? []) as $zone): ?><option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Phone</label><input name="phone"></div>
            <div><label>Email</label><input name="email" type="email"></div>
            <div><label>Password</label><input name="password" type="password"></div>
            <div><label>License No.</label><input name="license_no"></div>
            <div><label>Status</label><select name="status"><option value="approved">Approved</option><option value="pending">Pending</option><option value="rejected">Rejected</option><option value="suspended">Suspended</option></select></div>
            <div><label>Profile Image</label><input name="profile_image" type="file" accept="image/*"></div>
        </div>
        <label>Bio</label><textarea name="bio"></textarea>
        <label>Admin Note</label><input name="admin_note">
        <button style="margin-top:12px">Add Agent</button>
    </form>
    <table>
        <thead><tr><th>Business</th><th>Zone</th><th>Contact</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($agents as $agent): ?>
            <tr>
                <form method="post" action="/admin/real-estate/agents/<?= (int) $agent['id'] ?>/update" enctype="multipart/form-data">
                    <td>
                        <input name="business_name" value="<?= htmlspecialchars($agent['business_name']) ?>" required>
                        <input name="owner_name" value="<?= htmlspecialchars($agent['owner_name']) ?>" required>
                        <input name="license_no" value="<?= htmlspecialchars($agent['license_no'] ?? '') ?>" placeholder="License">
                    </td>
                    <td>
                        <select name="zone_id">
                            <option value="">All Zones</option>
                            <?php foreach (($zones ?? []) as $zone): ?>
                                <option value="<?= (int) $zone['id'] ?>" <?= (int) ($agent['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small><?= htmlspecialchars($agent['zone_name'] ?? 'All Zones') ?></small>
                    </td>
                    <td>
                        <input name="phone" value="<?= htmlspecialchars($agent['phone'] ?? '') ?>" placeholder="Phone">
                        <input name="email" value="<?= htmlspecialchars($agent['email'] ?? '') ?>" placeholder="Email">
                    </td>
                    <td>
                        <select name="status">
                            <?php foreach (['pending','approved','rejected','suspended'] as $status): ?>
                                <option value="<?= $status ?>" <?= $agent['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input name="password" type="password" placeholder="New password">
                        <input name="profile_image" type="file" accept="image/*">
                        <input name="admin_note" value="<?= htmlspecialchars($agent['admin_note'] ?? '') ?>" placeholder="Admin note">
                        <textarea name="bio" placeholder="Bio"><?= htmlspecialchars($agent['bio'] ?? '') ?></textarea>
                        <button class="btn secondary">Save</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="amenities">
    <h2>Amenities</h2>
    <form method="post" action="/admin/real-estate/amenities" class="row">
        <div><label>Name</label><input name="name" required placeholder="Parking"></div>
        <div><label>Icon</label><input name="icon" placeholder="parking"></div>
        <div><label>Sort</label><input name="sort_order" type="number" value="0"></div>
        <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        <div style="align-self:end"><button>Add Amenity</button></div>
    </form>
    <table>
        <thead><tr><th>Name</th><th>Icon</th><th>Sort</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($amenities as $amenity): ?>
            <tr>
                <form method="post" action="/admin/real-estate/amenities/<?= (int) $amenity['id'] ?>/update">
                    <td><input name="name" value="<?= htmlspecialchars($amenity['name']) ?>"></td>
                    <td><input name="icon" value="<?= htmlspecialchars($amenity['icon'] ?? '') ?>"></td>
                    <td><input name="sort_order" type="number" value="<?= (int) $amenity['sort_order'] ?>"></td>
                    <td><label><input type="checkbox" name="status" value="1" <?= (int) $amenity['status'] === 1 ? 'checked' : '' ?>> Active</label></td>
                    <td><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="properties">
    <h2>Properties</h2>
    <form method="post" action="/admin/real-estate/properties" enctype="multipart/form-data">
        <div class="row">
            <div><label>Title</label><input name="title" required placeholder="2 BHK Apartment"></div>
            <div><label>Agent</label><select name="agent_id"><option value="">Admin Managed</option><?php foreach ($agents as $agent): ?><option value="<?= (int) $agent['id'] ?>"><?= htmlspecialchars($agent['business_name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Zone</label><select name="zone_id"><option value="">All Zones</option><?php foreach (($zones ?? []) as $zone): ?><option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Purpose</label><select name="listing_purpose"><option value="sell">Sell</option><option value="rent">Rent</option><option value="lease">Lease</option></select></div>
            <div><label>Category</label><select name="property_category"><option value="apartment">Apartment</option><option value="villa">Villa</option><option value="plot">Plot</option><option value="office">Office</option><option value="shop">Shop</option><option value="commercial">Commercial</option></select></div>
            <div><label>Price</label><input name="price" type="number" step="0.01" required></div>
            <div><label>City</label><input name="city" value="Lucknow" required></div>
            <div><label>Area</label><input name="area"></div>
            <div><label>Address</label><input name="address" required></div>
            <div><label>Bedrooms</label><input name="bedrooms" type="number" value="2"></div>
            <div><label>Bathrooms</label><input name="bathrooms" type="number" value="2"></div>
            <div><label>Balconies</label><input name="balconies" type="number" value="1"></div>
            <div><label>Parking</label><input name="parking" type="number" value="1"></div>
            <div><label>Built-up Area</label><input name="built_up_area" type="number" step="0.01"></div>
            <div><label>Carpet Area</label><input name="carpet_area" type="number" step="0.01"></div>
            <div><label>Plot Area</label><input name="plot_area" type="number" step="0.01"></div>
            <div><label>Latitude</label><input name="latitude" type="number" step="0.0000001"></div>
            <div><label>Longitude</label><input name="longitude" type="number" step="0.0000001"></div>
            <div><label>Furnishing</label><input name="furnishing" placeholder="Semi furnished"></div>
            <div><label>Ownership</label><input name="ownership_type" placeholder="Freehold"></div>
            <div><label>Property Age</label><input name="property_age" placeholder="0-1 years"></div>
            <div><label>Status</label><select name="status"><option value="approved">Approved</option><option value="pending">Pending</option><option value="rejected">Rejected</option></select></div>
            <div><label>Availability</label><select name="availability_status"><option value="available">Available</option><option value="sold">Sold</option><option value="rented">Rented</option><option value="inactive">Inactive</option></select></div>
            <div><label>Thumbnail</label><input name="thumbnail" type="file" accept="image/*"></div>
        </div>
        <label>Description</label><textarea name="description"></textarea>
        <label>Amenities</label>
        <div class="row">
            <?php foreach ($amenities as $amenity): ?>
                <label><input type="checkbox" name="amenity_ids[]" value="<?= (int) $amenity['id'] ?>"> <?= htmlspecialchars($amenity['name']) ?></label>
            <?php endforeach; ?>
        </div>
        <label>Gallery Images</label><input name="gallery[]" type="file" accept="image/*" multiple>
        <label><input type="checkbox" name="is_verified" value="1" checked> Verified</label>
        <label><input type="checkbox" name="is_featured" value="1"> Featured</label>
        <button style="margin-top:12px">Add Property</button>
    </form>
    <table>
        <thead><tr><th>Property</th><th>Zone</th><th>Agent</th><th>Price</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($properties as $property): ?>
            <tr>
                <form method="post" action="/admin/real-estate/properties/<?= (int) $property['id'] ?>/update" enctype="multipart/form-data">
                    <td>
                        <input name="title" value="<?= htmlspecialchars($property['title']) ?>" required>
                        <input name="city" value="<?= htmlspecialchars($property['city']) ?>" required>
                        <input name="area" value="<?= htmlspecialchars($property['area'] ?? '') ?>" placeholder="Area">
                        <textarea name="address" required><?= htmlspecialchars($property['address']) ?></textarea>
                    </td>
                    <td>
                        <select name="zone_id">
                            <option value="">All Zones</option>
                            <?php foreach (($zones ?? []) as $zone): ?>
                                <option value="<?= (int) $zone['id'] ?>" <?= (int) ($property['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="agent_id"><option value="">Admin Managed</option><?php foreach ($agents as $agent): ?><option value="<?= (int) $agent['id'] ?>" <?= (int) ($property['agent_id'] ?? 0) === (int) $agent['id'] ? 'selected' : '' ?>><?= htmlspecialchars($agent['business_name']) ?></option><?php endforeach; ?></select>
                        <span class="pill"><?= htmlspecialchars($property['agent_name'] ?? 'Admin') ?></span>
                    </td>
                    <td>
                        <input name="price" type="number" step="0.01" value="<?= htmlspecialchars((string) $property['price']) ?>">
                        <select name="listing_purpose"><option value="sell" <?= $property['listing_purpose'] === 'sell' ? 'selected' : '' ?>>Sell</option><option value="rent" <?= $property['listing_purpose'] === 'rent' ? 'selected' : '' ?>>Rent</option><option value="lease" <?= $property['listing_purpose'] === 'lease' ? 'selected' : '' ?>>Lease</option></select>
                        <select name="property_category"><option value="apartment" <?= $property['property_category'] === 'apartment' ? 'selected' : '' ?>>Apartment</option><option value="villa" <?= $property['property_category'] === 'villa' ? 'selected' : '' ?>>Villa</option><option value="plot" <?= $property['property_category'] === 'plot' ? 'selected' : '' ?>>Plot</option><option value="office" <?= $property['property_category'] === 'office' ? 'selected' : '' ?>>Office</option><option value="shop" <?= $property['property_category'] === 'shop' ? 'selected' : '' ?>>Shop</option><option value="commercial" <?= $property['property_category'] === 'commercial' ? 'selected' : '' ?>>Commercial</option></select>
                    </td>
                    <td>
                        <select name="status"><option value="pending" <?= $property['status'] === 'pending' ? 'selected' : '' ?>>Pending</option><option value="approved" <?= $property['status'] === 'approved' ? 'selected' : '' ?>>Approved</option><option value="rejected" <?= $property['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option></select>
                        <select name="availability_status"><option value="available" <?= $property['availability_status'] === 'available' ? 'selected' : '' ?>>Available</option><option value="sold" <?= $property['availability_status'] === 'sold' ? 'selected' : '' ?>>Sold</option><option value="rented" <?= $property['availability_status'] === 'rented' ? 'selected' : '' ?>>Rented</option><option value="inactive" <?= $property['availability_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select>
                        <label><input type="checkbox" name="is_verified" value="1" <?= (int) $property['is_verified'] === 1 ? 'checked' : '' ?>> Verified</label>
                        <label><input type="checkbox" name="is_featured" value="1" <?= (int) $property['is_featured'] === 1 ? 'checked' : '' ?>> Featured</label>
                    </td>
                    <td>
                        <input type="hidden" name="price_unit" value="<?= htmlspecialchars($property['price_unit'] ?? '') ?>">
                        <input type="hidden" name="maintenance_charge" value="<?= htmlspecialchars((string) ($property['maintenance_charge'] ?? 0)) ?>">
                        <input type="hidden" name="deposit_amount" value="<?= htmlspecialchars((string) ($property['deposit_amount'] ?? 0)) ?>">
                        <input type="hidden" name="bedrooms" value="<?= (int) $property['bedrooms'] ?>">
                        <input type="hidden" name="bathrooms" value="<?= (int) $property['bathrooms'] ?>">
                        <input type="hidden" name="balconies" value="<?= (int) $property['balconies'] ?>">
                        <input type="hidden" name="parking" value="<?= (int) $property['parking'] ?>">
                        <input type="hidden" name="built_up_area" value="<?= htmlspecialchars((string) $property['built_up_area']) ?>">
                        <input type="hidden" name="carpet_area" value="<?= htmlspecialchars((string) $property['carpet_area']) ?>">
                        <input type="hidden" name="plot_area" value="<?= htmlspecialchars((string) $property['plot_area']) ?>">
                        <input type="hidden" name="area_unit" value="<?= htmlspecialchars($property['area_unit']) ?>">
                        <input type="hidden" name="latitude" value="<?= htmlspecialchars((string) ($property['latitude'] ?? '')) ?>">
                        <input type="hidden" name="longitude" value="<?= htmlspecialchars((string) ($property['longitude'] ?? '')) ?>">
                        <input type="hidden" name="furnishing" value="<?= htmlspecialchars($property['furnishing'] ?? '') ?>">
                        <input type="hidden" name="ownership_type" value="<?= htmlspecialchars($property['ownership_type'] ?? '') ?>">
                        <input type="hidden" name="property_age" value="<?= htmlspecialchars($property['property_age'] ?? '') ?>">
                        <input type="hidden" name="contact_preference" value="<?= htmlspecialchars($property['contact_preference'] ?? 'inquiry') ?>">
                        <input type="hidden" name="rejection_reason" value="<?= htmlspecialchars($property['rejection_reason'] ?? '') ?>">
                        <textarea name="description" placeholder="Description"><?= htmlspecialchars($property['description'] ?? '') ?></textarea>
                        <input name="thumbnail" type="file" accept="image/*">
                        <input name="gallery[]" type="file" accept="image/*" multiple>
                        <button class="btn secondary">Save</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="projects">
    <h2>Projects</h2>
    <form method="post" action="/admin/real-estate/projects" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" required placeholder="Gomti Greens Residency"></div>
            <div><label>Builder</label><select name="builder_id"><option value="">Admin Managed</option><?php foreach ($agents as $agent): ?><option value="<?= (int) $agent['id'] ?>"><?= htmlspecialchars($agent['business_name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Zone</label><select name="zone_id"><option value="">All Zones</option><?php foreach (($zones ?? []) as $zone): ?><option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>City</label><input name="city" value="Lucknow" required></div>
            <div><label>Area</label><input name="area"></div>
            <div><label>Address</label><input name="address"></div>
            <div><label>Latitude</label><input name="latitude" type="number" step="0.0000001"></div>
            <div><label>Longitude</label><input name="longitude" type="number" step="0.0000001"></div>
            <div><label>Launch Date</label><input name="launch_date" type="date"></div>
            <div><label>Status</label><select name="status"><option value="approved">Approved</option><option value="pending">Pending</option><option value="rejected">Rejected</option></select></div>
            <div><label>Thumbnail</label><input name="thumbnail" type="file" accept="image/*"></div>
        </div>
        <label>Description</label><textarea name="description"></textarea>
        <label><input type="checkbox" name="is_featured" value="1"> Featured</label>
        <button style="margin-top:12px">Add Project</button>
    </form>
    <table>
        <thead><tr><th>Project</th><th>Zone</th><th>Builder</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach (($projects ?? []) as $project): ?>
            <tr>
                <form method="post" action="/admin/real-estate/projects/<?= (int) $project['id'] ?>/update" enctype="multipart/form-data">
                    <td>
                        <input name="name" value="<?= htmlspecialchars($project['name']) ?>" required>
                        <input name="city" value="<?= htmlspecialchars($project['city']) ?>" required>
                        <input name="area" value="<?= htmlspecialchars($project['area'] ?? '') ?>" placeholder="Area">
                        <textarea name="address" placeholder="Address"><?= htmlspecialchars($project['address'] ?? '') ?></textarea>
                    </td>
                    <td>
                        <select name="zone_id">
                            <option value="">All Zones</option>
                            <?php foreach (($zones ?? []) as $zone): ?>
                                <option value="<?= (int) $zone['id'] ?>" <?= (int) ($project['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small><?= htmlspecialchars($project['zone_name'] ?? 'All Zones') ?></small>
                    </td>
                    <td>
                        <select name="builder_id">
                            <option value="">Admin Managed</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?= (int) $agent['id'] ?>" <?= (int) ($project['builder_id'] ?? 0) === (int) $agent['id'] ? 'selected' : '' ?>><?= htmlspecialchars($agent['business_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="pill"><?= htmlspecialchars($project['builder_name'] ?? 'Admin') ?></span>
                    </td>
                    <td>
                        <select name="status">
                            <?php foreach (['pending','approved','rejected'] as $status): ?>
                                <option value="<?= $status ?>" <?= $project['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label><input type="checkbox" name="is_featured" value="1" <?= (int) $project['is_featured'] === 1 ? 'checked' : '' ?>> Featured</label>
                    </td>
                    <td>
                        <input name="latitude" type="number" step="0.0000001" value="<?= htmlspecialchars((string) ($project['latitude'] ?? '')) ?>" placeholder="Latitude">
                        <input name="longitude" type="number" step="0.0000001" value="<?= htmlspecialchars((string) ($project['longitude'] ?? '')) ?>" placeholder="Longitude">
                        <input name="launch_date" type="date" value="<?= htmlspecialchars((string) ($project['launch_date'] ?? '')) ?>">
                        <textarea name="description" placeholder="Description"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                        <input name="thumbnail" type="file" accept="image/*">
                        <button class="btn secondary">Save</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="project-units">
    <h2>Project Units</h2>
    <form method="post" action="/admin/real-estate/project-units" enctype="multipart/form-data">
        <div class="row">
            <div><label>Project</label><select name="project_id" required><?php foreach (($projects ?? []) as $project): ?><option value="<?= (int) $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Unit Name</label><input name="name" required placeholder="2 BHK Premium"></div>
            <div><label>Price From</label><input name="price_from" type="number" step="0.01" required></div>
            <div><label>Area</label><input name="area" type="number" step="0.01" required></div>
            <div><label>Bedrooms</label><input name="bedrooms" type="number" value="2"></div>
            <div><label>Bathrooms</label><input name="bathrooms" type="number" value="2"></div>
            <div><label>Sort</label><input name="sort_order" type="number" value="0"></div>
            <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div><label>Floor Plan</label><input name="floor_plan_image" type="file" accept="image/*"></div>
        </div>
        <button style="margin-top:12px">Add Unit</button>
    </form>
    <table>
        <thead><tr><th>Unit</th><th>Project</th><th>Price / Area</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach (($units ?? []) as $unit): ?>
            <tr>
                <form method="post" action="/admin/real-estate/project-units/<?= (int) $unit['id'] ?>/update" enctype="multipart/form-data">
                    <td>
                        <input name="name" value="<?= htmlspecialchars($unit['name']) ?>" required>
                        <input name="bedrooms" type="number" value="<?= (int) $unit['bedrooms'] ?>" placeholder="Bedrooms">
                        <input name="bathrooms" type="number" value="<?= (int) $unit['bathrooms'] ?>" placeholder="Bathrooms">
                    </td>
                    <td>
                        <select name="project_id">
                            <?php foreach (($projects ?? []) as $project): ?>
                                <option value="<?= (int) $project['id'] ?>" <?= (int) $unit['project_id'] === (int) $project['id'] ? 'selected' : '' ?>><?= htmlspecialchars($project['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small><?= htmlspecialchars($unit['project_name'] ?? '') ?></small>
                    </td>
                    <td>
                        <input name="price_from" type="number" step="0.01" value="<?= htmlspecialchars((string) $unit['price_from']) ?>">
                        <input name="area" type="number" step="0.01" value="<?= htmlspecialchars((string) $unit['area']) ?>">
                    </td>
                    <td>
                        <select name="status">
                            <option value="1" <?= (int) $unit['status'] === 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= (int) $unit['status'] === 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </td>
                    <td>
                        <input name="sort_order" type="number" value="<?= (int) $unit['sort_order'] ?>">
                        <input name="floor_plan_image" type="file" accept="image/*">
                        <button class="btn secondary">Save</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="inquiries">
    <h2>Inquiries</h2>
    <table>
        <thead><tr><th>Inquiry</th><th>Property</th><th>Customer</th><th>Message</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($inquiries as $inquiry): ?>
            <tr>
                <form method="post" action="/admin/real-estate/inquiries/<?= (int) $inquiry['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($inquiry['inquiry_number']) ?></strong><br><?= htmlspecialchars($inquiry['created_at'] ?? '') ?></td>
                    <td><?= htmlspecialchars($inquiry['property_title'] ?? 'Property') ?><br><span class="pill"><?= htmlspecialchars($inquiry['agent_name'] ?? 'Admin') ?></span></td>
                    <td><?= htmlspecialchars($inquiry['customer_name']) ?><br><?= htmlspecialchars($inquiry['customer_phone']) ?><br><?= htmlspecialchars($inquiry['customer_email'] ?? '') ?></td>
                    <td><?= nl2br(htmlspecialchars($inquiry['message'] ?? '')) ?><?php if (!empty($inquiry['agent_reply'])): ?><br><small><strong>Reply:</strong> <?= htmlspecialchars($inquiry['agent_reply']) ?></small><?php endif; ?></td>
                    <td><select name="status"><?php foreach (['open','contacted','converted','closed'] as $status): ?><option value="<?= $status ?>" <?= $inquiry['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></td>
                    <td><input name="agent_reply" value="<?= htmlspecialchars($inquiry['agent_reply'] ?? '') ?>" placeholder="Reply / note"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" id="visits">
    <h2>Site Visits</h2>
    <table>
        <thead><tr><th>Visit</th><th>Property</th><th>Customer</th><th>Requested</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($visits as $visit): ?>
            <tr>
                <form method="post" action="/admin/real-estate/site-visits/<?= (int) $visit['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($visit['visit_number']) ?></strong><br><?= htmlspecialchars($visit['created_at'] ?? '') ?></td>
                    <td><?= htmlspecialchars($visit['property_title'] ?? 'Property') ?><br><span class="pill"><?= htmlspecialchars($visit['agent_name'] ?? 'Admin') ?></span></td>
                    <td><?= htmlspecialchars($visit['customer_name']) ?><br><?= htmlspecialchars($visit['customer_phone']) ?><br><?= htmlspecialchars($visit['customer_email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($visit['requested_date']) ?><br><?= htmlspecialchars($visit['requested_time']) ?><br><?= htmlspecialchars($visit['note'] ?? '') ?></td>
                    <td><select name="status"><?php foreach (['pending','confirmed','completed','cancelled','rejected'] as $status): ?><option value="<?= $status ?>" <?= $visit['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></td>
                    <td><input name="admin_note" value="<?= htmlspecialchars($visit['admin_note'] ?? '') ?>" placeholder="Admin note"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
