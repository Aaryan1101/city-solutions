<section class="card">
    <h2>Edit Category</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/categories') ?>/<?= $category['id'] ?>/edit" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" value="<?= htmlspecialchars($category['name']) ?>" required></div>
            <div><label>Category Shipping Cost</label><input name="shipping_cost" type="number" step="0.01" value="<?= htmlspecialchars((string) ($category['shipping_cost'] ?? 0)) ?>"></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="<?= (int) $category['sort_order'] ?>"></div>
        </div>
        <label>Status</label>
        <select name="status">
            <option value="1" <?= (int) $category['status'] === 1 ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= (int) $category['status'] === 0 ? 'selected' : '' ?>>Inactive</option>
        </select>
        <?php if ($category['image']): ?>
            <label>Current Image</label>
            <img class="thumb" src="<?= htmlspecialchars($category['image']) ?>">
        <?php endif; ?>
        <label>Replace Image</label><input name="image" type="file" accept="image/*">
        <div style="height:12px;"></div>
        <button>Save Category</button>
        <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/categories') ?>">Cancel</a>
    </form>
</section>
