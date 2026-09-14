<?php
$active = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isMartActive = str_starts_with($active, '/admin/categories')
    || str_starts_with($active, '/admin/subcategories')
    || str_starts_with($active, '/admin/brands')
    || str_starts_with($active, '/admin/products')
    || str_starts_with($active, '/admin/banners')
    || str_starts_with($active, '/admin/coupons')
    || str_starts_with($active, '/admin/vendors')
    || str_starts_with($active, '/admin/orders')
    || str_starts_with($active, '/admin/notifications')
    || str_starts_with($active, '/admin/reviews')
    || str_starts_with($active, '/admin/refunds')
    || str_starts_with($active, '/admin/payments')
    || str_starts_with($active, '/admin/pos')
    || str_starts_with($active, '/admin/wallets')
    || str_starts_with($active, '/admin/delivery-men')
    || str_starts_with($active, '/admin/shipping')
    || str_starts_with($active, '/admin/support')
    || str_starts_with($active, '/admin/reports');
$isEcommerceActive = str_starts_with($active, '/admin/ecommerce');
$isMedicalActive = str_starts_with($active, '/admin/medical');
$isServicesActive = str_starts_with($active, '/admin/services');
$isHotelsActive = str_starts_with($active, '/admin/hotels');
$isRealEstateActive = str_starts_with($active, '/admin/real-estate');
$isRestaurantsActive = str_starts_with($active, '/admin/restaurants');
$isTaxiActive = str_starts_with($active, '/admin/taxi');
$isFloatingAdsActive = str_starts_with($active, '/admin/floating-ads');
$adminRole = $_SESSION['admin_role'] ?? 'super_admin';
$isSuperAdmin = $adminRole === 'super_admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'City Solutions Mart') ?></title>
    <style>
        :root {
            --bg: #f4f7fb;
            --surface: #ffffff;
            --surface-2: #f8fbff;
            --ink: #172a42;
            --muted: #6f8198;
            --line: #dfe8f3;
            --line-strong: #cbd8e6;
            --blue: #1f8fe5;
            --blue-dark: #1267b5;
            --green: #21a36d;
            --red: #dd3f3f;
            --amber: #c77a08;
            --nav: #10243a;
            --nav-soft: #18314d;
            --shadow: 0 18px 44px rgba(21, 42, 70, .10);
            --shadow-soft: 0 8px 24px rgba(21, 42, 70, .07);
            --radius: 14px;
            --radius-sm: 10px;
        }
        * { box-sizing: border-box; }
        html { min-height: 100%; }
        body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(31,143,229,.13), transparent 34rem),
                linear-gradient(180deg, #f8fbff 0%, var(--bg) 42%, #eef4fb 100%);
            color: var(--ink);
            font-size: 14px;
            line-height: 1.45;
        }
        a { color: inherit; text-decoration: none; }
        h1, h2, h3 { color: var(--ink); margin: 0; line-height: 1.15; }
        h1 { font-size: clamp(24px, 2vw, 34px); font-weight: 900; letter-spacing: 0; }
        h2 { font-size: 18px; font-weight: 900; margin-bottom: 14px; }
        h3 { font-size: 15px; font-weight: 900; }
        small { color: var(--muted); }
        code {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 7px;
            background: #eef5ff;
            color: var(--blue-dark);
            font-size: 12px;
            font-weight: 800;
        }
        .shell { display: flex; min-height: 100vh; }
        .side {
            width: 292px;
            flex: 0 0 292px;
            background: linear-gradient(180deg, var(--nav) 0%, #0b1a2b 100%);
            color: #d7e5f4;
            padding: 18px 14px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            box-shadow: 12px 0 30px rgba(16, 36, 58, .16);
        }
        .brand {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            padding: 10px 10px 18px;
            margin-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,.10);
            font-weight: 900;
            font-size: 15px;
            color: #fff;
        }
        .brand:before {
            content: "CS";
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #2ba6ff, #2ed193);
            color: #fff;
            font-size: 14px;
            box-shadow: 0 12px 24px rgba(31,143,229,.28);
        }
        .brand small {
            display: block;
            margin-top: 4px;
            color: #9db4cd;
            font-size: 11px;
            font-weight: 800;
        }
        .nav { padding: 4px 0 16px; }
        .nav a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            min-height: 38px;
            padding: 9px 12px;
            border-radius: 11px;
            margin-bottom: 4px;
            color: #b9c9dc;
            font-weight: 800;
            transition: background .16s ease, color .16s ease, transform .16s ease;
        }
        .nav a:hover { background: rgba(255,255,255,.08); color: #fff; transform: translateX(2px); }
        .nav a.active {
            background: linear-gradient(135deg, rgba(31,143,229,.26), rgba(46,209,147,.18));
            color: #fff;
            box-shadow: inset 3px 0 0 #38bdf8;
        }
        .nav-section {
            margin: 18px 10px 8px;
            color: #7f96b1;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .11em;
        }
        .nav-group {
            margin: 7px 0;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 13px;
            background: rgba(255,255,255,.025);
            overflow: hidden;
        }
        .nav-group summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px;
            color: #e9f2fb;
            font-weight: 900;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        .nav-group summary::-webkit-details-marker { display: none; }
        .nav-group summary:after {
            content: '+';
            width: 22px;
            height: 22px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(255,255,255,.09);
            color: #9dd7ff;
            font-size: 16px;
            line-height: 1;
        }
        .nav-group[open] { background: rgba(255,255,255,.055); border-color: rgba(255,255,255,.12); }
        .nav-group[open] summary { border-bottom: 1px solid rgba(255,255,255,.08); }
        .nav-group[open] summary:after { content: '-'; }
        .nav-group .nav-items { padding: 8px; }
        .nav-group .nav-items a {
            min-height: 34px;
            padding: 8px 10px;
            font-size: 13px;
            border-radius: 9px;
        }
        .main {
            flex: 1;
            min-width: 0;
            padding: 22px;
        }
        .top {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin: -22px -22px 22px;
            padding: 18px 24px;
            background: rgba(248,251,255,.86);
            border-bottom: 1px solid rgba(203,216,230,.72);
            backdrop-filter: blur(16px);
        }
        .top-kicker {
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 5px;
        }
        .top h1 { margin-top: 4px; }
        .top .pill { white-space: nowrap; }
        .card {
            background: rgba(255,255,255,.94);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-soft);
            margin-bottom: 18px;
            overflow-x: auto;
        }
        .card > h2:first-child {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 14px;
            margin-bottom: 18px;
            border-bottom: 1px solid var(--line);
        }
        .card form h2 {
            margin: 24px 0 14px;
            padding: 12px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #f2f8ff, #f7fbff);
            border: 1px solid var(--line);
            color: #1d3b5c;
            font-size: 16px;
        }
        .card form h2:first-child { margin-top: 0; }
        .card form > div:not(.row):has(input[type="checkbox"]) {
            padding: 10px 12px;
            border-radius: 11px;
            background: var(--surface-2);
            border: 1px solid var(--line);
        }
        .dashboard-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(260px, .7fr);
            gap: 18px;
            margin-bottom: 18px;
        }
        .hero-panel {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            padding: 24px;
            background:
                linear-gradient(135deg, rgba(16,36,58,.98), rgba(24,72,115,.92)),
                radial-gradient(circle at 82% 15%, rgba(46,209,147,.30), transparent 18rem);
            color: #fff;
            box-shadow: var(--shadow);
        }
        .hero-panel h2 {
            color: #fff;
            font-size: clamp(24px, 3vw, 38px);
            margin: 0 0 10px;
        }
        .hero-panel p {
            max-width: 720px;
            margin: 0;
            color: #b9cce0;
            font-weight: 700;
        }
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .hero-actions .btn.secondary {
            background: rgba(255,255,255,.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,.16);
        }
        .focus-panel {
            border-radius: 20px;
            padding: 20px;
            background: #fff;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-soft);
        }
        .focus-list {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }
        .focus-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 12px;
            border-radius: 12px;
            background: var(--surface-2);
            border: 1px solid var(--line);
            color: #3d5570;
            font-weight: 900;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }
        .stat {
            position: relative;
            min-height: 126px;
            overflow: hidden;
            border: 0;
            background:
                linear-gradient(135deg, #ffffff 0%, #f7fbff 62%, #edf7ff 100%);
        }
        .stat:after {
            content: "";
            position: absolute;
            right: -18px;
            bottom: -22px;
            width: 92px;
            height: 92px;
            border-radius: 50%;
            background: rgba(31,143,229,.10);
        }
        .stat span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        .stat strong {
            position: relative;
            z-index: 1;
            display: block;
            margin-top: 14px;
            color: var(--ink);
            font-size: clamp(30px, 4vw, 44px);
            font-weight: 950;
            letter-spacing: 0;
        }
        form { margin: 0; }
        input, select, textarea {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid var(--line-strong);
            border-radius: var(--radius-sm);
            background: #fff;
            color: var(--ink);
            font: inherit;
            outline: none;
            transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
        }
        input:focus, select:focus, textarea:focus {
            border-color: rgba(31,143,229,.75);
            box-shadow: 0 0 0 4px rgba(31,143,229,.12);
        }
        textarea { min-height: 96px; resize: vertical; }
        input[type="checkbox"] {
            width: 18px !important;
            min-height: 18px;
            height: 18px;
            accent-color: var(--blue);
            vertical-align: middle;
        }
        input[type="file"] {
            padding: 9px;
            background: var(--surface-2);
            border-style: dashed;
        }
        label {
            display: block;
            color: #405872;
            font-weight: 900;
            margin: 12px 0 6px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        button, .btn {
            border: 0;
            background: linear-gradient(135deg, var(--blue), #207bd1);
            color: #fff;
            min-height: 40px;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 10px 22px rgba(31,143,229,.18);
            transition: transform .16s ease, box-shadow .16s ease, opacity .16s ease;
        }
        button:hover, .btn:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(31,143,229,.23); }
        .btn.secondary { background: #eaf5ff; color: var(--blue-dark); box-shadow: none; }
        .btn.danger { background: #fff0f0; color: var(--red); box-shadow: none; }
        table {
            width: 100%;
            min-width: 780px;
            border-collapse: separate;
            border-spacing: 0;
        }
        th, td {
            text-align: left;
            padding: 13px 12px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }
        th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fbff;
            color: #687d95;
            font-size: 11px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        tbody tr { transition: background .14s ease; }
        tbody tr:hover { background: #f7fbff; }
        tbody tr:last-child td { border-bottom: 0; }
        .row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            align-items: start;
        }
        .thumb {
            width: 62px;
            height: 62px;
            object-fit: cover;
            border-radius: 13px;
            background: #eaf5ff;
            border: 1px solid var(--line);
        }
        video.thumb { background: #07111e; }
        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 25px;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eaf5ff;
            color: var(--blue-dark);
            font-weight: 900;
            font-size: 12px;
            white-space: nowrap;
        }
        .pill:empty { display: none; }
        .error {
            background: #fff1f1;
            color: #b82020;
            padding: 12px 14px;
            border: 1px solid #ffd1d1;
            border-radius: var(--radius-sm);
            margin-bottom: 14px;
            font-weight: 800;
        }
        .info {
            margin: 14px 0;
            padding: 14px;
            border: 1px solid #cfe4fb;
            border-radius: var(--radius-sm);
            background: #f2f8ff;
            color: #3b5875;
        }
        .info strong { color: var(--ink); }
        @media (max-width: 1180px) {
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 860px) {
            .shell { display: block; }
            .side {
                width: auto;
                height: auto;
                max-height: 72vh;
                position: relative;
                border-radius: 0 0 18px 18px;
            }
            .main { padding: 16px; }
            .top {
                position: relative;
                margin: -16px -16px 16px;
                padding: 16px;
                align-items: flex-start;
            }
            .grid, .row { grid-template-columns: 1fr; }
            .dashboard-hero { grid-template-columns: 1fr; }
            table { min-width: 680px; }
        }
    </style>
</head>
<body>
<?php if (($view ?? '') === 'admin/login' || ($view ?? '') === 'vendor/login' || ($view ?? '') === 'service_provider/login' || ($view ?? '') === 'hotel_owner/login' || ($view ?? '') === 'real_estate_agent/login'): ?>
    <?php require $viewPath; ?>
<?php else: ?>
<div class="shell">
    <aside class="side">
        <div class="brand"><span><?php if (str_starts_with($view ?? '', 'service_provider/')): ?>City Services Provider<small>Provider workspace</small><?php elseif (str_starts_with($view ?? '', 'hotel_owner/')): ?>City Hotel Owner<small>Hotel operations</small><?php elseif (str_starts_with($view ?? '', 'real_estate_agent/')): ?>City Real Estate Agent<small>Property workspace</small><?php elseif (str_starts_with($view ?? '', 'vendor/')): ?>City Mart Vendor<small>Seller workspace</small><?php else: ?>City Solutions Admin<small>Operations console</small><?php endif; ?></span></div>
        <nav class="nav">
            <?php if (str_starts_with($view ?? '', 'service_provider/')): ?>
            <a class="<?= $active === '/service-provider' ? 'active' : '' ?>" href="/service-provider">Dashboard</a>
            <a href="/service-provider/logout">Logout</a>
            <?php elseif (str_starts_with($view ?? '', 'hotel_owner/')): ?>
            <a class="<?= $active === '/hotel-owner' ? 'active' : '' ?>" href="/hotel-owner">Dashboard</a>
            <a href="/hotel-owner/logout">Logout</a>
            <?php elseif (str_starts_with($view ?? '', 'real_estate_agent/')): ?>
            <a class="<?= $active === '/real-estate-agent' ? 'active' : '' ?>" href="/real-estate-agent#summary">Dashboard</a>
            <a href="/real-estate-agent#profile">Profile</a>
            <a href="/real-estate-agent#properties">Properties</a>
            <a href="/real-estate-agent#projects">Projects</a>
            <a href="/real-estate-agent#project-units">Project Units</a>
            <a href="/real-estate-agent#inquiries">Inquiries</a>
            <a href="/real-estate-agent#visits">Site Visits</a>
            <a href="/real-estate-agent/logout">Logout</a>
            <?php elseif (str_starts_with($view ?? '', 'vendor/')): ?>
            <a class="<?= $active === '/vendor' ? 'active' : '' ?>" href="/vendor">Dashboard</a>
            <a class="<?= $active === '/vendor/products' ? 'active' : '' ?>" href="/vendor/products">Products</a>
            <a class="<?= $active === '/vendor/orders' ? 'active' : '' ?>" href="/vendor/orders">Orders</a>
            <a class="<?= str_starts_with($active, '/vendor/notifications') ? 'active' : '' ?>" href="/vendor/notifications">Notifications</a>
            <a class="<?= str_starts_with($active, '/vendor/reviews') ? 'active' : '' ?>" href="/vendor/reviews">Reviews</a>
            <a class="<?= str_starts_with($active, '/vendor/refunds') ? 'active' : '' ?>" href="/vendor/refunds">Refunds</a>
            <a class="<?= str_starts_with($active, '/vendor/support') ? 'active' : '' ?>" href="/vendor/support">Support</a>
            <a class="<?= str_starts_with($active, '/vendor/wallet') ? 'active' : '' ?>" href="/vendor/wallet">Wallet</a>
            <a href="/vendor/logout">Logout</a>
            <?php else: ?>
            <a class="<?= $active === '/admin' ? 'active' : '' ?>" href="/admin">Dashboard</a>
            <details class="nav-group" <?= $isMartActive ? 'open' : '' ?>>
                <summary>Mart Module</summary>
                <div class="nav-items">
                    <a class="<?= $active === '/admin/categories' ? 'active' : '' ?>" href="/admin/categories">Categories</a>
                    <a class="<?= str_starts_with($active, '/admin/subcategories') ? 'active' : '' ?>" href="/admin/subcategories">Subcategories</a>
                    <a class="<?= str_starts_with($active, '/admin/brands') ? 'active' : '' ?>" href="/admin/brands">Brands</a>
                    <a class="<?= $active === '/admin/products' ? 'active' : '' ?>" href="/admin/products">Products</a>
                    <a class="<?= $active === '/admin/banners' ? 'active' : '' ?>" href="/admin/banners">Banners</a>
                    <a class="<?= str_starts_with($active, '/admin/coupons') ? 'active' : '' ?>" href="/admin/coupons">Coupons</a>
                    <a class="<?= str_starts_with($active, '/admin/vendors') ? 'active' : '' ?>" href="/admin/vendors">Vendors</a>
                    <a class="<?= str_starts_with($active, '/admin/orders') ? 'active' : '' ?>" href="/admin/orders">Orders</a>
                    <a class="<?= str_starts_with($active, '/admin/notifications') ? 'active' : '' ?>" href="/admin/notifications">Notifications</a>
                    <a class="<?= str_starts_with($active, '/admin/reviews') ? 'active' : '' ?>" href="/admin/reviews">Reviews</a>
                    <a class="<?= str_starts_with($active, '/admin/refunds') ? 'active' : '' ?>" href="/admin/refunds">Refunds</a>
                    <a class="<?= str_starts_with($active, '/admin/payments') ? 'active' : '' ?>" href="/admin/payments">Payments</a>
                    <a class="<?= str_starts_with($active, '/admin/pos') ? 'active' : '' ?>" href="/admin/pos">POS</a>
                    <a class="<?= str_starts_with($active, '/admin/wallets') ? 'active' : '' ?>" href="/admin/wallets">Wallets</a>
                    <a class="<?= str_starts_with($active, '/admin/delivery-men') ? 'active' : '' ?>" href="/admin/delivery-men">Delivery Men</a>
                    <a class="<?= str_starts_with($active, '/admin/shipping') ? 'active' : '' ?>" href="/admin/shipping">Shipping</a>
                    <a class="<?= str_starts_with($active, '/admin/support') ? 'active' : '' ?>" href="/admin/support">Support</a>
                    <a class="<?= str_starts_with($active, '/admin/reports') ? 'active' : '' ?>" href="/admin/reports">Reports</a>
                    <?php if ($isSuperAdmin): ?>
                    <a class="<?= $active === '/admin/settings' ? 'active' : '' ?>" href="/admin/settings">Mart Settings</a>
                    <?php endif; ?>
                </div>
            </details>
            <details class="nav-group" <?= $isEcommerceActive ? 'open' : '' ?>>
                <summary>E-Commerce Module</summary>
                <div class="nav-items">
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/categories') ? 'active' : '' ?>" href="/admin/ecommerce/categories">E-Com Categories</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/subcategories') ? 'active' : '' ?>" href="/admin/ecommerce/subcategories">E-Com Subcategories</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/brands') ? 'active' : '' ?>" href="/admin/ecommerce/brands">E-Com Brands</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/products') ? 'active' : '' ?>" href="/admin/ecommerce/products">E-Com Products</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/banners') ? 'active' : '' ?>" href="/admin/ecommerce/banners">E-Com Banners</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/coupons') ? 'active' : '' ?>" href="/admin/ecommerce/coupons">E-Com Coupons</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/orders') ? 'active' : '' ?>" href="/admin/ecommerce/orders">E-Com Orders</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/reviews') ? 'active' : '' ?>" href="/admin/ecommerce/reviews">E-Com Reviews</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/refunds') ? 'active' : '' ?>" href="/admin/ecommerce/refunds">E-Com Refunds</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/payments') ? 'active' : '' ?>" href="/admin/ecommerce/payments">E-Com Payments</a>
                    <a class="<?= str_starts_with($active, '/admin/ecommerce/reports') ? 'active' : '' ?>" href="/admin/ecommerce/reports">E-Com Reports</a>
                    <?php if ($isSuperAdmin): ?>
                    <a class="<?= $active === '/admin/settings' ? 'active' : '' ?>" href="/admin/settings">E-Com Settings</a>
                    <?php endif; ?>
                </div>
            </details>
            <details class="nav-group" <?= $isMedicalActive ? 'open' : '' ?>>
                <summary>Medical Module</summary>
                <div class="nav-items">
                    <a class="<?= str_starts_with($active, '/admin/medical/categories') ? 'active' : '' ?>" href="/admin/medical/categories">Medicine Categories</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/subcategories') ? 'active' : '' ?>" href="/admin/medical/subcategories">Medicine Subcategories</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/brands') ? 'active' : '' ?>" href="/admin/medical/brands">Medical Brands</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/products') ? 'active' : '' ?>" href="/admin/medical/products">Medicines</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/banners') ? 'active' : '' ?>" href="/admin/medical/banners">Medical Banners</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/coupons') ? 'active' : '' ?>" href="/admin/medical/coupons">Medical Coupons</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/orders') ? 'active' : '' ?>" href="/admin/medical/orders">Medical Orders</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/reviews') ? 'active' : '' ?>" href="/admin/medical/reviews">Medical Reviews</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/refunds') ? 'active' : '' ?>" href="/admin/medical/refunds">Medical Refunds</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/prescriptions') ? 'active' : '' ?>" href="/admin/medical/prescriptions">Prescriptions</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/payments') ? 'active' : '' ?>" href="/admin/medical/payments">Medical Payments</a>
                    <a class="<?= str_starts_with($active, '/admin/medical/reports') ? 'active' : '' ?>" href="/admin/medical/reports">Medical Reports</a>
                    <?php if ($isSuperAdmin): ?>
                    <a class="<?= $active === '/admin/settings' ? 'active' : '' ?>" href="/admin/settings">Medical Settings</a>
                    <?php endif; ?>
                </div>
            </details>
            <details class="nav-group" <?= $isServicesActive ? 'open' : '' ?>>
                <summary>Services Module</summary>
                <div class="nav-items">
                    <a class="<?= str_starts_with($active, '/admin/services') ? 'active' : '' ?>" href="/admin/services#summary">Summary</a>
                    <a href="/admin/services#categories">Categories</a>
                    <a href="/admin/services#providers">Providers</a>
                    <a href="/admin/services#services">Services</a>
                    <a href="/admin/services#addons">Add-ons</a>
                    <a href="/admin/services#slots">Slots</a>
                    <a href="/admin/services#blackouts">Blackouts</a>
                    <a href="/admin/services#reports">Reports</a>
                    <a href="/admin/services#settlements">Settlements</a>
                    <a href="/admin/services#bookings">Bookings</a>
                </div>
            </details>
            <details class="nav-group" <?= $isHotelsActive ? 'open' : '' ?>>
                <summary>Hotel Module</summary>
                <div class="nav-items">
                    <a class="<?= str_starts_with($active, '/admin/hotels') ? 'active' : '' ?>" href="/admin/hotels#summary">Summary</a>
                    <a href="/admin/hotels#categories">Hotel Categories</a>
                    <a href="/admin/hotels#owners">Hotel Owners</a>
                    <a href="/admin/hotels#hotels">Hotels</a>
                    <a href="/admin/hotels#rooms">Rooms</a>
                    <a href="/admin/hotels#bookings">Bookings</a>
                </div>
            </details>
            <details class="nav-group" <?= $isRealEstateActive ? 'open' : '' ?>>
                <summary>Real Estate Module</summary>
                <div class="nav-items">
                    <a class="<?= str_starts_with($active, '/admin/real-estate') ? 'active' : '' ?>" href="/admin/real-estate#summary">Summary</a>
                    <a href="/admin/real-estate#agents">Agents / Builders</a>
                    <a href="/admin/real-estate#amenities">Amenities</a>
                    <a href="/admin/real-estate#properties">Properties</a>
                    <a href="/admin/real-estate#projects">Projects</a>
                    <a href="/admin/real-estate#project-units">Project Units</a>
                    <a href="/admin/real-estate#inquiries">Inquiries</a>
                    <a href="/admin/real-estate#visits">Site Visits</a>
                    <a href="/admin/real-estate#complaints">Listing Reports</a>
                </div>
            </details>
            <details class="nav-group" <?= $isRestaurantsActive ? 'open' : '' ?>>
                <summary>Restaurant Module</summary>
                <div class="nav-items">
                    <a class="<?= str_starts_with($active, '/admin/restaurants') ? 'active' : '' ?>" href="/admin/restaurants#summary">Summary</a>
                    <a href="/admin/restaurants#restaurants">Restaurants</a>
                    <a href="/admin/restaurants#food-items">Food Items</a>
                    <a href="/admin/restaurants#food-orders">Food Orders</a>
                    <a href="/admin/restaurants#bookings">Bookings</a>
                    <a href="/admin/restaurants#waitlist">Waitlist</a>
                </div>
            </details>
            <details class="nav-group" <?= $isTaxiActive ? 'open' : '' ?>>
                <summary>Taxi Module</summary>
                <div class="nav-items">
                    <a class="<?= str_starts_with($active, '/admin/taxi') ? 'active' : '' ?>" href="/admin/taxi#summary">Summary</a>
                    <a href="/admin/taxi#vehicle-types">Vehicle Types</a>
                    <a href="/admin/taxi#drivers">Drivers</a>
                    <a href="/admin/taxi#rides">Rides</a>
                </div>
            </details>
            <div class="nav-section">System</div>
            <a class="<?= $isFloatingAdsActive ? 'active' : '' ?>" href="/admin/floating-ads">Floating Ads</a>
            <?php if ($isSuperAdmin): ?>
            <a class="<?= str_starts_with($active, '/admin/zones') ? 'active' : '' ?>" href="/admin/zones">Zones</a>
            <?php endif; ?>
            <a class="<?= str_starts_with($active, '/admin/complaints') ? 'active' : '' ?>" href="/admin/complaints">Complaints</a>
            <?php if ($isSuperAdmin): ?>
            <a class="<?= str_starts_with($active, '/admin/health') ? 'active' : '' ?>" href="/admin/health">Production Health</a>
            <a class="<?= str_starts_with($active, '/admin/admin-users') ? 'active' : '' ?>" href="/admin/admin-users">Admin Users</a>
            <?php endif; ?>
            <?php if ($isSuperAdmin): ?>
            <a class="<?= $active === '/admin/settings' ? 'active' : '' ?>" href="/admin/settings">Settings</a>
            <?php endif; ?>
            <a href="/admin/logout">Logout</a>
            <?php endif; ?>
        </nav>
    </aside>
    <main class="main">
        <div class="top">
            <div>
                <div class="top-kicker">Operations workspace</div>
                <h1><?= htmlspecialchars($title ?? 'Admin') ?></h1>
            </div>
            <span class="pill">Panel Only</span>
        </div>
        <?php require $viewPath; ?>
    </main>
</div>
<?php endif; ?>
</body>
</html>
