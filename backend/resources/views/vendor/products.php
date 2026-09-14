<section class="card">
    <h2>Submit Product</h2>
    <form method="post" action="/vendor/products" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Category</label><select id="product-category" name="category_id"><?php foreach ($categories as $category): ?><option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Brand</label><select name="brand_id"><option value="">No Brand</option><?php foreach ($brands as $brand): ?><option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <label>Subcategory</label><select id="product-subcategory" name="subcategory_id"><option value="">No Subcategory</option><?php foreach (($subcategories ?? []) as $subcategory): ?><option value="<?= (int) $subcategory['id'] ?>" data-category="<?= (int) $subcategory['category_id'] ?>"><?= htmlspecialchars($subcategory['name']) ?></option><?php endforeach; ?></select>
        <label>Unit</label><input name="unit" value="piece">
        <div class="row">
            <div><label>Price</label><input name="price" type="number" step="0.01" required></div>
            <div><label>Discount Price</label><input name="discount_price" type="number" step="0.01"></div>
            <div><label>Stock</label><input name="stock" type="number" value="0"></div>
        </div>
        <label>SKU optional</label><input name="sku" placeholder="Auto-generated if empty">
        <label>Description</label><textarea name="description"></textarea>
        <label>Thumbnail</label><input name="thumbnail" type="file" accept="image/*">
        <label>More Images</label><input name="images[]" type="file" accept="image/*" multiple>
        <label>Variants</label>
        <textarea name="variants" placeholder="Name|Unit|Price|Discount Price|Stock|SKU&#10;Small Pack|250 g|60||20|SMALL-250"></textarea>
        <div style="height:12px;"></div><button>Submit For Approval</button>
    </form>
</section>

<section class="card">
    <h2>My Products</h2>
    <table>
        <thead><tr><th>Image</th><th>Name</th><th>Module</th><th>Brand</th><th>Category</th><th>Price</th><th>Stock</th><th>Approval</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?php if ($product['thumbnail']): ?><img class="thumb" src="<?= htmlspecialchars($product['thumbnail']) ?>"><?php endif; ?></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><span class="pill"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $product['module_key'] ?? 'mart'))) ?></span></td>
                <td><?= htmlspecialchars($product['brand_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($product['category_name'] ?? '-') ?><?php if (!empty($product['subcategory_name'])): ?><br><small>› <?= htmlspecialchars($product['subcategory_name']) ?></small><?php endif; ?></td>
                <td>₹<?= number_format((float) $product['price'], 2) ?></td>
                <td><?= (int) $product['stock'] ?></td>
                <td><span class="pill"><?= $product['status'] ? 'Approved' : 'Pending' ?></span></td>
                <td>
                    <a class="btn secondary" href="/vendor/products/<?= $product['id'] ?>/edit">Edit</a>
                    <a class="btn danger" href="/vendor/products/<?= $product['id'] ?>/delete">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<script>
(() => { const c=document.getElementById('product-category'), s=document.getElementById('product-subcategory'); if(!c||!s)return; const f=()=>{[...s.options].forEach((o,i)=>{if(i===0)return;o.hidden=o.dataset.category!==c.value;o.disabled=o.hidden;});if(s.selectedOptions[0]?.disabled)s.value='';};c.addEventListener('change',f);f(); })();
</script>
