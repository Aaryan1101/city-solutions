<?php
$moduleSettings = [
    'mart' => 'Mart',
    'ecommerce' => 'E-Commerce',
    'medical' => 'Medical',
    'services' => 'Services',
    'hotel' => 'Hotels',
    'real_estate' => 'Real Estate',
    'restaurant' => 'Restaurants',
];
$moduleDefaultName = static fn (string $module): string => match ($module) {
    'ecommerce' => 'City E-Commerce',
    'medical' => 'City Medical',
    'services' => 'City Services',
    'hotel' => 'City Hotels',
    'real_estate' => 'City Real Estate',
    'restaurant' => 'City Restaurants',
    default => 'City Solutions Mart',
};
$settingValue = static fn (array $settings, string $module, string $key, string $default = ''): string => (string) ($settings[$module . '_' . $key] ?? $settings[$key] ?? $default);
$paymentGateways = [
    'manual' => 'Manual / Generic Gateway',
    'razorpay' => 'Razorpay',
    'stripe' => 'Stripe',
    'paypal' => 'PayPal',
    'paytm' => 'Paytm',
    'payu' => 'PayU',
    'cashfree' => 'Cashfree',
    'phonepe' => 'PhonePe',
    'ccavenue' => 'CCAvenue',
    'paystack' => 'Paystack',
    'flutterwave' => 'Flutterwave',
    'ssl_commerz' => 'SSLCommerz',
    'bkash' => 'bKash',
    'mercadopago' => 'MercadoPago',
    'custom' => 'Custom HTTPS Gateway',
];
$gatewaySelect = static function (string $name, string $selected) use ($paymentGateways): void {
    echo '<select name="' . htmlspecialchars($name) . '">';
    foreach ($paymentGateways as $key => $label) {
        echo '<option value="' . htmlspecialchars($key) . '"' . ($selected === $key ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
    }
    echo '</select>';
};
$webhookPath = static fn (string $module): string => match ($module) {
    'mart' => '/api/v1/mart/payments/webhook',
    'ecommerce' => '/api/v1/ecommerce/payments/webhook',
    'medical' => '/api/v1/medical/payments/webhook',
    'services' => '/api/v1/services/payments/webhook',
    'hotel' => '/api/v1/hotels/payments/webhook',
    'restaurant' => '/api/v1/restaurants/payments/webhook',
    default => '',
};
?>
<section class="card">
    <h2>Mart Settings</h2>
    <form method="post" action="/admin/settings">
        <input type="hidden" name="settings_scope" value="global">
        <div class="row">
            <div><label>App Name</label><input name="app_name" value="<?= htmlspecialchars($settings['app_name']) ?>" required></div>
            <div><label>Currency</label><input name="currency" value="<?= htmlspecialchars($settings['currency']) ?>" required></div>
            <div><label>Currency Symbol</label><input name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" required></div>
        </div>
        <div class="row">
            <div><label>Minimum Order Amount</label><input name="minimum_order_amount" type="number" step="0.01" value="<?= htmlspecialchars($settings['minimum_order_amount']) ?>"></div>
            <div><label>Delivery Charge</label><input name="delivery_charge" type="number" step="0.01" value="<?= htmlspecialchars($settings['delivery_charge']) ?>"></div>
            <div><label>Vendor Commission %</label><input name="vendor_commission_percent" type="number" step="0.01" min="0" max="100" value="<?= htmlspecialchars($settings['vendor_commission_percent']) ?>"></div>
        </div>
        <div class="row">
            <div>
                <label>Default Locale</label>
                <select name="app_locale">
                    <option value="en" <?= ($settings['app_locale'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="hi" <?= ($settings['app_locale'] ?? 'en') === 'hi' ? 'selected' : '' ?>>Hindi</option>
                </select>
            </div>
            <div><label>Supported Locales</label><input name="supported_locales" value="<?= htmlspecialchars($settings['supported_locales'] ?? 'en,hi') ?>"></div>
        </div>
        <div class="row">
            <div><label>Cash On Delivery</label><div><input style="width:auto;" name="cod_enabled" type="checkbox" value="1" <?= $settings['cod_enabled'] === '1' ? 'checked' : '' ?>> Enabled</div></div>
            <div><label>Online Payment</label><div><input style="width:auto;" name="online_payment_enabled" type="checkbox" value="1" <?= ($settings['online_payment_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Enabled</div></div>
            <div><label>Manual / Bank Transfer</label><div><input style="width:auto;" name="manual_payment_enabled" type="checkbox" value="1" <?= ($settings['manual_payment_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Enabled</div></div>
        </div>
        <div class="row">
            <div><label>Wallet Payment</label><div><input style="width:auto;" name="wallet_payment_enabled" type="checkbox" value="1" <?= ($settings['wallet_payment_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Enabled</div></div>
        </div>
        <h2>Payment Setup</h2>
        <div class="row">
            <div><label>Online Payment Title</label><input name="online_payment_title" value="<?= htmlspecialchars($settings['online_payment_title'] ?? 'Online Payment') ?>"></div>
            <div><label>Gateway</label><?php $gatewaySelect('online_payment_gateway', (string) ($settings['online_payment_gateway'] ?? 'manual')); ?></div>
            <div><label>Public Key</label><input name="online_payment_public_key" value="<?= htmlspecialchars($settings['online_payment_public_key'] ?? '') ?>"></div>
        </div>
        <div class="row">
            <div><label>Secret Key</label><input type="password" name="online_payment_secret_key" value="" placeholder="<?= !empty($settings['online_payment_secret_key']) ? 'Configured - enter new value to replace' : 'Enter secret key' ?>"></div>
            <div><label>Webhook Secret</label><input type="password" name="online_payment_webhook_secret" value="" placeholder="<?= !empty($settings['online_payment_webhook_secret']) ? 'Configured - enter new value to replace' : 'Enter webhook secret' ?>"></div>
            <div><label>Environment</label><select name="online_payment_environment"><option value="test" <?= ($settings['online_payment_environment'] ?? 'test') === 'test' ? 'selected' : '' ?>>Test</option><option value="live" <?= ($settings['online_payment_environment'] ?? 'test') === 'live' ? 'selected' : '' ?>>Live</option></select></div>
        </div>
        <label>Merchant / Account ID</label>
        <input name="online_payment_merchant_id" value="<?= htmlspecialchars($settings['online_payment_merchant_id'] ?? '') ?>" placeholder="Razorpay account id / Stripe account id / gateway merchant id">
        <label>Online Payment Description</label>
        <textarea name="online_payment_description"><?= htmlspecialchars($settings['online_payment_description'] ?? '') ?></textarea>
        <label>Online Payment Instructions</label>
        <textarea name="online_payment_instructions"><?= htmlspecialchars($settings['online_payment_instructions'] ?? '') ?></textarea>
        <div class="row">
            <div><label>Gateway Capture URL</label><input name="online_payment_capture_url" value="<?= htmlspecialchars($settings['online_payment_capture_url'] ?? '') ?>"></div>
            <div><label>Gateway Status URL</label><input name="online_payment_status_url" value="<?= htmlspecialchars($settings['online_payment_status_url'] ?? '') ?>"></div>
            <div><label>Gateway Refund URL</label><input name="online_payment_refund_url" value="<?= htmlspecialchars($settings['online_payment_refund_url'] ?? '') ?>"></div>
        </div>
        <label>Gateway Auth Header</label>
        <input type="password" name="online_payment_auth_header" value="" placeholder="<?= !empty($settings['online_payment_auth_header']) ? 'Configured - enter new value to replace' : 'Authorization: Bearer secret-token' ?>">
        <div class="info">
            <strong>Payment webhook setup</strong><br>
            Choose a gateway preset for admin clarity. For full automatic capture/refund, enter that gateway's HTTPS capture/status/refund API URLs plus either a secret key or an auth header. If your gateway uses its own hosted checkout, keep the public key/instructions filled for the app and use webhook verification for final status.
            <br><br>
            Use <code>X-City-Signature</code> or <code>X-Webhook-Signature</code> with HMAC-SHA256 of the raw JSON body using the webhook secret.
            Orders accept <code>order_id</code> or <code>order_number</code>. Bookings accept <code>booking_id</code> or <code>booking_number</code>.
            Success statuses: <code>paid</code>, <code>captured</code>, <code>success</code>, <code>succeeded</code>, <code>verified</code>; refunds use <code>refunded</code>.
            <br>
            <small>
                Mart: <code>/api/v1/mart/payments/webhook</code> |
                E-Commerce: <code>/api/v1/ecommerce/payments/webhook</code> |
                Medical: <code>/api/v1/medical/payments/webhook</code> |
                Services: <code>/api/v1/services/payments/webhook</code> |
                Hotels: <code>/api/v1/hotels/payments/webhook</code> |
                Restaurants: <code>/api/v1/restaurants/payments/webhook</code>
            </small>
        </div>
        <div class="row">
            <div><label>Bank Transfer Title</label><input name="bank_transfer_title" value="<?= htmlspecialchars($settings['bank_transfer_title'] ?? 'Bank Transfer') ?>"></div>
            <div><label>Bank Transfer Description</label><input name="bank_transfer_description" value="<?= htmlspecialchars($settings['bank_transfer_description'] ?? '') ?>"></div>
        </div>
        <label>Bank / UPI Instructions</label>
        <textarea name="bank_transfer_instructions"><?= htmlspecialchars($settings['bank_transfer_instructions'] ?? '') ?></textarea>
        <h2>Operations &amp; Hardening</h2>
        <div class="row">
            <div><label>API Write Limit Per Minute</label><input name="api_rate_limit_per_minute" type="number" min="10" max="600" value="<?= htmlspecialchars($settings['api_rate_limit_per_minute'] ?? '60') ?>"></div>
            <div><label>Panel Session Timeout Minutes</label><input name="panel_session_timeout_minutes" type="number" min="15" max="1440" value="<?= htmlspecialchars($settings['panel_session_timeout_minutes'] ?? '120') ?>"></div>
            <div><label>Support Poll Interval Seconds</label><input name="support_poll_interval_seconds" type="number" min="5" max="120" value="<?= htmlspecialchars($settings['support_poll_interval_seconds'] ?? '15') ?>"></div>
            <div><label>Support Realtime Provider</label><input name="support_realtime_provider" value="<?= htmlspecialchars($settings['support_realtime_provider'] ?? 'polling') ?>" placeholder="polling"></div>
        </div>
        <label>About Us</label>
        <textarea name="about_us"><?= htmlspecialchars($settings['about_us'] ?? '') ?></textarea>
        <label>Terms &amp; Conditions</label>
        <textarea name="terms_conditions"><?= htmlspecialchars($settings['terms_conditions'] ?? '') ?></textarea>
        <label>Privacy Policy</label>
        <textarea name="privacy_policy"><?= htmlspecialchars($settings['privacy_policy'] ?? '') ?></textarea>
        <label>Refund Policy</label>
        <textarea name="refund_policy"><?= htmlspecialchars($settings['refund_policy'] ?? '') ?></textarea>
        <label>Shipping Policy</label>
        <textarea name="shipping_policy"><?= htmlspecialchars($settings['shipping_policy'] ?? '') ?></textarea>
        <label>Support Content</label>
        <textarea name="support_content"><?= htmlspecialchars($settings['support_content'] ?? '') ?></textarea>
        <h2>Public Website Contact</h2>
        <div class="row">
            <div><label>Business Name</label><input name="public_business_name" value="<?= htmlspecialchars($settings['public_business_name'] ?? 'City Solutions') ?>"></div>
            <div><label>Legal Entity</label><input name="public_legal_entity" value="<?= htmlspecialchars($settings['public_legal_entity'] ?? 'City Solutions') ?>"></div>
            <div><label>Support Email</label><input name="public_support_email" type="email" value="<?= htmlspecialchars($settings['public_support_email'] ?? 'support@citysolutions.in') ?>"></div>
        </div>
        <div class="row">
            <div><label>Support Phone</label><input name="public_support_phone" value="<?= htmlspecialchars($settings['public_support_phone'] ?? '') ?>"></div>
            <div style="grid-column:span 2"><label>Business Address</label><input name="public_business_address" value="<?= htmlspecialchars($settings['public_business_address'] ?? 'Lucknow, India') ?>"></div>
        </div>
        <h2>Medical Compliance</h2>
        <label>Medical Terms</label>
        <textarea name="medical_terms_conditions"><?= htmlspecialchars($settings['medical_terms_conditions'] ?? '') ?></textarea>
        <label>Medical Privacy</label>
        <textarea name="medical_privacy_policy"><?= htmlspecialchars($settings['medical_privacy_policy'] ?? '') ?></textarea>
        <label>Medical Refund Policy</label>
        <textarea name="medical_refund_policy"><?= htmlspecialchars($settings['medical_refund_policy'] ?? '') ?></textarea>
        <label>Prescription Policy</label>
        <textarea name="medical_prescription_policy"><?= htmlspecialchars($settings['medical_prescription_policy'] ?? '') ?></textarea>
        <h2>Hotel Cancellation Policy</h2>
        <div class="row">
            <div><label>Free Cancellation Hours Before Check-in</label><input name="hotel_cancellation_free_hours" type="number" min="0" value="<?= htmlspecialchars($settings['hotel_cancellation_free_hours'] ?? '24') ?>"></div>
            <div><label>Late Cancellation Refund %</label><input name="hotel_late_cancellation_refund_percent" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars($settings['hotel_late_cancellation_refund_percent'] ?? '50') ?>"></div>
            <div><label>Hotel Commission %</label><input name="hotel_owner_commission_percent" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars($settings['hotel_owner_commission_percent'] ?? '10') ?>"></div>
        </div>
        <div class="row">
            <div><label>Maintenance Mode</label><div><input style="width:auto;" name="maintenance_mode" type="checkbox" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>> Enabled</div></div>
            <div><label>Latest App Version</label><input name="latest_app_version" value="<?= htmlspecialchars($settings['latest_app_version'] ?? '') ?>" placeholder="1.0.0"></div>
            <div><label>Force Update Version</label><input name="force_update_version" value="<?= htmlspecialchars($settings['force_update_version'] ?? '') ?>" placeholder="1.0.0"></div>
        </div>
        <label>Maintenance Message</label>
        <textarea name="maintenance_message"><?= htmlspecialchars($settings['maintenance_message'] ?? '') ?></textarea>
        <h2>Firebase Push</h2>
        <div class="row">
            <div><label>Push Notifications</label><div><input style="width:auto;" name="firebase_push_enabled" type="checkbox" value="1" <?= ($settings['firebase_push_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Enabled</div></div>
            <div><label>Firebase Customer Auth</label><div><input style="width:auto;" name="firebase_auth_enabled" type="checkbox" value="1" <?= ($settings['firebase_auth_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Enabled</div></div>
            <div><label>Firebase API Key</label><input name="firebase_api_key" value="<?= htmlspecialchars($settings['firebase_api_key'] ?? '') ?>"></div>
            <div><label>Firebase Auth Domain</label><input name="firebase_auth_domain" value="<?= htmlspecialchars($settings['firebase_auth_domain'] ?? '') ?>"></div>
        </div>
        <div class="row">
            <div><label>Phone OTP Login</label><div><input style="width:auto;" name="firebase_phone_auth_enabled" type="checkbox" value="1" <?= ($settings['firebase_phone_auth_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Enabled</div></div>
            <div><label>Google Login</label><div><input style="width:auto;" name="firebase_google_auth_enabled" type="checkbox" value="1" <?= ($settings['firebase_google_auth_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Enabled</div></div>
        </div>
        <div class="row">
            <div><label>Firebase Project ID</label><input name="firebase_project_id" value="<?= htmlspecialchars($settings['firebase_project_id'] ?? '') ?>"></div>
            <div><label>Firebase Sender ID</label><input name="firebase_sender_id" value="<?= htmlspecialchars($settings['firebase_sender_id'] ?? '') ?>"></div>
            <div><label>Firebase App ID</label><input name="firebase_app_id" value="<?= htmlspecialchars($settings['firebase_app_id'] ?? '') ?>"></div>
        </div>
        <div class="row">
            <div><label>Storage Bucket</label><input name="firebase_storage_bucket" value="<?= htmlspecialchars($settings['firebase_storage_bucket'] ?? '') ?>"></div>
            <div><label>Measurement ID</label><input name="firebase_measurement_id" value="<?= htmlspecialchars($settings['firebase_measurement_id'] ?? '') ?>"></div>
            <div><label>Config Status</label><input readonly value="<?= !empty($settings['firebase_service_account_json']) ? 'Service account configured' : 'Paste service account JSON for HTTP v1 push' ?>"></div>
        </div>
        <label>Firebase Server Key</label><input type="password" name="firebase_server_key" value="" placeholder="<?= !empty($settings['firebase_server_key']) ? 'Configured - enter new value to replace' : 'Enter Firebase server key' ?>">
        <label>Firebase Service Account JSON</label>
        <textarea name="firebase_service_account_json" placeholder="<?= !empty($settings['firebase_service_account_json']) ? 'Configured - paste valid new JSON to replace' : 'Paste the Firebase service account JSON downloaded from Project Settings > Service Accounts.' ?>"></textarea>
        <p style="color:var(--muted);margin-top:-4px;">Same pattern as OnlineKiryana: keep Firebase web/app config fields for reference, and use the service-account JSON/server key for actual push sending. Use the test button below before enabling push in production.</p>
        <label>Firebase Test Device Token</label><input name="firebase_test_device_token" value="<?= htmlspecialchars($settings['firebase_test_device_token'] ?? '') ?>">
        <h2>Floating App Ad</h2>
        <div class="row">
            <div><label>Floating Ad</label><div><input style="width:auto;" name="floating_ad_enabled" type="checkbox" value="1" <?= ($settings['floating_ad_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Enabled</div></div>
            <div><label>Ad ID / Version</label><input name="floating_ad_id" value="<?= htmlspecialchars($settings['floating_ad_id'] ?? 'default') ?>" placeholder="summer-sale-1"></div>
            <div><label>CTA Text</label><input name="floating_ad_cta_text" value="<?= htmlspecialchars($settings['floating_ad_cta_text'] ?? '') ?>" placeholder="Shop Now"></div>
        </div>
        <div class="row">
            <div><label>Title</label><input name="floating_ad_title" value="<?= htmlspecialchars($settings['floating_ad_title'] ?? '') ?>" placeholder="Special Offer"></div>
            <div><label>Image URL</label><input name="floating_ad_image_url" value="<?= htmlspecialchars($settings['floating_ad_image_url'] ?? '') ?>" placeholder="https://..."></div>
            <div><label>Link URL / Deep Link</label><input name="floating_ad_link_url" value="<?= htmlspecialchars($settings['floating_ad_link_url'] ?? '') ?>" placeholder="https://... or app link"></div>
        </div>
        <label>Video URL</label>
        <input name="floating_ad_video_url" value="<?= htmlspecialchars($settings['floating_ad_video_url'] ?? '') ?>" placeholder="https://.../ad.mp4. If set, video is shown before image.">
        <label>Message</label>
        <textarea name="floating_ad_message" placeholder="Short ad text shown in the floating window."><?= htmlspecialchars($settings['floating_ad_message'] ?? '') ?></textarea>
        <div style="height:12px;"></div><button>Save Settings</button>
    </form>
</section>

<section class="card">
    <h2>Module Specific Settings</h2>
    <p style="color:var(--muted);">These override global settings per module. Use these when Mart, E-Commerce, Medical, Services, Hotels, Real Estate, or Restaurants need different rules.</p>
    <form method="post" action="/admin/settings">
        <input type="hidden" name="settings_scope" value="modules">
        <?php foreach ($moduleSettings as $moduleKey => $moduleLabel): ?>
            <h2><?= htmlspecialchars($moduleLabel) ?></h2>
            <div class="row">
                <div><label>App Name</label><input name="<?= htmlspecialchars($moduleKey) ?>_app_name" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'app_name', $moduleDefaultName($moduleKey))) ?>"></div>
                <div><label>Currency</label><input name="<?= htmlspecialchars($moduleKey) ?>_currency" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'currency', 'INR')) ?>"></div>
                <div><label>Currency Symbol</label><input name="<?= htmlspecialchars($moduleKey) ?>_currency_symbol" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'currency_symbol', '₹')) ?>"></div>
            </div>
            <div class="row">
                <div><label>Minimum Order / Booking Amount</label><input name="<?= htmlspecialchars($moduleKey) ?>_minimum_order_amount" type="number" step="0.01" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'minimum_order_amount', '0')) ?>"></div>
                <div><label>Delivery / Service Charge</label><input name="<?= htmlspecialchars($moduleKey) ?>_delivery_charge" type="number" step="0.01" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'delivery_charge', '0')) ?>"></div>
                <div><label>Supported Locales</label><input name="<?= htmlspecialchars($moduleKey) ?>_supported_locales" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'supported_locales', 'en,hi')) ?>"></div>
            </div>
            <div class="row">
                <div><label>Cash Payment</label><div><input style="width:auto;" name="<?= htmlspecialchars($moduleKey) ?>_cod_enabled" type="checkbox" value="1" <?= $settingValue($settings, $moduleKey, 'cod_enabled', '1') === '1' ? 'checked' : '' ?>> Enabled</div></div>
                <div><label>Online Payment</label><div><input style="width:auto;" name="<?= htmlspecialchars($moduleKey) ?>_online_payment_enabled" type="checkbox" value="1" <?= $settingValue($settings, $moduleKey, 'online_payment_enabled', '1') === '1' ? 'checked' : '' ?>> Enabled</div></div>
                <div><label>Manual / Bank Transfer</label><div><input style="width:auto;" name="<?= htmlspecialchars($moduleKey) ?>_manual_payment_enabled" type="checkbox" value="1" <?= $settingValue($settings, $moduleKey, 'manual_payment_enabled', '1') === '1' ? 'checked' : '' ?>> Enabled</div></div>
            </div>
            <div class="row">
                <div><label>Wallet Payment</label><div><input style="width:auto;" name="<?= htmlspecialchars($moduleKey) ?>_wallet_payment_enabled" type="checkbox" value="1" <?= $settingValue($settings, $moduleKey, 'wallet_payment_enabled', '1') === '1' ? 'checked' : '' ?>> Enabled</div></div>
                <div><label>Online Gateway</label><?php $gatewaySelect($moduleKey . '_online_payment_gateway', $settingValue($settings, $moduleKey, 'online_payment_gateway', 'manual')); ?></div>
                <div><label>Public Key</label><input name="<?= htmlspecialchars($moduleKey) ?>_online_payment_public_key" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'online_payment_public_key')) ?>"></div>
            </div>
            <div class="row">
                <div><label>Secret Key</label><input type="password" name="<?= htmlspecialchars($moduleKey) ?>_online_payment_secret_key" value="" placeholder="<?= $settingValue($settings, $moduleKey, 'online_payment_secret_key') !== '' ? 'Configured - enter new value to replace' : 'Enter secret key' ?>"></div>
                <div><label>Merchant / Account ID</label><input name="<?= htmlspecialchars($moduleKey) ?>_online_payment_merchant_id" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'online_payment_merchant_id')) ?>"></div>
                <div><label>Environment</label><select name="<?= htmlspecialchars($moduleKey) ?>_online_payment_environment"><option value="test" <?= $settingValue($settings, $moduleKey, 'online_payment_environment', 'test') === 'test' ? 'selected' : '' ?>>Test</option><option value="live" <?= $settingValue($settings, $moduleKey, 'online_payment_environment', 'test') === 'live' ? 'selected' : '' ?>>Live</option></select></div>
            </div>
            <label>Webhook Secret</label>
            <input type="password" name="<?= htmlspecialchars($moduleKey) ?>_online_payment_webhook_secret" value="" placeholder="<?= $settingValue($settings, $moduleKey, 'online_payment_webhook_secret') !== '' ? 'Configured - enter new value to replace' : 'Enter webhook secret' ?>">
            <?php if ($webhookPath($moduleKey) !== ''): ?>
                <p style="color:var(--muted);margin-top:-4px;">Webhook endpoint: <code><?= htmlspecialchars($webhookPath($moduleKey)) ?></code>. Sign raw JSON with HMAC-SHA256 using this module secret.</p>
            <?php else: ?>
                <p style="color:var(--muted);margin-top:-4px;">No payment webhook is exposed for this module yet.</p>
            <?php endif; ?>
            <div class="row">
                <div><label>Capture URL</label><input name="<?= htmlspecialchars($moduleKey) ?>_online_payment_capture_url" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'online_payment_capture_url')) ?>"></div>
                <div><label>Status URL</label><input name="<?= htmlspecialchars($moduleKey) ?>_online_payment_status_url" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'online_payment_status_url')) ?>"></div>
                <div><label>Refund URL</label><input name="<?= htmlspecialchars($moduleKey) ?>_online_payment_refund_url" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'online_payment_refund_url')) ?>"></div>
            </div>
            <label>Gateway Auth Header</label>
            <input type="password" name="<?= htmlspecialchars($moduleKey) ?>_online_payment_auth_header" value="" placeholder="<?= $settingValue($settings, $moduleKey, 'online_payment_auth_header') !== '' ? 'Configured - enter new value to replace' : 'Authorization: Bearer secret-token' ?>">
            <label>Online Payment Instructions</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_online_payment_instructions"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'online_payment_instructions')) ?></textarea>
            <label>Bank / UPI Instructions</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_bank_transfer_instructions"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'bank_transfer_instructions')) ?></textarea>
            <div class="row">
                <div><label>Maintenance Mode</label><div><input style="width:auto;" name="<?= htmlspecialchars($moduleKey) ?>_maintenance_mode" type="checkbox" value="1" <?= $settingValue($settings, $moduleKey, 'maintenance_mode', '0') === '1' ? 'checked' : '' ?>> Enabled</div></div>
                <div><label>Latest App Version</label><input name="<?= htmlspecialchars($moduleKey) ?>_latest_app_version" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'latest_app_version')) ?>"></div>
                <div><label>Force Update Version</label><input name="<?= htmlspecialchars($moduleKey) ?>_force_update_version" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'force_update_version')) ?>"></div>
            </div>
            <label>Maintenance Message</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_maintenance_message"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'maintenance_message')) ?></textarea>
            <div class="row">
                <div><label>Firebase Push</label><div><input style="width:auto;" name="<?= htmlspecialchars($moduleKey) ?>_firebase_push_enabled" type="checkbox" value="1" <?= $settingValue($settings, $moduleKey, 'firebase_push_enabled', '0') === '1' ? 'checked' : '' ?>> Enabled</div></div>
                <div><label>Firebase Project ID</label><input name="<?= htmlspecialchars($moduleKey) ?>_firebase_project_id" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'firebase_project_id')) ?>"></div>
                <div><label>Firebase Sender ID</label><input name="<?= htmlspecialchars($moduleKey) ?>_firebase_sender_id" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'firebase_sender_id')) ?>"></div>
            </div>
            <label>Firebase Server Key</label><input type="password" name="<?= htmlspecialchars($moduleKey) ?>_firebase_server_key" value="" placeholder="<?= $settingValue($settings, $moduleKey, 'firebase_server_key') !== '' ? 'Configured - enter new value to replace' : 'Enter Firebase server key' ?>">
            <label>Firebase Service Account JSON</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_firebase_service_account_json" placeholder="<?= $settingValue($settings, $moduleKey, 'firebase_service_account_json') !== '' ? 'Configured - paste new JSON to replace' : 'Paste service account JSON' ?>"></textarea>
            <label>About / CMS Content</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_about_us"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'about_us')) ?></textarea>
            <label>Terms &amp; Conditions</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_terms_conditions"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'terms_conditions')) ?></textarea>
            <label>Privacy Policy</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_privacy_policy"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'privacy_policy')) ?></textarea>
            <label>Refund Policy</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_refund_policy"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'refund_policy')) ?></textarea>
            <label>Shipping / Service Policy</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_shipping_policy"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'shipping_policy')) ?></textarea>
            <label>Support Content</label>
            <textarea name="<?= htmlspecialchars($moduleKey) ?>_support_content"><?= htmlspecialchars($settingValue($settings, $moduleKey, 'support_content')) ?></textarea>
            <input type="hidden" name="<?= htmlspecialchars($moduleKey) ?>_online_payment_title" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'online_payment_title', 'Online Payment')) ?>">
            <input type="hidden" name="<?= htmlspecialchars($moduleKey) ?>_online_payment_description" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'online_payment_description')) ?>">
            <input type="hidden" name="<?= htmlspecialchars($moduleKey) ?>_bank_transfer_title" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'bank_transfer_title', 'Bank Transfer')) ?>">
            <input type="hidden" name="<?= htmlspecialchars($moduleKey) ?>_bank_transfer_description" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'bank_transfer_description')) ?>">
            <input type="hidden" name="<?= htmlspecialchars($moduleKey) ?>_app_locale" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'app_locale', 'en')) ?>">
            <input type="hidden" name="<?= htmlspecialchars($moduleKey) ?>_firebase_test_device_token" value="<?= htmlspecialchars($settingValue($settings, $moduleKey, 'firebase_test_device_token')) ?>">
        <?php endforeach; ?>
        <div style="height:12px;"></div><button>Save Module Settings</button>
    </form>
