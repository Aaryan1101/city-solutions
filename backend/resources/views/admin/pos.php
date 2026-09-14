<section class="card">
    <h2>Create POS Order</h2>
    <form method="post" action="/admin/pos">
        <div class="row">
            <div><label>Customer Name</label><input name="customer_name" value="Walk-in Customer"></div>
            <div><label>Customer Phone</label><input name="customer_phone"></div>
            <div><label>Quantity</label><input name="quantity" type="number" min="1" value="1" required></div>
        </div>
        <label>Product</label>
        <select name="product_id" required>
            <?php foreach ($products as $product): ?>
                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?> - ₹<?= number_format((float) ($product['discount_price'] ?: $product['price']), 2) ?> - stock <?= (int) $product['stock'] ?></option>
            <?php endforeach; ?>
        </select>
        <label>Order Note</label><textarea name="order_note">POS order</textarea>
        <div style="height:12px;"></div><button>Create Paid POS Order</button>
    </form>
</section>
