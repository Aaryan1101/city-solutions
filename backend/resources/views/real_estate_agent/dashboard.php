<section class="card" id="summary">
    <h2>Real Estate Summary</h2>
    <div class="grid">
        <div class="stat"><strong><?= count($properties) ?></strong><span>Properties</span></div>
        <div class="stat"><strong><?= count($projects) ?></strong><span>Projects</span></div>
        <div class="stat"><strong><?= count($units) ?></strong><span>Units</span></div>
        <div class="stat"><strong><?= count($inquiries) ?></strong><span>Inquiries</span></div>
        <div class="stat"><strong><?= count($visits) ?></strong><span>Site Visits</span></div>
    </div>
</section>

<section class="card" id="profile">
    <h2>Profile</h2>
    <form method="post" action="/real-estate-agent/profile">
        <div class="row">
            <div><label>Business Name</label><input name="business_name" value="<?= htmlspecialchars((string) ($agent['business_name'] ?? '')) ?>" required></div>
            <div><label>Owner Name</label><input name="owner_name" value="<?= htmlspecialchars((string) ($agent['owner_name'] ?? '')) ?>" required></div>
            <div><label>Phone</label><input name="phone" value="<?= htmlspecialchars((string) ($agent['phone'] ?? '')) ?>" required></div>
            <div><label>Email</label><input name="email" value="<?= htmlspecialchars((string) ($agent['email'] ?? '')) ?>"></div>
            <div><label>New Password</label><input name="password" type="password" placeholder="Leave blank to keep current"></div>
        </div>
        <label>Bio</label>
        <textarea name="bio"><?= htmlspecialchars((string) ($agent['bio'] ?? '')) ?></textarea>
        <button style="margin-top:12px">Save Profile</button>
    </form>
</section>

