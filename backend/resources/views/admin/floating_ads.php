<?php
$targetOptions = [['value' => 'global|none|', 'label' => 'No redirect']];
foreach ($products as $product) {
    $module = (string) ($product['module_key'] ?? 'mart');
    if (!in_array($module, ['mart', 'ecommerce', 'medical'], true)) {
        continue;
    }
    $targetOptions[] = [
        'value' => $module . '|product|' . (int) $product['id'],
        'label' => strtoupper($module) . ' product #' . (int) $product['id'] . ' - ' . (string) $product['name'],
    ];
}
foreach ($vendors as $vendor) {
    $module = (string) ($vendor['module_key'] ?? 'mart');
    if (!in_array($module, ['mart', 'ecommerce', 'medical'], true)) {
        continue;
    }
    $targetOptions[] = [
        'value' => $module . '|store|' . (int) $vendor['id'],
        'label' => strtoupper($module) . ' store #' . (int) $vendor['id'] . ' - ' . (string) $vendor['shop_name'],
    ];
}
$targetValueFor = static fn (array $ad): string => (string) (($ad['target_module'] ?? 'global') . '|' . ($ad['target_type'] ?? 'none') . '|' . ($ad['target_value'] ?? ''));
$targetSelect = static function (string $name, string $selected = '') use ($targetOptions): void {
    echo '<select name="' . htmlspecialchars($name) . '">';
    foreach ($targetOptions as $option) {
        echo '<option value="' . htmlspecialchars($option['value']) . '"' . ($selected === $option['value'] ? ' selected' : '') . '>' . htmlspecialchars($option['label']) . '</option>';
    }
    echo '<option value="global|url|"' . (str_contains($selected, '|url|') ? ' selected' : '') . '>External URL / deep link</option>';
    echo '</select>';
};
?>
<section class="card">
    <h2>Add Floating Ad</h2>
    <form method="post" action="/admin/floating-ads" enctype="multipart/form-data">
        <div class="row">
            <div><label>Title</label><input name="title" placeholder="Offer title"></div>
            <div><label>CTA Text</label><input name="cta_text" value="Explore"></div>
            <div><label>Priority</label><input name="priority" type="number" value="0"></div>
        </div>
        <label>Message</label>
        <textarea name="message" placeholder="Optional text shown only when user taps the ad."></textarea>
        <div class="row">
            <div>
                <label>Redirect Target</label>
                <?php $targetSelect('target_ref'); ?>
            </div>
            <div><label>External URL / Deep Link</label><input name="target_value" placeholder="Only required when Redirect Target is URL"></div>
            <div><label>Fallback Link URL</label><input name="link_url" placeholder="Optional browser URL if app cannot resolve target"></div>
        </div>
        <div class="row">
            <div><label>Start At</label><input name="starts_at" type="datetime-local"></div>
            <div><label>End At</label><input name="ends_at" type="datetime-local"></div>
            <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <label>Upload Media</label>
        <input name="media" type="file" accept="image/*,video/mp4,video/webm,video/quicktime" required>
        <p style="color:var(--muted);">Use direct MP4/WebM/MOV or image. Video is cached locally by the app before playback.</p>
        <div style="height:12px;"></div><button>Add Floating Ad</button>
    </form>
</section>

<section class="card">
    <h2>Target Reference</h2>
    <p style="color:var(--muted);">Choose products or stores directly from Redirect Target. Product/store redirects currently open inside the app for Mart, E-Commerce, and Medical. For hotels, services, restaurants, real estate, campaign pages, or anything else, use External URL / deep link.</p>
</section>

<section class="card">
    <h2>Floating Ads</h2>
    <table>
        <thead>
        <tr>
            <th>Media</th><th>Text</th><th>Target</th><th>Schedule</th><th>Status</th><th>Update</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ads as $ad): ?>
            <tr>
                <td>
                    <?php if (($ad['media_type'] ?? '') === 'video'): ?>
                        <video class="thumb" src="<?= htmlspecialchars($ad['media_url'] ?? '') ?>" muted></video>
                    <?php elseif (!empty($ad['media_url'])): ?>
                        <img class="thumb" src="<?= htmlspecialchars($ad['media_url']) ?>">
                    <?php endif; ?>
                    <div class="pill"><?= htmlspecialchars($ad['media_type'] ?? '') ?></div>
                </td>
                <td>
                    <strong><?= htmlspecialchars($ad['title'] ?? '') ?></strong><br>
                    <?= htmlspecialchars($ad['message'] ?? '') ?><br>
                    <span class="pill"><?= htmlspecialchars($ad['cta_text'] ?? 'Explore') ?></span>
                </td>
                <td><?= htmlspecialchars(($ad['target_module'] ?? '') . ' / ' . ($ad['target_type'] ?? '') . ' / ' . ($ad['target_value'] ?? '')) ?></td>
                <td><?= htmlspecialchars(($ad['starts_at'] ?? '') ?: 'Now') ?><br><?= htmlspecialchars(($ad['ends_at'] ?? '') ?: 'No end') ?></td>
                <td><span class="pill"><?= !empty($ad['status']) ? 'Active' : 'Inactive' ?></span><br>Priority <?= (int) ($ad['priority'] ?? 0) ?></td>
                <td>
                    <form method="post" action="/admin/floating-ads/<?= (int) $ad['id'] ?>/update" enctype="multipart/form-data">
                        <div class="row">
                            <div><label>Title</label><input name="title" value="<?= htmlspecialchars($ad['title'] ?? '') ?>"></div>
                            <div><label>CTA</label><input name="cta_text" value="<?= htmlspecialchars($ad['cta_text'] ?? '') ?>"></div>
                            <div><label>Priority</label><input name="priority" type="number" value="<?= (int) ($ad['priority'] ?? 0) ?>"></div>
                        </div>
                        <input type="hidden" name="message" value="<?= htmlspecialchars($ad['message'] ?? '') ?>">
                        <label>Redirect Target</label>
                        <?php $targetSelect('target_ref', $targetValueFor($ad)); ?>
                        <label>Target URL / Value</label>
                        <input name="target_value" value="<?= htmlspecialchars(($ad['target_type'] ?? '') === 'url' ? ($ad['target_value'] ?? '') : '') ?>" placeholder="Only for URL redirects">
                        <label>Fallback Link</label>
                        <input name="link_url" value="<?= htmlspecialchars($ad['link_url'] ?? '') ?>">
                        <input type="hidden" name="starts_at" value="<?= htmlspecialchars($ad['starts_at'] ?? '') ?>">
                        <input type="hidden" name="ends_at" value="<?= htmlspecialchars($ad['ends_at'] ?? '') ?>">
                        <label>Status</label><select name="status"><option value="1" <?= !empty($ad['status']) ? 'selected' : '' ?>>Active</option><option value="0" <?= empty($ad['status']) ? 'selected' : '' ?>>Inactive</option></select>
                        <label>Replace Media</label><input name="media" type="file" accept="image/*,video/mp4,video/webm,video/quicktime">
                        <div style="height:8px;"></div><button>Save</button>
                    </form>
                </td>
                <td><a class="btn danger" href="/admin/floating-ads/<?= (int) $ad['id'] ?>/delete">Delete</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
