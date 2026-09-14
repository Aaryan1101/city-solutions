<section class="card">
    <h2>Add Banner</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/banners') ?>" enctype="multipart/form-data">
        <div class="row">
            <div><label>Title</label><input name="title"></div>
            <div><label>Link Type</label><select name="link_type"><option value="none">None</option><option value="product">Product</option><option value="category">Category</option><option value="url">URL</option></select></div>
            <div><label>Link Value</label><input name="link_value"></div>
        </div>
        <div class="row">
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
            <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div><label>Image</label><input name="image" type="file" accept="image/*"></div>
        </div>
        <div style="height:12px;"></div><button>Add Banner</button>
    </form>
</section>

<section class="card">
    <h2>Banners</h2>
    <table>
        <thead><tr><th>Image</th><th>Title</th><th>Link</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($banners as $banner): ?>
            <tr>
                <td><?php if ($banner['image']): ?><img class="thumb" src="<?= htmlspecialchars($banner['image']) ?>"><?php endif; ?></td>
                <td><?= htmlspecialchars($banner['title']) ?></td>
                <td><?= htmlspecialchars($banner['link_type']) ?>: <?= htmlspecialchars($banner['link_value']) ?></td>
                <td><span class="pill"><?= $banner['status'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/banners') ?>/<?= $banner['id'] ?>/edit">Edit</a>
                    <a class="btn danger" href="<?= htmlspecialchars($basePath ?? '/admin/banners') ?>/<?= $banner['id'] ?>/delete">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
