# Worker App Secondary Route Coverage

The worker app is intended for mobile field workers who need live assignment lists, status updates, and location updates.

## Supported Now

- Mart delivery: `orders.delivery_man_id`, filtered by `module_key = mart` through the shared orders table.
- E-Commerce delivery: `orders.delivery_man_id`, filtered by `module_key = ecommerce` through the shared orders table.
- Medical delivery: `orders.delivery_man_id`, filtered by `module_key = medical` through the shared orders table.
- Services field work: `service_bookings.provider_id` through service provider login.
- Taxi/cab rides: `taxi_rides.driver_id` through taxi driver login, including live location and OTP ride start.

## Not In Worker App By Default

- Hotel bookings: handled by hotel owner/admin panels. Add hotel staff worker routes only if housekeeping, pickup, room-service, or check-in task assignment is required.
- Restaurant table bookings: handled by restaurant/admin panels. Add restaurant staff worker routes only if waiter/host table assignment or in-restaurant queue operations are required.
- Real estate visits: handled by real-estate agent/admin panels. Add mobile agent worker routes only if agents need app-side site-visit assignment/status updates.
- Complaints/support: handled by admin/module support flows. Add worker routing only if complaints need assigned field-resolution tasks.

## Recommended Expansion If Needed

If these operational roles become mobile-first, add them as separate worker roles instead of mixing them into delivery/service/taxi logic:

- `hotel_staff`
- `restaurant_staff`
- `real_estate_agent`
- `complaint_resolver`

Each role should have its own assignment query and allowed status transitions.
