<main>
  <section class="desi-hero">
    <div>
      <span class="desi-kicker">City Solutions • Local services platform</span>
      <h1>Apne shehar ki daily needs, ek app mein.</h1>
      <span class="desi-hindi">Grocery, shopping, medicines, ghar ke services, hotel, restaurant, property aur taxi.</span>
      <p>City Solutions customers ko verified local businesses aur service partners se connect karta hai. App area ke hisaab se availability dikhata hai, order aur booking track karta hai, aur support/complaint trail ek jagah rakhta hai.</p>
      <div class="desi-actions">
        <a class="btn btn-orange" href="/contact#join">Community join karein</a>
        <a class="btn btn-outline" href="/#modules">App mein kya hai</a>
      </div>
      <div class="desi-proof service-line" aria-label="City Solutions highlights">
        <article><strong>Mart</strong><span>Groceries</span></article>
        <article><strong>Medical</strong><span>Prescription flow</span></article>
        <article><strong>Services</strong><span>Home experts</span></article>
        <article><strong>Hotel</strong><span>Room booking</span></article>
        <article><strong>Taxi</strong><span>Driver app</span></article>
      </div>
    </div>

    <div class="desi-phone-wrap" aria-label="City Solutions app preview">
      <div class="desi-phone">
        <div class="desi-phone-screen">
          <div class="desi-app-top">
            <div class="desi-app-brand">
              <span>City Solutions</span>
              <img src="/uploads/brand/city-solutions-logo.jpeg" alt="City Solutions logo">
            </div>
            <div class="desi-location">Hazratganj, Lucknow</div>
            <h2 style="margin:22px 0 0;font-size:28px;line-height:1.1">Local services near you</h2>
          </div>
          <div class="desi-search">Search medicine, groceries, services...</div>
          <div class="desi-app-grid">
            <div class="desi-app-tile"><span class="desi-app-icon">E</span>E-Com</div>
            <div class="desi-app-tile"><span class="desi-app-icon">M</span>Mart</div>
            <div class="desi-app-tile"><span class="desi-app-icon">S</span>Services</div>
            <div class="desi-app-tile"><span class="desi-app-icon">Rx</span>Medical</div>
            <div class="desi-app-tile"><span class="desi-app-icon">H</span>Hotel</div>
            <div class="desi-app-tile"><span class="desi-app-icon">R</span>Restaurant</div>
            <div class="desi-app-tile"><span class="desi-app-icon">RE</span>Property</div>
            <div class="desi-app-tile"><span class="desi-app-icon">T</span>Taxi</div>
            <div class="desi-app-tile"><span class="desi-app-icon">C</span>Complaint</div>
          </div>
          <div style="margin:0 18px 20px;padding:18px;border-radius:18px;background:#0b63c7;color:#fff;font-weight:900">
            Verified partners. Area-wise availability.
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="desi-section" id="modules">
    <span class="pill">App services</span>
    <h2 class="desi-title">Every module is built for actual local operations.</h2>
    <p class="desi-copy">Customer app, partner panels, zone control, order/booking status, refund trail, wallet, support and reports work together without mixing module data.</p>
    <div class="desi-module-grid">
      <article class="desi-module-card">
        <span class="desi-module-icon">M</span>
        <div><h3>Mart</h3><p>Nearby groceries, essentials, offers, delivery tracking and refunds.</p></div>
      </article>
      <article class="desi-module-card">
        <span class="desi-module-icon">E</span>
        <div><h3>E-Commerce</h3><p>Local shopping with vendor products, cart, wishlist, reviews and orders.</p></div>
      </article>
      <article class="desi-module-card">
        <span class="desi-module-icon">Rx</span>
        <div><h3>Medical</h3><p>Prescription upload, medicine review, pharmacy workflow and safe fulfillment.</p></div>
      </article>
      <article class="desi-module-card">
        <span class="desi-module-icon">S</span>
        <div><h3>Services</h3><p>Electrician, plumber, cleaning, salon, repair and provider booking flows.</p></div>
      </article>
      <article class="desi-module-card">
        <span class="desi-module-icon">H</span>
        <div><h3>Hotels</h3><p>Room discovery, booking, owner panel, cancellation and refund policies.</p></div>
      </article>
      <article class="desi-module-card">
        <span class="desi-module-icon">R</span>
        <div><h3>Restaurant</h3><p>Table reservations, waitlist, dining bookings and restaurant operations.</p></div>
      </article>
      <article class="desi-module-card">
        <span class="desi-module-icon">RE</span>
        <div><h3>Real Estate</h3><p>Listings, projects, site visits, agent inquiries and property complaints.</p></div>
      </article>
      <article class="desi-module-card">
        <span class="desi-module-icon">T</span>
        <div><h3>Taxi</h3><p>Cab booking, driver assignment, OTP ride start and live worker route support.</p></div>
      </article>
    </div>
  </section>

  <?php if (!empty($catalogPreview)): ?>
  <section class="desi-section" id="catalogue">
    <span class="pill">Explore the catalogue</span>
    <h2 class="desi-title">Find the right section without digging through one long list.</h2>
    <p class="desi-copy">Mart, shopping and medical catalogues are organised into clear categories and subcategories managed by City Solutions partners.</p>
    <div class="desi-catalog-grid">
      <?php foreach (array_slice($catalogPreview, 0, 12) as $category): ?>
      <article class="desi-catalog-item">
        <span class="desi-catalog-module"><?= htmlspecialchars(match ($category['module_key']) { 'ecommerce' => 'E-Commerce', 'medical' => 'Medical', default => 'Mart' }) ?></span>
        <h3><?= htmlspecialchars($category['name']) ?></h3>
        <?php if (!empty($category['subcategories'])): ?>
        <div class="desi-subcategory-list">
          <?php foreach ($category['subcategories'] as $subcategory): ?><span><?= htmlspecialchars($subcategory) ?></span><?php endforeach; ?>
        </div>
        <?php else: ?><p>More sections will appear as local partners expand the catalogue.</p><?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="desi-band">
    <span class="pill" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.18)">Why local partners join</span>
    <h2 class="desi-title">Local partners get proper tools, not just listing space.</h2>
    <p class="desi-copy">Stores, providers, hotels, restaurants and property partners can manage their work through module-specific panels while admin keeps approval, zone and support control.</p>
    <div class="desi-community-grid">
      <article class="desi-community-card">Local stores<span>Products, stock, offers, orders and delivery assignment.</span></article>
      <article class="desi-community-card">Service providers<span>Bookings, availability, status updates and reports.</span></article>
      <article class="desi-community-card">Hotels<span>Rooms, booking dates, owner panel and cancellation controls.</span></article>
      <article class="desi-community-card">Restaurants<span>Tables, slots, waitlist and dining reservations.</span></article>
      <article class="desi-community-card">Property partners<span>Listings, site visits, inquiries and verification trail.</span></article>
    </div>
  </section>

  <section class="desi-section">
    <div class="desi-local-grid">
      <div class="desi-story">
        <span class="pill">Built for Indian cities</span>
        <h2 class="desi-title">Simple for customers. Structured for operations.</h2>
        <p class="desi-copy">A customer can place an order, book a service, reserve a table, book a hotel room, request a property visit or raise a complaint. The backend keeps each module independent while account, wallet, support and zone logic stay unified where needed.</p>
        <div class="desi-actions">
          <a class="btn btn-dark" href="/trust-safety">Trust & safety dekhein</a>
          <a class="btn btn-outline" href="/medical-compliance">Medical compliance</a>
        </div>
      </div>
      <div class="desi-photo-card">
        <div>
          <strong>Local network, verified partners</strong>
          <p style="margin:8px 0 0;color:#dbe7f6">City Solutions area-wise onboarding and admin controls ke saath operate hota hai.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="desi-band" id="community">
    <span class="pill" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.18)">Community</span>
    <h2 class="desi-title">Join the City Solutions local network.</h2>
    <p class="desi-copy">Customers can follow area launches. Businesses can request onboarding. Local contributors can recommend trusted shops, service experts, pharmacies, hotels, restaurants and property partners.</p>
    <div class="desi-actions">
      <a class="btn btn-orange" href="/contact#join">Join City Solutions Community</a>
      <a class="btn btn-outline" style="background:rgba(255,255,255,.10);color:#fff;border-color:rgba(255,255,255,.18)" href="/contact">Contact team</a>
    </div>
  </section>
</main>
