<section class="card">
    <h2>Add Product</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/products') ?>" enctype="multipart/form-data">
        <div class="row">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Category</label><select id="product-category" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Brand</label><select name="brand_id"><option value="">No Brand</option><?php foreach ($brands as $brand): ?><option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="row">
            <div><label>Subcategory</label><select id="product-subcategory" name="subcategory_id"><option value="">No Subcategory</option><?php foreach (($subcategories ?? []) as $subcategory): ?><option value="<?= (int) $subcategory['id'] ?>" data-category="<?= (int) $subcategory['category_id'] ?>"><?= htmlspecialchars($subcategory['name']) ?></option><?php endforeach; ?></select></div>
            <div style="grid-column:span 2"><small>Only subcategories belonging to the selected main category are available.</small></div>
        </div>
        <div class="row">
            <div><label>Zone</label><select name="zone_id"><option value="">All Zones</option><?php foreach (($zones ?? []) as $zone): ?><option value="<?= (int) $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option><?php endforeach; ?></select></div>
            <div style="grid-column:span 2"><label>Vendor</label><select name="vendor_id"><option value="">Admin Store</option><?php foreach ($vendors as $vendor): ?><option value="<?= $vendor['id'] ?>"><?= htmlspecialchars($vendor['shop_name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="row">
            <div><label>Unit</label><input name="unit" value="piece"></div>
            <div><label>Price</label><input name="price" type="number" step="0.01" required></div>
            <div><label>Discount Price</label><input name="discount_price" type="number" step="0.01"></div>
        </div>
        <div class="row">
            <div><label>Stock</label><input name="stock" type="number" value="0"></div>
            <div><label>SKU</label><input name="sku"></div>
            <div><label>Barcode</label><input name="barcode"></div>
        </div>
        <div class="row">
            <div><label>Tax %</label><input name="tax_percent" type="number" step="0.01" value="0"></div>
            <div><label>Product Shipping Cost</label><input name="shipping_cost" type="number" step="0.01" value="0"></div>
            <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <div class="row">
            <div><label>Freshness / Quality Note</label><input name="freshness_note" placeholder="Picked today, sealed pack, etc."></div>
            <div><label>Expiry Date</label><input name="expiry_date" type="date"></div>
            <div><label>Shelf Life</label><input name="shelf_life" placeholder="Best before 7 days / 12 months"></div>
        </div>
        <div class="row">
            <div><label>Warranty / Guarantee</label><input name="warranty_note" placeholder="1 year brand warranty"></div>
            <div style="grid-column:span 2"><label>Return Policy</label><input name="return_policy" placeholder="7 day replacement / non-returnable fresh item"></div>
        </div>
        <?php if (($moduleKey ?? '') === 'medical'): ?>
            <div class="row">
                <div><label>Medicine Type</label><select name="medicine_type"><option value="otc">OTC</option><option value="prescription_required">Prescription Required</option><option value="restricted">Restricted / Pharmacist Review</option><option value="blocked_online">Blocked Online</option></select></div>
                <div><label>Schedule / Risk Tag</label><input name="schedule_tag" placeholder="Schedule H, H1, high-risk, etc."></div>
                <div><label>Max Qty Per Order</label><input name="max_qty_per_order" type="number" min="1"></div>
            </div>
            <div class="row">
                <div><label>Max Qty Per Month</label><input name="max_qty_per_month" type="number" min="1"></div>
                <div><label>Pharmacist Review</label><div><input style="width:auto;" name="requires_pharmacist_review" type="checkbox" value="1"> Require review before fulfillment</div></div>
                <div><label>Age Confirmation</label><div><input style="width:auto;" name="requires_age_confirmation" type="checkbox" value="1"> Require customer age confirmation</div></div>
            </div>
        <?php endif; ?>
        <label>Digital Product</label><div><input style="width:auto;" name="is_digital" type="checkbox" value="1"> Digital delivery</div>
        <div class="row">
            <div><label>Flash Deal</label><div><input style="width:auto;" name="is_flash_deal" type="checkbox" value="1"> Include in flash deals</div></div>
            <div><label>Flash Deal Ends</label><input name="flash_deal_ends_at" type="datetime-local"></div>
            <div><label>Clearance Sale</label><div><input style="width:auto;" name="is_clearance" type="checkbox" value="1"> Include in clearance</div></div>
        </div>
        <label>Featured</label><div><input style="width:auto;" name="is_featured" type="checkbox" value="1"> Featured product</div>
        <label>Digital File URL</label><input name="digital_file_url" placeholder="https://...">
        <div class="row">
            <div><label>SEO Title</label><input name="seo_title"></div>
            <div><label>SEO Description</label><input name="seo_description"></div>
            <div><label>Colors</label><textarea name="colors" placeholder="Red&#10;Blue&#10;Green"></textarea></div>
        </div>
        <label>Attributes</label><textarea name="attributes" placeholder="Size: Large&#10;Material: Cotton"></textarea>
        <label>Description</label><textarea name="description"></textarea>
        <label>Thumbnail</label><input name="thumbnail" type="file" accept="image/*">
        <label>More Images</label><input name="images[]" type="file" accept="image/*" multiple>
        <label>Variants</label>
        <textarea name="variants" placeholder="Name|Unit|Price|Discount Price|Stock|SKU&#10;Small Pack|250 g|60||20|SMALL-250"></textarea>
        <div style="height:12px;"></div><button>Add Product</button>
    </form>
</section>

<section class="card">
    <h2>Products</h2>
    <table>
        <thead><tr><th>Image</th><th>Name</th><th>Zone</th><th>Vendor</th><th>Brand</th><th>Category</th><th>Price</th><th>Stock</th><th>Depth</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?php if ($product['thumbnail']): ?><img class="thumb" src="<?= htmlspecialchars($product['thumbnail']) ?>"><?php endif; ?></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= htmlspecialchars($product['zone_name'] ?? 'All Zones') ?></td>
                <td><?= htmlspecialchars($product['vendor_name'] ?? 'Admin Store') ?></td>
                <td><?= htmlspecialchars($product['brand_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($product['category_name'] ?? '-') ?><?php if (!empty($product['subcategory_name'])): ?><br><small>› <?= htmlspecialchars($product['subcategory_name']) ?></small><?php endif; ?></td>
                <td>₹<?= number_format((float) $product['price'], 2) ?></td>
                <td><?= (int) $product['stock'] ?></td>
                <td><?php if (!empty($product['barcode'])): ?><small>Barcode: <?= htmlspecialchars($product['barcode']) ?></small><br><?php endif; ?><small>Tax: <?= number_format((float) ($product['tax_percent'] ?? 0), 2) ?>%</small><br><small>Ship: ₹<?= number_format((float) ($product['shipping_cost'] ?? 0), 2) ?></small><?php if (!empty($product['freshness_note'])): ?><br><small>Fresh: <?= htmlspecialchars($product['freshness_note']) ?></small><?php endif; ?><?php if (!empty($product['expiry_date'])): ?><br><small>Expiry: <?= htmlspecialchars($product['expiry_date']) ?></small><?php endif; ?><?php if (!empty($product['shelf_life'])): ?><br><small>Shelf: <?= htmlspecialchars($product['shelf_life']) ?></small><?php endif; ?><?php if (!empty($product['warranty_note'])): ?><br><small>Warranty: <?= htmlspecialchars($product['warranty_note']) ?></small><?php endif; ?><?php if (($moduleKey ?? '') === 'medical'): ?><br><small>Medicine: <?= htmlspecialchars($product['medicine_type'] ?? 'otc') ?></small><?php if (!empty($product['schedule_tag'])): ?><br><small>Tag: <?= htmlspecialchars($product['schedule_tag']) ?></small><?php endif; ?><?php if (!empty($product['max_qty_per_order'])): ?><br><small>Max/order: <?= (int) $product['max_qty_per_order'] ?></small><?php endif; ?><?php if (!empty($product['max_qty_per_month'])): ?><br><small>Max/month: <?= (int) $product['max_qty_per_month'] ?></small><?php endif; ?><?php endif; ?><?php if (!empty($product['is_digital'])): ?><br><small>Digital</small><?php endif; ?><?php if (!empty($product['is_flash_deal'])): ?><br><small>Flash Deal</small><?php endif; ?><?php if (!empty($product['is_clearance'])): ?><br><small>Clearance</small><?php endif; ?></td>
                <td><span class="pill"><?= $product['status'] ? 'Approved' : (($product['vendor_id'] ?? null) ? 'Pending Approval' : 'Inactive') ?></span></td>
                <td>
                    <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/products') ?>/<?= $product['id'] ?>/edit">Edit</a>
                    <a class="btn danger" href="<?= htmlspecialchars($basePath ?? '/admin/products') ?>/<?= $product['id'] ?>/delete">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<script>
(() => {
    const category = document.getElementById('product-category');
    const subcategory = document.getElementById('product-subcategory');
    if (!category || !subcategory) return;
    const filter = () => {
        const selected = category.value;
        [...subcategory.options].forEach((option, index) => {
            if (index === 0) return;
            option.hidden = option.dataset.category !== selected;
            option.disabled = option.hidden;
        });
        if (subcategory.selectedOptions[0]?.disabled) subcategory.value = '';
    };
    category.addEventListener('change', filter);
    filter();
})();
</script>
