<section class="card">
    <div class="section-heading"><div><span class="eyebrow">Catalogue structure</span><h2>Edit Subcategory</h2></div></div>
    <form method="post" action="<?= htmlspecialchars($basePath) ?>/<?= (int) $subcategory['id'] ?>/edit" enctype="multipart/form-data">
        <div class="row">
            <div><label>Parent Category</label><select name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $subcategory['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Name</label><input name="name" value="<?= htmlspecialchars($subcategory['name']) ?>" required></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="<?= (int) $subcategory['sort_order'] ?>"></div>
        </div>
        <div class="row">
            <div><label>Status</label><select name="status"><option value="1" <?= !empty($subcategory['status']) ? 'selected' : '' ?>>Active</option><option value="0" <?= empty($subcategory['status']) ? 'selected' : '' ?>>Inactive</option></select></div>
            <div style="grid-column:span 2"><label>Replace Image</label><input name="image" type="file" accept="image/*"></div>
        </div>
        <?php if (!empty($subcategory['image'])): ?><img class="thumb" src="<?= htmlspecialchars($subcategory['image']) ?>" alt="Current image"><?php endif; ?>
        <div style="height:12px"></div><button>Save Changes</button> <a class="btn secondary" href="<?= htmlspecialchars($basePath) ?>">Cancel</a>
    </form>
</section>
