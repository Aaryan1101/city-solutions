<section class="card">
    <h2>Product Reviews</h2>
    <table>
        <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Reply</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $review): ?>
            <tr>
                <td><?= htmlspecialchars($review['product_name']) ?><?php if (!empty($review['vendor_name'])): ?><br><small><?= htmlspecialchars($review['vendor_name']) ?></small><?php endif; ?></td>
                <td><?= htmlspecialchars($review['customer_name'] ?? 'Customer') ?></td>
                <td><?= (int) $review['rating'] ?>/5</td>
                <td><?= htmlspecialchars($review['comment'] ?? '') ?></td>
                <td><?= htmlspecialchars($review['reply'] ?? '') ?></td>
                <td><span class="pill"><?= ((int) $review['status']) === 1 ? 'Visible' : 'Hidden' ?></span></td>
                <td>
                    <form method="post" action="<?= htmlspecialchars($basePath ?? '/admin/reviews') ?>/<?= $review['id'] ?>/status">
                        <input style="width:auto;" name="status" type="checkbox" value="1" <?= ((int) $review['status']) === 1 ? 'checked' : '' ?>> Visible
                        <div style="height:8px;"></div><button>Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
