<main class="legal">
  <span class="pill">Account</span>
  <h1>Account Deletion Request</h1>
  <p>Users may request deletion of their City Solutions account and eligible personal information. Some records may be retained where required for legal compliance, fraud prevention, accounting, dispute handling, prescription records, invoices, wallet ledgers, payment records, tax records or safety obligations.</p>

  <h2>How to request deletion</h2>
  <ul>
    <li>Open the City Solutions app and go to Account / Support where account deletion is available, or</li>
    <li>Email <?= htmlspecialchars($publicSupportEmail ?? 'support@citysolutions.in', ENT_QUOTES) ?> with the subject “Account Deletion Request”.</li>
  </ul>

  <h2>Information to include</h2>
  <ul>
    <li>Registered phone number and email address.</li>
    <li>Full name used in the account.</li>
    <li>Recent order/booking number if available, so we can verify ownership.</li>
    <li>A clear statement that you want your account deleted.</li>
  </ul>

  <h2>What may be deleted</h2>
  <p>Eligible profile information, saved addresses, notification tokens, preferences and inactive personal data may be deleted or anonymized after verification.</p>

  <h2>What may be retained</h2>
  <p>Order records, booking records, invoices, payment transactions, wallet ledgers, refund records, prescription review records, complaints, support logs, fraud/security logs and partner settlement records may be retained where required by law, tax/accounting obligations, dispute resolution, fraud prevention, safety or legitimate business compliance.</p>

  <h2>Processing time</h2>
  <p>We aim to acknowledge deletion requests within a reasonable time after verification. Completion time may vary depending on pending orders, refunds, wallet balances, disputes, legal holds or verification issues.</p>

  <h2>After deletion</h2>
  <p>Once deleted, the account may no longer be recoverable. Active orders, bookings, refunds, wallet withdrawals or complaints should be completed or resolved before deletion where possible.</p>

  <h2>Contact</h2>
  <p>Send account deletion and data requests to <?= htmlspecialchars($publicSupportEmail ?? 'support@citysolutions.in', ENT_QUOTES) ?><?= !empty($publicSupportPhone) ? ' or call ' . htmlspecialchars($publicSupportPhone, ENT_QUOTES) : '' ?>.</p>
</main>
