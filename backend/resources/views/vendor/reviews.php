<section class="card">
    <h2>Product Reviews</h2>
    <table>
        <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Reply</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $review): ?>
            <tr>
                <td><?= htmlspecialchars($review['product_name']) ?></td>
                <td><?= htmlspecialchars($review['customer_name'] ?? 'Customer') ?></td>
                <td><?= (int) $review['rating'] ?>/5</td>
                <td><?= htmlspecialchars($review['comment'] ?? '') ?></td>
                <td>
                    <form method="post" action="/vendor/reviews/<?= $review['id'] ?>/reply">
                        <textarea name="reply"><?= htmlspecialchars($review['reply'] ?? '') ?></textarea>
                        <div style="height:8px;"></div><button>Save Reply</button>
                    </form>
                </td>
                <td><span class="pill"><?= ((int) $review['status']) === 1 ? 'Visible' : 'Hidden' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
