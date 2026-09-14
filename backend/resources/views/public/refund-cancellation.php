<main class="legal">
  <span class="pill">Refunds</span>
  <h1>Refund & Cancellation Policy</h1>
  <p>Effective date: <?= date('F j, Y') ?>. This policy explains cancellation, refund, wallet credit and dispute handling across City Solutions modules. Module-specific rules shown in the app, checkout, booking flow, product page or partner policy may also apply.</p>

  <h2>General principles</h2>
  <ul>
    <li>Refunds are reviewed against order/booking status, payment status, product/service type, partner confirmation, customer evidence and applicable law.</li>
    <li>Cancellation is usually easier before processing, dispatch, check-in, appointment start or provider assignment.</li>
    <li>Refund approval does not always mean instant bank settlement; gateway, bank and wallet timelines may apply.</li>
    <li>Fraud, misuse, false claims, repeated abuse or policy violations may result in rejection, wallet reversal or account restrictions.</li>
  </ul>

  <h2>Mart, e-commerce and medical orders</h2>
  <p>Orders may be cancelled before processing where enabled. After processing, dispatch or delivery begins, cancellation depends on product type, partner policy, delivery status and applicable law. Damaged, missing, wrong or expired items should be reported promptly with photos/video where relevant.</p>

  <h2>Medical orders</h2>
  <p>Prescription medicines, cold-chain products, opened packages, hygiene-sensitive products, regulated medicines and correctly supplied medicines may be non-returnable except where required by law or where the wrong, damaged, expired or unsafe item was supplied. Prescription rejection may lead to cancellation or refund according to payment status and admin review.</p>

  <h2>Services</h2>
  <p>Service bookings may be cancelled or rescheduled according to provider availability, booking status, cancellation window, materials purchased, travel cost, work already started and admin/provider review. No-shows or late cancellations may be partially or fully non-refundable.</p>

  <h2>Hotels</h2>
  <p>Hotel cancellation depends on check-in date, free cancellation window, rate plan, no-show status, room policy, taxes and hotel-owner confirmation. Late cancellations may receive partial refund only. Special-rate or non-refundable bookings may not be refundable unless required by law or confirmed by the hotel/admin.</p>

  <h2>Restaurants</h2>
  <p>Table reservations and waitlist requests may be cancelled according to restaurant policy. Booking fees, deposits, no-show charges or late cancellations may be partially or fully non-refundable if configured by the restaurant/admin.</p>

  <h2>Real estate visits</h2>
  <p>Site visit requests are scheduling activities and normally do not create a product refund unless a paid service, deposit or booking fee is configured. Rescheduling depends on agent/project availability.</p>

  <h2>Wallet credits and original payment refunds</h2>
  <p>Approved refunds may be credited to wallet or sent to the original payment method depending on payment configuration, gateway support, admin decision, fraud checks and partner policy. Wallet credits may be usable only inside the City Solutions platform unless withdrawal is enabled and approved.</p>

  <h2>How to request a refund or cancellation</h2>
  <p>Use the order/booking details screen, refund center, support ticket or complaint flow. Provide order/booking number, reason, photos/video where relevant and complete contact details. Admin review may require additional information.</p>

  <h2>Processing timelines</h2>
  <p>Admin review usually starts after the request is submitted with complete information. Gateway or bank refunds may take additional business days after approval. Complex cases involving partners, prescriptions, hotels, delivery disputes or damaged goods may take longer.</p>

  <h2>Contact</h2>
  <p>For refund or cancellation support, contact <?= htmlspecialchars($publicSupportEmail ?? 'support@citysolutions.in', ENT_QUOTES) ?><?= !empty($publicSupportPhone) ? ' or ' . htmlspecialchars($publicSupportPhone, ENT_QUOTES) : '' ?>.</p>
</main>