<section class="card" id="properties">
    <h2>Submit Property</h2>
    <form method="post" action="/real-estate-agent/properties" enctype="multipart/form-data">
        <div class="row">
            <div><label>Title</label><input name="title" required placeholder="3 BHK Apartment"></div>
            <div><label>Purpose</label><select name="listing_purpose"><option value="sell">Sell</option><option value="rent">Rent</option><option value="lease">Lease</option></select></div>
            <div><label>Category</label><select name="property_category"><option value="apartment">Apartment</option><option value="villa">Villa</option><option value="plot">Plot</option><option value="office">Office</option><option value="shop">Shop</option><option value="commercial">Commercial</option></select></div>
            <div><label>Price</label><input name="price" type="number" min="0" step="0.01" required></div>
            <div><label>City</label><input name="city" value="Lucknow" required></div>
            <div><label>Area</label><input name="area"></div>
            <div><label>Address</label><input name="address" required></div>
            <div><label>Bedrooms</label><input name="bedrooms" type="number" value="2"></div>
            <div><label>Bathrooms</label><input name="bathrooms" type="number" value="2"></div>
            <div><label>Parking</label><input name="parking" type="number" value="1"></div>
            <div><label>Built-up Area</label><input name="built_up_area" type="number" min="0" step="0.01"></div>
            <div><label>Furnishing</label><input name="furnishing" placeholder="Semi furnished"></div>
            <div><label>Latitude</label><input name="latitude" type="number" step="0.0000001"></div>
            <div><label>Longitude</label><input name="longitude" type="number" step="0.0000001"></div>
            <div><label>Thumbnail</label><input name="thumbnail" type="file" accept="image/*"></div>
        </div>
        <label>Description</label>
        <textarea name="description"></textarea>
        <button style="margin-top:12px">Submit For Approval</button>
    </form>
    <h2>My Properties</h2>
    <table>
        <thead><tr><th>Property</th><th>Price</th><th>Status</th><th>Availability</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($properties as $property): ?>
            <tr>
                <td><strong><?= htmlspecialchars($property['title']) ?></strong><br><?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars((string) ($property['area'] ?? '')) ?></td>
                <td>₹<?= number_format((float) $property['price'], 2) ?><br><small><?= htmlspecialchars($property['listing_purpose']) ?></small></td>
                <td><span class="pill"><?= htmlspecialchars($property['status']) ?></span></td>
                <td><span class="pill"><?= htmlspecialchars($property['availability_status']) ?></span></td>
                <td>
                    <form method="post" action="/real-estate-agent/properties/<?= (int) $property['id'] ?>/update">
                        <select name="availability_status">
                            <?php foreach (['available','sold','rented','inactive'] as $status): ?>
                                <option value="<?= $status ?>" <?= $property['availability_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn secondary">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="projects">
    <h2>Submit Project</h2>
    <form method="post" action="/real-estate-agent/projects" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>City</label><input name="city" value="Lucknow" required></div>
            <div><label>Area</label><input name="area"></div>
            <div><label>Address</label><input name="address"></div>
            <div><label>Latitude</label><input name="latitude" type="number" step="0.0000001"></div>
            <div><label>Longitude</label><input name="longitude" type="number" step="0.0000001"></div>
            <div><label>Launch Date</label><input name="launch_date" type="date"></div>
            <div><label>Thumbnail</label><input name="thumbnail" type="file" accept="image/*"></div>
        </div>
        <label>Description</label>
        <textarea name="description"></textarea>
        <button style="margin-top:12px">Submit Project</button>
    </form>
    <h2>My Projects</h2>
    <table>
        <thead><tr><th>Project</th><th>Location</th><th>Status</th><th>Launch</th></tr></thead>
        <tbody>
        <?php foreach ($projects as $project): ?>
            <tr>
                <td><strong><?= htmlspecialchars($project['name']) ?></strong></td>
                <td><?= htmlspecialchars($project['city']) ?><br><?= htmlspecialchars((string) ($project['area'] ?? '')) ?></td>
                <td><span class="pill"><?= htmlspecialchars($project['status']) ?></span></td>
                <td><?= htmlspecialchars((string) ($project['launch_date'] ?? '-')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="project-units">
    <h2>Add Project Unit</h2>
    <form method="post" action="/real-estate-agent/project-units" enctype="multipart/form-data">
        <div class="row">
            <div><label>Project</label><select name="project_id" required><?php foreach ($projects as $project): ?><option value="<?= (int) $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Unit Name</label><input name="name" required placeholder="2 BHK Premium"></div>
            <div><label>Price From</label><input name="price_from" type="number" min="0" step="0.01" required></div>
            <div><label>Area</label><input name="area" type="number" min="0" step="0.01" required></div>
            <div><label>Bedrooms</label><input name="bedrooms" type="number" value="2"></div>
            <div><label>Bathrooms</label><input name="bathrooms" type="number" value="2"></div>
            <div><label>Sort</label><input name="sort_order" type="number" value="0"></div>
            <div><label>Floor Plan</label><input name="floor_plan_image" type="file" accept="image/*"></div>
        </div>
        <button style="margin-top:12px">Add Unit</button>
    </form>
    <table>
        <thead><tr><th>Unit</th><th>Project</th><th>Price</th><th>Area</th></tr></thead>
        <tbody>
        <?php foreach ($units as $unit): ?>
            <tr>
                <td><?= htmlspecialchars($unit['name']) ?><br><small><?= (int) $unit['bedrooms'] ?> bed, <?= (int) $unit['bathrooms'] ?> bath</small></td>
                <td><?= htmlspecialchars($unit['project_name']) ?></td>
                <td>₹<?= number_format((float) $unit['price_from'], 2) ?></td>
                <td><?= number_format((float) $unit['area'], 0) ?> sq ft</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="inquiries">
    <h2>Inquiries</h2>
    <table>
        <thead><tr><th>Inquiry</th><th>Customer</th><th>Message</th><th>Status</th><th>Reply</th></tr></thead>
        <tbody>
        <?php foreach ($inquiries as $inquiry): ?>
            <tr>
                <form method="post" action="/real-estate-agent/inquiries/<?= (int) $inquiry['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($inquiry['inquiry_number']) ?></strong><br><?= htmlspecialchars($inquiry['property_title'] ?? 'Property') ?></td>
                    <td><?= htmlspecialchars($inquiry['customer_name']) ?><br><?= htmlspecialchars($inquiry['customer_phone']) ?><br><?= htmlspecialchars((string) ($inquiry['customer_email'] ?? '')) ?></td>
                    <td><?= nl2br(htmlspecialchars((string) ($inquiry['message'] ?? ''))) ?></td>
                    <td><select name="status"><?php foreach (['open','contacted','converted','closed'] as $status): ?><option value="<?= $status ?>" <?= $inquiry['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></td>
                    <td><input name="agent_reply" value="<?= htmlspecialchars((string) ($inquiry['agent_reply'] ?? '')) ?>" placeholder="Reply / note"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card" id="visits">
    <h2>Site Visits</h2>
    <table>
        <thead><tr><th>Visit</th><th>Customer</th><th>Requested</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($visits as $visit): ?>
            <tr>
                <form method="post" action="/real-estate-agent/site-visits/<?= (int) $visit['id'] ?>/status">
                    <td><strong><?= htmlspecialchars($visit['visit_number']) ?></strong><br><?= htmlspecialchars($visit['property_title'] ?? 'Property') ?></td>
                    <td><?= htmlspecialchars($visit['customer_name']) ?><br><?= htmlspecialchars($visit['customer_phone']) ?></td>
                    <td><?= htmlspecialchars($visit['requested_date']) ?><br><?= htmlspecialchars($visit['requested_time']) ?></td>
                    <td><select name="status"><?php foreach (['pending','confirmed','completed','cancelled','rejected'] as $status): ?><option value="<?= $status ?>" <?= $visit['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></td>
                    <td><input name="admin_note" value="<?= htmlspecialchars((string) ($visit['admin_note'] ?? '')) ?>" placeholder="Note"><button class="btn secondary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
