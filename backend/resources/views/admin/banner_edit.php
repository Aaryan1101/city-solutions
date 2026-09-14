<section class="card">
    <h2>Edit Banner</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/banners') ?>/<?= $banner['id'] ?>/edit" enctype="multipart/form-data">
        <div class="row">
            <div><label>Title</label><input name="title" value="<?= htmlspecialchars($banner['title']) ?>"></div>
            <div>
                <label>Link Type</label>
                <select name="link_type">
                    <?php foreach (['none', 'product', 'category', 'url'] as $type): ?>
                        <option value="<?= $type ?>" <?= $banner['link_type'] === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Link Value</label><input name="link_value" value="<?= htmlspecialchars($banner['link_value']) ?>"></div>
        </div>
        <div class="row">
            <div><label>Sort Order</label><input name="sort_order" type="number" value="<?= (int) $banner['sort_order'] ?>"></div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="1" <?= (int) $banner['status'] === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (int) $banner['status'] === 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div><label>Replace Image</label><input name="image" type="file" accept="image/*"></div>
        </div>
        <?php if ($banner['image']): ?>
            <label>Current Image</label>
            <img class="thumb" src="<?= htmlspecialchars($banner['image']) ?>">
        <?php endif; ?>
        <div style="height:12px;"></div>
        <button>Save Banner</button>
        <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/banners') ?>">Cancel</a>
    </form>
</section>
