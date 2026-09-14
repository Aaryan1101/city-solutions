# City Solutions Media Asset Requirements

Use this list to collect real images/videos for backend upload. The app should read these URLs from API responses instead of adding new Flutter assets for every product, vendor, banner, ad, hotel, or service.

## Global App

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Brand | App logo, transparent PNG if possible | 512x512 |
| Welcome/Splash | City/service lifestyle hero image | 1200x900 |
| Empty states | No orders, no bookings, no results, no notifications, no wallet activity | 800x800 |
| User profile | Default avatar | 512x512 |
| Floating ads | Image, thumbnail, MP4 video | 9:16 or source aspect, under 10-15 MB |

## Home

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Home banners | 3-5 city/module promo banners | 1600x600 |
| Module icons | E-Commerce, Mart, Medical, Services, Hotels, Real Estate, Restaurant | 512x512 PNG |
| Promo cards | One offer visual per active module | 1200x700 |
| Location/Near Me | City/location illustration or photo | 1200x700 |

## Mart

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Categories | Fruits, dairy, grocery, snacks, beverages, cleaning, personal care | 800x800 |
| Brands | Brand logos | 512x512 PNG |
| Vendors | Store logo and store cover image | Logo 512x512, cover 1600x600 |
| Products | 2-4 images per product | 1000x1000 WebP/JPG |
| Promotions | Banner, flash deal, clearance, top-rated section art | 1600x600 |

## E-Commerce

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Categories | Electronics, fashion, beauty, home, appliances, accessories | 800x800 |
| Brands | Brand logos | 512x512 PNG |
| Vendors | Store logo and cover image | Logo 512x512, cover 1600x600 |
| Products | 2-5 images per product, variant/color images where possible | 1000x1000 |
| Promotions | Flash deal, best selling, clearance, coupons | 1600x600 |

## Medical

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Categories | Medicines, wellness, personal care, baby care, devices, supplements | 800x800 |
| Pharmacies | Pharmacy logo and cover image | Logo 512x512, cover 1600x600 |
| Products | Product pack shots, prescription-required badge image if needed | 1000x1000 |
| Prescription | Upload/review/approval illustrations | 800x800 |
| Promotions | Medicine delivery and wellness offer banners | 1600x600 |

## Services

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Categories | Cleaning, plumber, electrician, salon, mechanic, repair, pest control, painting | 800x800 |
| Providers | Provider profile photo/logo and cover image | Logo 512x512, cover 1600x600 |
| Services | Before/after or service-specific photos | 1200x900 |
| Flow states | Booking success, cancel request, reschedule illustrations | 800x800 |
| Promotions | Service offer banners | 1600x600 |

## Hotels

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Hotels | Exterior, lobby, room, bathroom, restaurant, amenity gallery | 1600x1000 |
| Rooms | One image per room type minimum | 1600x1000 |
| Owners | Hotel owner/logo image | 512x512 |
| Promotions | Hotel deal banners | 1600x600 |
| Flow states | Booking success/cancellation illustrations | 800x800 |

## Restaurant

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Restaurant | Logo, cover, dining/table photos | Logo 512x512, cover 1600x600 |
| Cuisine | Cuisine/category images | 800x800 |
| Tables | Table/area photos where possible | 1200x900 |
| Promotions | Restaurant booking offer banners | 1600x600 |

## Real Estate

| Area | Needed Media | Recommended Size |
| --- | --- | --- |
| Properties | Cover image and gallery: exterior, rooms, kitchen, bathroom, locality | 1600x1000 |
| Categories | Apartment, villa, plot, commercial, rental | 800x800 |
| Agents/Builders | Profile/logo image | 512x512 |
| Localities | Area/neighborhood images | 1600x900 |
| Promotions | Property banners | 1600x600 |

## Backend Field Mapping

Minimum fields to support clean display:

- `image_url` for category/service/product primary image.
- `thumbnail` for product/service list cards.
- `gallery_json` or a related media table for multiple product/hotel/property images.
- `logo` and `cover_image` for vendors, providers, pharmacies, hotels, restaurants, agents.
- `banner_image` for module banners and promo sections.
- `media_type`, `media_url`, `thumbnail_url`, `target_type`, `target_id` for floating ads.

Preferred formats:

- Product/category/service cards: WebP first, compressed JPG second.
- Logos/icons: transparent PNG.
- Banners: WebP/JPG.
- Videos: MP4, H.264, browser-playable direct URL.
