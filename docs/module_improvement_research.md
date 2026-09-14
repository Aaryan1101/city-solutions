# City Solutions Module Improvement Research

This roadmap summarizes patterns from strong marketplace apps and legal/safety gaps that should become product tasks. It is not legal advice; final compliance should be reviewed by qualified counsel and licensed operators.

## Cross-Module Priorities

- Build a single customer trust layer: verified account, saved addresses, zone detection, wallet, refunds, support, complaints and notification preferences.
- Make every module show clear fulfillment state: pending review, confirmed, preparing, assigned, in transit, completed, cancelled, refund pending and refunded.
- Add richer search and filters: recent searches, saved filters, recommended filters, near-me sorting and high-intent shortcuts.
- Add module-specific policy display before checkout or booking: cancellation window, refund eligibility, prescription requirement, hotel taxes, service warranty and partner responsibility.
- Improve complaint routing: user complaint should attach module, order/booking id, vendor/provider/hotel/agent, zone, media and severity.

## Medical

Observed from e-pharmacy and medicine delivery norms:

- Prescription-only medicines must not behave like normal cart products.
- Schedule H/H1/X-like, narcotic, psychotropic, tranquilizer, sleeping pill, codeine/opioid, strong antibiotic and habit-forming products need strict controls.
- Several online pharmacy references state that prescription drugs require valid prescription and draft e-pharmacy rules prohibit sale of tranquilizers, psychotropic drugs, narcotics and habit-forming drugs online.

Recommended tasks:

- Add product fields: `medicine_type` (`otc`, `prescription_required`, `restricted`, `blocked_online`), `schedule_tag`, `max_qty_per_order`, `max_qty_per_month`, `requires_pharmacist_review`, `requires_age_confirmation`.
- Block checkout if prescription-required item has no prescription upload.
- Keep order in `prescription_pending` until pharmacist/admin approval.
- Add prescription review queue with approve/reject, reviewer name, timestamp, reason and replacement suggestion.
- Prevent restricted products from COD fulfillment until review is complete.
- Add suspicious-order flags: repeated purchase, high quantity, multiple sedatives, prescription mismatch, underage customer.
- Add customer-facing warning: “Use medicines only under medical advice.”
- Add pharmacist support channel and prescription privacy policy.

## Mart / Grocery

Patterns from quick-commerce and grocery apps:

- Strong repeat-order shortcuts matter more than browsing.
- Substitution, stock accuracy, delivery slot and expiry/freshness handling are key trust points.

Recommended tasks:

- Reorder from past cart.
- “Frequently bought” and “Buy again” sections.
- Substitution preference: call before replacing, auto-replace, no replacement.
- Freshness/expiry display for perishable and packaged goods.
- Delivery slot or estimated delivery promise by zone.
- Missing/damaged item report from order detail.

## E-Commerce

Patterns from mature marketplace apps:

- Buyer confidence comes from return windows, seller trust, variants, reviews and transparent pricing.

Recommended tasks:

- Return eligibility matrix by category/product/vendor.
- Seller rating, response time and fulfillment score.
- Rich variant selection with stock per variant.
- Product comparison, recently viewed and recommendation sections.
- Warranty/guarantee fields and downloadable invoice.
- Fraud checks for high-value COD orders.

## Services

Patterns from Urban Company-style service apps:

- Verified professional identity, skill category, warranty and rescheduling are core.

Recommended tasks:

- Provider identity verification fields and badge.
- Service warranty duration per category.
- Before/after service image upload.
- Customer checklist before booking.
- Reschedule request flow with provider approval.
- Provider arrival OTP and job completion OTP.
- Service issue escalation within warranty period.

## Hotels

Patterns from Booking.com/Airbnb-style flows:

- Users need policy clarity, photo trust, taxes/fees, reviews and cancellation rules before booking.

Recommended tasks:

- Cancellation policy preview before payment.
- Tax/fee breakdown and security deposit field.
- Room amenities and bed configuration filters.
- Hotel/room photo verification workflow.
- Guest rules and check-in requirements.
- No-show handling and partial refund automation.
- Owner-side room inventory calendar.

## Restaurants

Patterns from Zomato/EazyDiner/OpenTable-style table booking:

- Table booking needs availability, party size, slot confirmation and dining offers.

Recommended tasks:

- Party-size based slot availability.
- Booking deposit/no-show policy.
- Restaurant cuisine, ambience and offer filters.
- Table preference notes: indoor/outdoor, family, celebration.
- Restaurant confirmation/decline flow.
- Waitlist when slots are full.
- Dining offer validation at restaurant.

## Real Estate

Patterns from Magicbricks/99acres/Housing/Airbnb search ideas:

- Trust depends on verification, locality data, map search and agent responsiveness.

Recommended tasks:

- Verified property/agent badge.
- Locality price trend and nearby amenities.
- Map search and commute/time filters.
- Fraud/report listing flow.
- Site visit confirmation and reschedule.
- Agent response time metric.
- Project documents and RERA/regulatory id fields where applicable.

## Website / Legal Layer

Public website should include:

- Home page explaining modules and panels.
- About page.
- Trust & Safety page.
- Privacy policy.
- Terms of use.
- Refund and cancellation policy.
- Medical compliance page.
- Contact page.
- Clear panel login links: `/admin/login`, `/vendor/login`, `/service-provider/login`, `/hotel-owner/login`, `/real-estate-agent/login`.
