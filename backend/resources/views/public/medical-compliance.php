<main class="legal">
  <span class="pill">Medical Compliance</span>
  <h1>Medical orders require stricter safeguards.</h1>
  <div class="notice">City Solutions must be configured and operated with licensed pharmacy partners, prescription review, restricted product controls and local legal review before the Medical module is opened for public production use.</div>

  <h2>Platform limitation</h2>
  <p>City Solutions is not a doctor, hospital, medical adviser or pharmacy by itself unless the legal operator has the required licenses. The platform supports ordering, prescription upload, review workflows, support and fulfillment coordination with authorized pharmacy/admin personnel. Users should consult a registered medical practitioner before using medicines.</p>

  <h2>Prescription controls</h2>
  <ul>
    <li>Products should be classified as OTC, prescription required, restricted, or blocked for online sale.</li>
    <li>Prescription-required orders should remain pending until a valid prescription is uploaded and reviewed.</li>
    <li>Review records should capture approval/rejection, reviewer identity, timestamp, rejection reason and replacement suggestion where applicable.</li>
    <li>Quantity limits, repeat-order checks and age confirmation should be configured for sensitive medicines.</li>
    <li>Prescription images and medical records should be accessible only to authorized personnel and retained according to applicable law.</li>
  </ul>

  <h2>Restricted and high-risk medicines</h2>
  <p>Schedule H/H1/X-like medicines, narcotic, psychotropic, tranquilizer, sleeping pill, opioid, codeine, strong antibiotic, habit-forming, cold-chain, high-risk or locally restricted products must not be sold casually. Some categories should be blocked from app checkout unless the operator has lawful pharmacy licensing, prescription validation, record keeping and fulfillment controls.</p>

  <h2>Customer responsibilities</h2>
  <ul>
    <li>Do not self-medicate or misuse medicines.</li>
    <li>Upload only valid prescriptions issued for the patient.</li>
    <li>Do not alter, forge, reuse, sell or share prescriptions unlawfully.</li>
    <li>Follow dosage, warnings and doctor/pharmacist instructions.</li>
    <li>Report wrong, damaged, expired or unsafe medicine immediately.</li>
  </ul>

  <h2>Pharmacy and admin responsibilities</h2>
  <p>Authorized pharmacy/admin personnel must verify prescriptions, product category, patient details, quantity, legal restrictions, stock, expiry, batch/traceability where applicable and safe fulfillment requirements. Suspicious, invalid, incomplete or unsafe orders should be rejected and escalated.</p>

  <h2>Returns and refunds</h2>
  <p>Prescription medicines, opened products, cold-chain products, hygiene-sensitive products, restricted medicines and correctly supplied medicines may be non-returnable except where required by law or where the wrong, damaged, expired or unsafe item was supplied. Approved refunds may be credited to wallet or original payment method according to payment configuration and policy.</p>

  <h2>Launch checklist</h2>
  <ul>
    <li>Configure licensed pharmacy/operator details in admin settings.</li>
    <li>Mark prescription-required, restricted and blocked medicines correctly.</li>
    <li>Set quantity limits and pharmacist/admin review requirements.</li>
    <li>Configure support escalation and complaint routing.</li>
    <li>Review the medical workflow with qualified legal/pharmacy compliance advisers for each operating area.</li>
  </ul>

  <h2>Contact</h2>
  <p>For prescription, medicine safety, privacy or compliance concerns, contact <?= htmlspecialchars($publicSupportEmail ?? 'support@citysolutions.in', ENT_QUOTES) ?><?= !empty($publicSupportPhone) ? ' or ' . htmlspecialchars($publicSupportPhone, ENT_QUOTES) : '' ?>.</p>
</main>
