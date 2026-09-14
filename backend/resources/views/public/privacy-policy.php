<main class="legal">
  <span class="pill">Privacy</span>
  <h1>Privacy Policy</h1>
  <p>Effective date: <?= date('F j, Y') ?>. This Privacy Policy explains how <?= htmlspecialchars($publicBusinessName ?? 'City Solutions', ENT_QUOTES) ?> collects, uses, stores, shares and protects information across the City Solutions mobile app, website, customer support channels, vendor/provider panels, hotel and restaurant tools, real estate tools and administrator systems.</p>

  <h2>Who operates the platform</h2>
  <p>The platform is operated by <?= htmlspecialchars($publicLegalEntity ?? 'City Solutions', ENT_QUOTES) ?>. Contact: <?= htmlspecialchars($publicSupportEmail ?? 'support@citysolutions.in', ENT_QUOTES) ?><?= !empty($publicSupportPhone) ? ', ' . htmlspecialchars($publicSupportPhone, ENT_QUOTES) : '' ?>. Address: <?= htmlspecialchars($publicBusinessAddress ?? 'Lucknow, India', ENT_QUOTES) ?>.</p>

  <h2>Information we collect</h2>
  <ul>
    <li>Account and profile information such as name, phone number, email address, saved addresses, zone, language preference and login/session identifiers.</li>
    <li>Order, booking, cart, wishlist, refund, wallet, withdrawal, invoice, payment reference, support, chat and complaint records.</li>
    <li>Location information selected by the user or detected through map/location tools to determine zone availability, delivery/service coverage and address accuracy.</li>
    <li>Medical and prescription-related documents only when required for prescription review, medicine fulfillment, customer support, dispute handling or legally required records.</li>
    <li>Partner information for vendors, service providers, hotel owners, restaurant operators, real estate agents and panel users, including business details, uploaded media, inventory, booking availability and settlement records.</li>
    <li>Device and technical information such as app version, device token for notifications, request logs, IP address, error logs and security/rate-limit signals.</li>
  </ul>

  <h2>How we use information</h2>
  <p>We use information to create accounts, process orders and bookings, verify prescriptions, assign zones, route support and complaints, display relevant inventory, send notifications, manage refunds and wallet credits, maintain financial and operational ledgers, prevent fraud or abuse, secure the platform and comply with legal obligations.</p>

  <h2>Legal basis and consent</h2>
  <p>Where required, we ask for permission before accessing sensitive information such as location, camera, file uploads or notifications. Users may deny permissions, but some features may not work without them. Prescription uploads and medical information should be submitted only when needed for a medicine order or pharmacy review.</p>

  <h2>Sharing and disclosure</h2>
  <p>Information may be shared with the relevant vendor, provider, hotel, restaurant, real estate agent, delivery partner, payment provider, notification provider, hosting provider, support team, auditors, professional advisers, regulators or legal authorities when necessary for platform operation, dispute resolution, safety, fraud prevention or legal compliance. We do not sell personal or sensitive user data.</p>

  <h2>Payments and wallets</h2>
  <p>Payment information may be processed by configured payment gateways or recorded as manual payment references. Wallet balances, refunds, withdrawals and transaction ledgers are retained for audit, fraud prevention and accounting purposes.</p>

  <h2>Medical privacy</h2>
  <p>Prescription documents and health-related order details are sensitive. They should be accessed only by authorized pharmacy/admin personnel for review, fulfillment, support, dispute handling and record keeping. Users must not upload false prescriptions or documents belonging to another person without lawful authority.</p>

  <h2>Security</h2>
  <p>We use access controls, role-based panels, HTTPS hosting, validation, rate limiting and operational safeguards to reduce unauthorized access. No system is completely risk-free, so users and partners should keep credentials secure and immediately report suspicious activity.</p>

  <h2>Retention</h2>
  <p>We retain account, order, booking, invoice, payment, prescription, support, complaint, audit and settlement records as long as needed for service delivery, legal compliance, accounting, dispute handling, fraud prevention and legitimate business operations. Some records may be retained even after account deletion where law or legitimate compliance needs require it.</p>

  <h2>User choices</h2>
  <ul>
    <li>Users can update profile and address information in the app where available.</li>
    <li>Users can disable app permissions from device settings.</li>
    <li>Users can request correction, export or deletion of eligible personal information by contacting support.</li>
    <li>Users can request account deletion through the app/account section where available or through the account deletion page.</li>
  </ul>

  <h2>Children</h2>
  <p>The platform is intended for users who can lawfully enter into transactions. Minors should use the platform only with parental or guardian supervision. Medical products must not be ordered for misuse or without appropriate medical guidance.</p>

  <h2>Changes to this policy</h2>
  <p>We may update this policy to reflect changes in law, platform features, modules or business operations. Continued use after an update means the updated policy applies.</p>

  <h2>Privacy contact</h2>
  <p>For privacy, data deletion, account deletion, complaint or safety requests, contact <?= htmlspecialchars($publicSupportEmail ?? 'support@citysolutions.in', ENT_QUOTES) ?><?= !empty($publicSupportPhone) ? ' or ' . htmlspecialchars($publicSupportPhone, ENT_QUOTES) : '' ?>.</p>
</main>
