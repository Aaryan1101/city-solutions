<section class="card">
    <h2>Edit Product</h2>
    <p style="color:var(--muted);">Saved changes go back to admin approval before customers see them.</p>
    <form method="post" action="/vendor/products/<?= $product['id'] ?>/edit" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" value="<?= htmlspecialchars($product['name']) ?>" required></div>
            <div>
                <label>Category</label>
                <select id="product-category" name="category_id">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= (int) $product['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Subcategory</label><select id="product-subcategory" name="subcategory_id"><option value="">No Subcategory</option><?php foreach (($subcategories ?? []) as $subcategory): ?><option value="<?= (int) $subcategory['id'] ?>" data-category="<?= (int) $subcategory['category_id'] ?>" <?= (int) ($product['subcategory_id'] ?? 0) === (int) $subcategory['id'] ? 'selected' : '' ?>><?= htmlspecialchars($subcategory['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="row">
            <div>
                <label>Brand</label>
                <select name="brand_id">
                    <option value="">No Brand</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= $brand['id'] ?>" <?= (int) ($product['brand_id'] ?? 0) === (int) $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars($brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Unit</label><input name="unit" value="<?= htmlspecialchars($product['unit']) ?>"></div>
        </div>
        <div class="row">
            <div><label>Price</label><input name="price" type="number" step="0.01" value="<?= htmlspecialchars((string) $product['price']) ?>" required></div>
            <div><label>Discount Price</label><input name="discount_price" type="number" step="0.01" value="<?= htmlspecialchars((string) ($product['discount_price'] ?? '')) ?>"></div>
            <div><label>Stock</label><input name="stock" type="number" value="<?= (int) $product['stock'] ?>"></div>
        </div>
        <label>SKU optional</label><input name="sku" value="<?= htmlspecialchars($product['sku'] ?? '') ?>" placeholder="Auto-generated if empty">
        <label>Description</label><textarea name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        <?php if ($product['thumbnail']): ?>
            <label>Current Thumbnail</label>
            <img class="thumb" src="<?= htmlspecialchars($product['thumbnail']) ?>">
        <?php endif; ?>
        <?php if (!empty($images)): ?>
            <label>Gallery</label>
            <?php foreach ($images as $image): ?><img class="thumb" src="<?= htmlspecialchars($image['image']) ?>"><?php endforeach; ?>
        <?php endif; ?>
        <label>Replace Thumbnail</label><input name="thumbnail" type="file" accept="image/*">
        <label>Add More Images</label><input name="images[]" type="file" accept="image/*" multiple>
        <label>Variants</label>
        <textarea name="variants" placeholder="Name|Unit|Price|Discount Price|Stock|SKU&#10;Small Pack|250 g|60||20|SMALL-250"><?= htmlspecialchars($variantsText ?? '') ?></textarea>
        <div style="height:12px;"></div>
        <button>Save For Approval</button>
        <a class="btn secondary" href="/vendor/products">Cancel</a>
    </form>
</section>
<script>
(() => { const c=document.getElementById('product-category'), s=document.getElementById('product-subcategory'); if(!c||!s)return; const f=()=>{[...s.options].forEach((o,i)=>{if(i===0)return;o.hidden=o.dataset.category!==c.value;o.disabled=o.hidden;});if(s.selectedOptions[0]?.disabled)s.value='';};c.addEventListener('change',f);f(); })();
</script>
