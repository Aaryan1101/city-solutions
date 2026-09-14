<section class="card">
    <h2>Add Brand</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/brands') ?>" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
            <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <label>Logo/Image</label><input name="image" type="file" accept="image/*">
        <div style="height:12px;"></div><button>Add Brand</button>
    </form>
</section>

<section class="card">
    <h2>Brands</h2>
    <table>
        <thead><tr><th>Image</th><th>Name</th><th>Status</th><th>Sort</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($brands as $brand): ?>
            <tr>
                <td><?php if ($brand['image']): ?><img class="thumb" src="<?= htmlspecialchars($brand['image']) ?>"><?php endif; ?></td>
                <td><?= htmlspecialchars($brand['name']) ?></td>
                <td><span class="pill"><?= $brand['status'] ? 'Active' : 'Inactive' ?></span></td>
                <td><?= (int) $brand['sort_order'] ?></td>
                <td>
                    <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/brands') ?>/<?= $brand['id'] ?>/edit">Edit</a>
                    <a class="btn danger" href="<?= htmlspecialchars($basePath ?? '/admin/brands') ?>/<?= $brand['id'] ?>/delete">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
