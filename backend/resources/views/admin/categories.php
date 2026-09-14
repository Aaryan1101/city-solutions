<section class="card">
    <h2>Add Category</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/categories') ?>" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Category Shipping Cost</label><input name="shipping_cost" type="number" step="0.01" value="0"></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
        </div>
        <label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select>
        <label>Image</label><input name="image" type="file" accept="image/*">
        <div style="height:12px;"></div><button>Add Category</button>
    </form>
</section>

<section class="card">
    <h2>Categories</h2>
    <table>
        <thead><tr><th>Image</th><th>Name</th><th>Shipping</th><th>Status</th><th>Sort</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?php if ($category['image']): ?><img class="thumb" src="<?= htmlspecialchars($category['image']) ?>"><?php endif; ?></td>
                <td><?= htmlspecialchars($category['name']) ?></td>
                <td>₹<?= number_format((float) ($category['shipping_cost'] ?? 0), 2) ?></td>
                <td><span class="pill"><?= $category['status'] ? 'Active' : 'Inactive' ?></span></td>
                <td><?= (int) $category['sort_order'] ?></td>
                <td>
                    <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/categories') ?>/<?= $category['id'] ?>/edit">Edit</a>
                    <a class="btn danger" href="<?= htmlspecialchars($basePath ?? '/admin/categories') ?>/<?= $category['id'] ?>/delete">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
