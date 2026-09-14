<section class="card">
    <h2>Edit Product</h2>
    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/products') ?>/<?= $product['id'] ?>/edit" enctype="multipart/form-data">
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
            <div>
                <label>Subcategory</label>
                <select id="product-subcategory" name="subcategory_id">
                    <option value="">No Subcategory</option>
                    <?php foreach (($subcategories ?? []) as $subcategory): ?>
                        <option value="<?= (int) $subcategory['id'] ?>" data-category="<?= (int) $subcategory['category_id'] ?>" <?= (int) ($product['subcategory_id'] ?? 0) === (int) $subcategory['id'] ? 'selected' : '' ?>><?= htmlspecialchars($subcategory['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Vendor</label>
                <select name="vendor_id">
                    <option value="">Admin Store</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?= $vendor['id'] ?>" <?= (int) ($product['vendor_id'] ?? 0) === (int) $vendor['id'] ? 'selected' : '' ?>><?= htmlspecialchars($vendor['shop_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label>Zone</label>
        <select name="zone_id">
            <option value="">All Zones</option>
            <?php foreach (($zones ?? []) as $zone): ?>
                <option value="<?= (int) $zone['id'] ?>" <?= (int) ($product['zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= htmlspecialchars($zone['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Brand</label>
        <select name="brand_id">
            <option value="">No Brand</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand['id'] ?>" <?= (int) ($product['brand_id'] ?? 0) === (int) $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars($brand['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="row">
            <div><label>Unit</label><input name="unit" value="<?= htmlspecialchars($product['unit']) ?>"></div>
            <div><label>Price</label><input name="price" type="number" step="0.01" value="<?= htmlspecialchars((string) $product['price']) ?>" required></div>
            <div><label>Discount Price</label><input name="discount_price" type="number" step="0.01" value="<?= htmlspecialchars((string) ($product['discount_price'] ?? '')) ?>"></div>
        </div>
        <div class="row">
            <div><label>Stock</label><input name="stock" type="number" value="<?= (int) $product['stock'] ?>"></div>
            <div><label>SKU</label><input name="sku" value="<?= htmlspecialchars($product['sku'] ?? '') ?>"></div>
            <div><label>Barcode</label><input name="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>"></div>
        </div>
        <div class="row">
            <div><label>Tax %</label><input name="tax_percent" type="number" step="0.01" value="<?= htmlspecialchars((string) ($product['tax_percent'] ?? 0)) ?>"></div>
            <div><label>Product Shipping Cost</label><input name="shipping_cost" type="number" step="0.01" value="<?= htmlspecialchars((string) ($product['shipping_cost'] ?? 0)) ?>"></div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="1" <?= (int) $product['status'] === 1 ? 'selected' : '' ?>>Approved / Active</option>
                    <option value="0" <?= (int) $product['status'] === 0 ? 'selected' : '' ?>>Pending / Inactive</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div><label>Freshness / Quality Note</label><input name="freshness_note" value="<?= htmlspecialchars($product['freshness_note'] ?? '') ?>"></div>
            <div><label>Expiry Date</label><input name="expiry_date" type="date" value="<?= htmlspecialchars($product['expiry_date'] ?? '') ?>"></div>
            <div><label>Shelf Life</label><input name="shelf_life" value="<?= htmlspecialchars($product['shelf_life'] ?? '') ?>"></div>
        </div>
        <div class="row">
            <div><label>Warranty / Guarantee</label><input name="warranty_note" value="<?= htmlspecialchars($product['warranty_note'] ?? '') ?>"></div>
            <div style="grid-column:span 2"><label>Return Policy</label><input name="return_policy" value="<?= htmlspecialchars($product['return_policy'] ?? '') ?>"></div>
        </div>
        <div class="row">
            <div><label>Featured</label><div><input style="width:auto;" name="is_featured" type="checkbox" value="1" <?= (int) $product['is_featured'] === 1 ? 'checked' : '' ?>> Featured product</div></div>
            <div><label>Digital Product</label><div><input style="width:auto;" name="is_digital" type="checkbox" value="1" <?= (int) ($product['is_digital'] ?? 0) === 1 ? 'checked' : '' ?>> Digital delivery</div></div>
        </div>
        <?php if (($moduleKey ?? '') === 'medical'): ?>
            <div class="row">
                <div><label>Medicine Type</label><select name="medicine_type">
                    <?php foreach (['otc' => 'OTC', 'prescription_required' => 'Prescription Required', 'restricted' => 'Restricted / Pharmacist Review', 'blocked_online' => 'Blocked Online'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($product['medicine_type'] ?? 'otc') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div><label>Schedule / Risk Tag</label><input name="schedule_tag" value="<?= htmlspecialchars($product['schedule_tag'] ?? '') ?>"></div>
                <div><label>Max Qty Per Order</label><input name="max_qty_per_order" type="number" min="1" value="<?= htmlspecialchars((string) ($product['max_qty_per_order'] ?? '')) ?>"></div>
            </div>
            <div class="row">
                <div><label>Max Qty Per Month</label><input name="max_qty_per_month" type="number" min="1" value="<?= htmlspecialchars((string) ($product['max_qty_per_month'] ?? '')) ?>"></div>
                <div><label>Pharmacist Review</label><div><input style="width:auto;" name="requires_pharmacist_review" type="checkbox" value="1" <?= (int) ($product['requires_pharmacist_review'] ?? 0) === 1 ? 'checked' : '' ?>> Require review before fulfillment</div></div>
                <div><label>Age Confirmation</label><div><input style="width:auto;" name="requires_age_confirmation" type="checkbox" value="1" <?= (int) ($product['requires_age_confirmation'] ?? 0) === 1 ? 'checked' : '' ?>> Require customer age confirmation</div></div>
            </div>
        <?php endif; ?>
        <div class="row">
            <div><label>Flash Deal</label><div><input style="width:auto;" name="is_flash_deal" type="checkbox" value="1" <?= (int) ($product['is_flash_deal'] ?? 0) === 1 ? 'checked' : '' ?>> Include in flash deals</div></div>
            <div><label>Flash Deal Ends</label><input name="flash_deal_ends_at" type="datetime-local" value="<?= htmlspecialchars(isset($product['flash_deal_ends_at']) && $product['flash_deal_ends_at'] ? str_replace(' ', 'T', substr((string) $product['flash_deal_ends_at'], 0, 16)) : '') ?>"></div>
            <div><label>Clearance Sale</label><div><input style="width:auto;" name="is_clearance" type="checkbox" value="1" <?= (int) ($product['is_clearance'] ?? 0) === 1 ? 'checked' : '' ?>> Include in clearance</div></div>
        </div>
        <label>Digital File URL</label><input name="digital_file_url" value="<?= htmlspecialchars($product['digital_file_url'] ?? '') ?>">
        <div class="row">
            <div><label>SEO Title</label><input name="seo_title" value="<?= htmlspecialchars($product['seo_title'] ?? '') ?>"></div>
            <div><label>SEO Description</label><input name="seo_description" value="<?= htmlspecialchars($product['seo_description'] ?? '') ?>"></div>
            <div><label>Colors</label><textarea name="colors" placeholder="Red&#10;Blue&#10;Green"><?= htmlspecialchars($colorsText ?? '') ?></textarea></div>
        </div>
        <label>Attributes</label><textarea name="attributes" placeholder="Size: Large&#10;Material: Cotton"><?= htmlspecialchars($attributesText ?? '') ?></textarea>
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
        <button>Save Product</button>
        <a class="btn secondary" href="<?= htmlspecialchars($basePath ?? '/admin/products') ?>">Cancel</a>
    </form>
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
