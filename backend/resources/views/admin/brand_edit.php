<section class="card">
    <h2>Edit Brand</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/brands') ?>/<?= $brand['id'] ?>/edit" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" value="<?= htmlspecialchars($brand['name']) ?>" required></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="<?= (int) $brand['sort_order'] ?>"></div>
            <div><label>Status</label><select name="status"><option value="1" <?= $brand['status'] ? 'selected' : '' ?>>Active</option><option value="0" <?= !$brand['status'] ? 'selected' : '' ?>>Inactive</option></select></div>
        </div>
        <?php if ($brand['image']): ?>
            <label>Current Image</label>
            <img class="thumb" src="<?= htmlspecialchars($brand['image']) ?>">
        <?php endif; ?>
        <label>Replace Logo/Image</label><input name="image" type="file" accept="image/*">
        <div style="height:12px;"></div>
        <button>Save Brand</button>
        <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/brands') ?>">Cancel</a>
    </form>
</section>
