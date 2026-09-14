<section class="card">
    <h2>Create Admin User</h2>
    <form method="post" action="/admin/admin-users" class="row">
        <div><label>Name</label><input name="name" required></div>
        <div><label>Email</label><input name="email" type="email" required></div>
        <div><label>Password</label><input name="password" type="password" minlength="8" required></div>
        <div>
            <label>Role</label>
            <select name="role">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($role))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Zone Scope</label>
            <select name="zone_id">
                <option value="">All zones</option>
                <?php foreach ($zones as $zone): ?>
                    <option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="align-self:end"><button>Create Admin</button></div>
    </form>
</section>

<section class="card">
    <h2>Admin Users</h2>
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Zone Scope</th><th>Update</th><th>Delete</th></tr></thead>
        <tbody>
        <?php foreach ($admins as $admin): ?>
            <tr>
                <form method="post" action="/admin/admin-users/<?= (int) $admin['id'] ?>/update">
                    <td><input name="name" value="<?= htmlspecialchars($admin['name']) ?>" required></td>
                    <td><input name="email" type="email" value="<?= htmlspecialchars($admin['email']) ?>" required></td>
                    <td>
                        <select name="role">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role) ?>" <?= $admin['role'] === $role ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('_', ' ', ucfirst($role))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="zone_id">
                            <option value="">All zones</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= (int) $zone['id'] ?>" <?= (int) ($admin['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input name="password" type="password" minlength="8" placeholder="New password optional">
                        <div style="height:8px"></div><button>Save</button>
                    </td>
                </form>
                <td>
                    <?php if ((int) $admin['id'] !== (int) ($_SESSION['admin_id'] ?? 0)): ?>
                        <form method="post" action="/admin/admin-users/<?= (int) $admin['id'] ?>/delete">
                            <button class="btn danger">Delete</button>
                        </form>
                    <?php else: ?>
                        <span class="pill">Current user</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
