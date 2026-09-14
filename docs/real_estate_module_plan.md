# Real Estate Module Plan

## Architecture

- Module path: `lib/features/real_estate`.
- Backend scope: independent PHP controllers, support schema, admin pages, API endpoints, and tables prefixed with `re_`.
- Shared infrastructure: customer account/session, global `zones`, global bottom navigation, shared complaint/support patterns where practical.
- Navigation: plain `Navigator.push` / `MaterialPageRoute`, matching the existing app.
- State: local `setState`, `FutureBuilder`, and module API client classes. No new state framework.

## Backend Data Model

- `re_agents`: approved agent/builder accounts for the real estate panel.
- `re_agent_documents`: verification documents.
- `re_properties`: independent property listings with `zone_id`, approval status, listing purpose, category, price, address, location, specs, verification flags, and feature flags.
- `re_property_images`: gallery images.
- `re_amenities`: amenity master data.
- `re_property_amenities`: property to amenity mapping.
- `re_floor_plans`: property floor plans.
- `re_projects`: builder projects/new launches.
- `re_project_units`: unit/floor-plan options inside projects.
- `re_favorites`: customer saved properties.
- `re_saved_searches`: customer saved filters.
- `re_inquiries`: customer inquiries to agents/builders.
- `re_site_visits`: visit bookings. These must also appear in the global Bookings tab.
- `re_complaints`: real-estate scoped complaints if global complaint routing is not enough.

Use the existing global `zones` table. Do not create `re_zones`.

## Required App Scope

- Real estate home with featured/latest properties and project sections.
- Listing cards with price, location, purpose, category, specs, and image.
- Filter/search: buy, rent, lease, commercial, price range, zone, amenities, bedrooms, property type.
- Property detail with gallery, specs, amenities, map preview, agent card, inquiry, call/chat intent, favorite, and schedule visit.
- Favorites and saved searches.
- Site visit request flow.
- Agent/builder profile.
- Projects/new launches with floor plans.

## Agent/Builder Panel

- Agent registration.
- Admin approval/rejection with reason.
- Agent login/panel following the existing vendor/provider/hotel-owner pattern.
- Own listings CRUD.
- Listing submission status: draft, pending, approved, rejected, sold, rented, inactive.
- Inquiry and site visit management.
- Basic reports: active listings, pending inquiries, scheduled visits.

## Admin Scope

- Agent/builder approval queue.
- Listing approval queue.
- Amenities CRUD.
- Property/project CRUD.
- Featured toggles.
- Reports: listing counts, pending approvals, active agents, inquiries, visits.

## Maps And Zones

- Use Google Maps for map view and detail map preview.
- Filter properties by selected/detected global zone.
- Store `lat`/`lng` on properties and projects.
- Later: map clustering and nearby properties by radius.

## Build Order

1. Backend schema and demo data.
2. Public API: home/list/detail/amenities/favorites/inquiries/site visits.
3. Flutter customer listing/detail/filter/favorite/inquiry/site visit.
4. Global Bookings integration for `re_site_visits`.
5. Admin CRUD/approval pages.
6. Agent registration and panel.
7. Maps and zone polish.
8. Projects/floor plans.
9. Reports/support/security polish.

## Production Notes

- Keep real estate tables/controllers independent from Mart/E-Commerce/Services.
- Reuse shared customer account only for customer actions.
- Use a separate approved agent/builder account model for management.
- Do not reuse Estaty code. Use it only as layout inspiration.
- Validate uploads, permissions, and role gates before production release.
