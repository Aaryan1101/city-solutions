<?php $joinEmail = $publicSupportEmail ?? 'support@citysolutions.in'; ?>
<main class="legal" id="join">
  <span class="pill">Join the community</span>
  <h1>Join City Solutions Community</h1>
  <p>Tell us how you want to participate. We use these requests to plan area launches, onboard trusted local businesses, and improve the services available in the app.</p>
  <h2>Customers</h2>
  <p>Join to receive launch updates, offers, local availability news, and early access updates for your area. For order, booking, refund or complaint issues, use the app support section and include the order or booking number.</p>
  <h2>Local businesses</h2>
  <p>Stores, pharmacies, restaurants, hotels, service professionals, and property partners can request onboarding. Share your business type, operating area, contact details, and what you want to offer through City Solutions.</p>
  <h2>What to send</h2>
  <ul>
    <li>Your name and city or local area.</li>
    <li>Whether you are joining as a customer, business, service professional, hotel, restaurant, property partner, or community contributor.</li>
    <li>Your contact number and preferred time to talk.</li>
    <li>For business requests, include business name, category, address, and service area.</li>
  </ul>
  <div class="notice">We do not ask for payment, banking information, passwords, or sensitive documents through the public website. Formal verification happens only through approved onboarding channels.</div>
  <div class="legal-links">
    <a class="btn btn-dark" href="mailto:<?= htmlspecialchars($joinEmail, ENT_QUOTES) ?>?subject=<?= rawurlencode('Join City Solutions Community') ?>">Email to join</a>
    <a class="btn btn-outline" href="/trust-safety">Read trust & safety</a>
  </div>
  <h2>Business contact</h2>
  <p>
    Email: <?= htmlspecialchars($joinEmail, ENT_QUOTES) ?><br>
    <?php if (!empty($publicSupportPhone)): ?>
      Phone: <?= htmlspecialchars($publicSupportPhone, ENT_QUOTES) ?><br>
    <?php endif; ?>
    Location: <?= nl2br(htmlspecialchars($publicBusinessAddress ?? 'Lucknow, India', ENT_QUOTES)) ?>
  </p>
</main>
