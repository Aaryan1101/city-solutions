<main class="legal">
  <span class="pill">Terms</span>
  <h1>Terms of Use</h1>
  <p>Effective date: <?= date('F j, Y') ?>. These Terms govern use of the City Solutions app, website and operational panels. By using the platform, users, vendors, providers, hotel owners, restaurant operators, agents and panel users agree to these Terms and all module-specific policies shown at checkout, booking, account, support or admin screens.</p>

  <h2>Platform role</h2>
  <p>City Solutions is a multi-module technology platform for local commerce, mart orders, medical orders, services, hotels, restaurant reservations, real estate activity, support and complaints. Some products and services are supplied by independent vendors, service providers, licensed pharmacy partners, hotels, restaurants, agents or other third parties. City Solutions may operate technology, routing, support, admin review, wallet/refund flows and quality controls, but fulfillment responsibility may remain with the relevant partner.</p>

  <h2>User responsibilities</h2>
  <ul>
    <li>Provide accurate name, phone, email, address, zone, booking, prescription and payment/reference information.</li>
    <li>Use the platform lawfully and only for legitimate personal or business needs.</li>
    <li>Do not misuse refunds, wallets, complaints, coupons, reviews, support, prescriptions, bookings or partner communication tools.</li>
    <li>Do not upload false, illegal, abusive, harmful, infringing, misleading or unauthorized content.</li>
    <li>Use medicines only under appropriate medical advice and do not attempt to purchase restricted or habit-forming medicines for misuse.</li>
  </ul>

  <h2>Orders, bookings and availability</h2>
  <p>All orders and bookings are subject to stock, zone coverage, partner availability, price verification, prescription review where applicable, payment verification and operational feasibility. Prices, offers, taxes, charges, delivery times, slots and availability may change before confirmation. We may reject, cancel, pause or modify an order/booking when required for safety, legal compliance, stock, fraud prevention, prescription failure, partner unavailability or operational limitations.</p>

  <h2>Payments, wallet and refunds</h2>
  <p>Payments may be collected through cash, wallet, manual transfer or configured online gateways. A payment shown as successful in the app may still require gateway, bank, wallet or admin reconciliation. Wallet credits may be used according to configured rules and may be reversed if credited by mistake, fraud, chargeback or policy violation. Refunds are governed by the Refund & Cancellation Policy and module-specific rules.</p>

  <h2>Medical module</h2>
  <p>Prescription-required medicines are not ordinary products. Orders may require a valid prescription, pharmacist/admin review, age confirmation, quantity limits and legal record keeping. Restricted, habit-forming, narcotic, psychotropic or prohibited medicines may be blocked or rejected. City Solutions does not provide medical diagnosis and does not replace consultation with a registered medical practitioner.</p>

  <h2>Partner responsibilities</h2>
  <p>Partners must maintain accurate listings, legal permissions, licenses, stock, pricing, taxes, service availability, staff conduct, hygiene/safety standards, cancellation rules, refund rules, customer support obligations and settlement records. Partners are responsible for compliance with laws applicable to their business category.</p>

  <h2>Reviews, complaints and communication</h2>
  <p>Users may submit ratings, reviews, support tickets and complaints. Content must be truthful, relevant and lawful. We may moderate, remove or restrict content that is abusive, fraudulent, defamatory, irrelevant, illegal, unsafe or violates platform rules.</p>

  <h2>Suspension and termination</h2>
  <p>We may suspend or restrict accounts, partners, orders, bookings, wallet activity, panels or platform access for suspected fraud, abuse, illegal activity, payment risk, security risk, repeated policy violations or legal compliance reasons.</p>

  <h2>Limitation of liability</h2>
  <p>To the maximum extent permitted by law, City Solutions is not liable for indirect, incidental, punitive, special or consequential losses, including loss of profit, data, reputation, opportunity or business interruption. Nothing in these Terms excludes liability that cannot legally be excluded.</p>

  <h2>Governing law and disputes</h2>
  <p>These Terms are governed by the laws of India unless another mandatory law applies. Users should first raise disputes through support or complaint channels with complete order/booking details. Formal disputes are subject to the competent courts and authorities for the operating location of <?= htmlspecialchars($publicLegalEntity ?? 'City Solutions', ENT_QUOTES) ?>, unless mandatory law provides otherwise.</p>

  <h2>Contact</h2>
  <p>For legal, support or grievance requests, contact <?= htmlspecialchars($publicSupportEmail ?? 'support@citysolutions.in', ENT_QUOTES) ?><?= !empty($publicSupportPhone) ? ' or ' . htmlspecialchars($publicSupportPhone, ENT_QUOTES) : '' ?>.</p>
</main>
