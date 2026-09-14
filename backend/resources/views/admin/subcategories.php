<section class="card">
    <div class="section-heading">
        <div>
            <span class="eyebrow"><?= htmlspecialchars($moduleLabel ?? 'Commerce') ?> catalogue</span>
            <h2>Add Subcategory</h2>
            <p>Group products more precisely beneath a main category. Subcategories stay isolated to this module.</p>
        </div>
        <span class="pill"><?= count($subcategories ?? []) ?> configured</span>
    </div>
    <?php if (empty($categories)): ?>
        <div class="notice warning">Create at least one main category before adding subcategories.</div>
    <?php else: ?>
    <form method="post" action="<?= htmlspecialchars($basePath) ?>" enctype="multipart/form-data">
        <div class="row">
            <div><label>Parent Category</label><select name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?><?= empty($category['status']) ? ' (inactive)' : '' ?></option><?php endforeach; ?></select></div>
            <div><label>Subcategory Name</label><input name="name" required placeholder="e.g. Smartphones"></div>
            <div><label>Sort Order</label><input name="sort_order" type="number" value="0"></div>
        </div>
        <div class="row">
            <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div style="grid-column:span 2"><label>Image</label><input name="image" type="file" accept="image/*"></div>
        </div>
        <button>Add Subcategory</button>
    </form>
    <?php endif; ?>
</section>

<section class="card">
    <div class="section-heading"><div><span class="eyebrow">Catalogue structure</span><h2>Subcategories</h2></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Image</th><th>Subcategory</th><th>Parent</th><th>Products</th><th>Status</th><th>Sort</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach (($subcategories ?? []) as $subcategory): ?>
                <tr>
                    <td><?php if (!empty($subcategory['image'])): ?><img class="thumb" src="<?= htmlspecialchars($subcategory['image']) ?>" alt=""><?php else: ?><span class="thumb-placeholder">—</span><?php endif; ?></td>
                    <td><strong><?= htmlspecialchars($subcategory['name']) ?></strong><br><small><?= htmlspecialchars($subcategory['slug']) ?></small></td>
                    <td><?= htmlspecialchars($subcategory['category_name']) ?></td>
                    <td><span class="pill"><?= (int) $subcategory['products_count'] ?> products</span></td>
                    <td><span class="pill"><?= !empty($subcategory['status']) ? 'Active' : 'Inactive' ?></span></td>
                    <td><?= (int) $subcategory['sort_order'] ?></td>
                    <td><a class="btn secondary" href="<?= htmlspecialchars($basePath) ?>/<?= (int) $subcategory['id'] ?>/edit">Edit</a> <a class="btn danger" href="<?= htmlspecialchars($basePath) ?>/<?= (int) $subcategory['id'] ?>/delete" onclick="return confirm('Delete this subcategory? Assigned products will become uncategorized at subcategory level.')">Delete</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($subcategories)): ?><tr><td colspan="7"><div class="empty-inline">No subcategories yet.</div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