</section>

<section class="card">
    <h2>Test Firebase Push</h2>
    <?php if (!empty($pushResult)): ?>
        <div class="<?= !empty($pushResult['ok']) ? 'pill' : 'error' ?>"><?= htmlspecialchars(($pushModule ?? 'global') . ': ' . ($pushResult['message'] ?? '')) ?></div>
        <?php if (!empty($pushResult['body'])): ?>
            <label>Firebase Response</label>
            <textarea readonly><?= htmlspecialchars((string) $pushResult['body']) ?></textarea>
        <?php endif; ?>
    <?php endif; ?>
    <form method="post" action="/admin/settings/firebase-test">
        <?php
        $selectedPushModule = (string) ($pushModule ?? 'global');
        $selectedPushTokenKey = $selectedPushModule === 'global' ? 'firebase_test_device_token' : $selectedPushModule . '_firebase_test_device_token';
        ?>
        <label>Settings Scope</label>
        <select name="module_key">
            <?php foreach (['global' => 'Global', 'mart' => 'Mart', 'ecommerce' => 'E-Commerce', 'medical' => 'Medical', 'services' => 'Services', 'hotel' => 'Hotels', 'real_estate' => 'Real Estate', 'restaurant' => 'Restaurants'] as $moduleKey => $moduleLabel): ?>
                <option value="<?= htmlspecialchars($moduleKey) ?>" <?= $selectedPushModule === $moduleKey ? 'selected' : '' ?>><?= htmlspecialchars($moduleLabel) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Device Token</label><input name="firebase_test_device_token" value="<?= htmlspecialchars($settings[$selectedPushTokenKey] ?? '') ?>" required>
        <div class="row">
            <div><label>Title</label><input name="title" value="City Solutions test"></div>
            <div><label>Message</label><input name="message" value="Firebase test notification from admin panel."></div>
        </div>
        <div style="height:12px;"></div><button>Send Test Push</button>
    </form>
    <p style="color:var(--muted);">Select Medical or Hotels to test that module's Firebase credentials, not only the global fallback.</p>
</section>
