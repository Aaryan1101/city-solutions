<?php
$publicPage = basename((string) ($viewPath ?? 'home.php'), '.php');
$pageTitles = [
    'home' => 'City Solutions | One app for daily city needs',
    'about' => 'About City Solutions',
    'trust-safety' => 'Trust & Safety | City Solutions',
    'privacy-policy' => 'Privacy Policy | City Solutions',
    'terms' => 'Terms of Use | City Solutions',
    'refund-cancellation' => 'Refund & Cancellation Policy | City Solutions',
    'medical-compliance' => 'Medical Compliance | City Solutions',
    'account-deletion' => 'Account Deletion | City Solutions',
    'contact' => 'Contact | City Solutions',
    'not-found' => 'Page Not Found | City Solutions',
];
$pageTitle = $pageTitles[$publicPage] ?? 'City Solutions';
$publicSettings = [];
try {
    $publicSettings = \App\Support\Settings::all();
} catch (\Throwable) {
    $publicSettings = [];
}
$publicValue = static fn (string $key, string $default = ''): string => trim((string) ($publicSettings[$key] ?? $default));
$publicBusinessName = $publicValue('public_business_name', 'City Solutions');
$publicLegalEntity = $publicValue('public_legal_entity', $publicBusinessName);
$publicSupportEmail = $publicValue('public_support_email', 'support@citysolutions.in');
$publicSupportPhone = $publicValue('public_support_phone');
$publicBusinessAddress = $publicValue('public_business_address', 'Lucknow, India');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="City Solutions brings shopping, mart, services, medical delivery, hotels, restaurants, and real estate workflows into one city-first platform.">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #eef7ff;
      --bg-card-white: #ffffff;
      --bg-dark: #062f68;
      --bg-dark-card: #0a458d;
      --text-dark: #0b2442;
      --text-cream: #f7fbff;
      --text-muted: #63758c;
      --text-muted-on-dark: #c7dfff;
      --accent-orange: #1098e8;
      --accent-orange-soft: #9de7ff;
      --accent-green: #21bd9a;
      --border-hairline: #cfe1f2;
      --brand-navy: #06357a;
      --brand-blue: #0e84df;
      --brand-sky: #22b8f2;
      --radius-lg: 30px;
      --radius-md: 16px;
      --radius-full: 999px;
      --shadow-soft: 0 24px 60px rgba(6, 47, 104, .14);
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      background:
        radial-gradient(circle at 16% 8%, rgba(34,184,242,.20), transparent 24rem),
        radial-gradient(circle at 86% 2%, rgba(14,132,223,.16), transparent 26rem),
        var(--bg-cream);
      color: var(--text-dark);
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      line-height: 1.5;
    }
    a { color: inherit; text-decoration: none; }
    img { max-width: 100%; display: block; }
    .page-frame {
      position: relative;
      padding: 22px clamp(18px, 3vw, 44px);
      overflow: hidden;
    }
    .page-frame:before,
    .page-frame:after {
      content: "";
      position: fixed;
      top: 118px;
      bottom: 36px;
      width: clamp(80px, 9vw, 160px);
      border-radius: 32px;
      pointer-events: none;
      z-index: 0;
      opacity: .9;
    }
    .page-frame:before {
      left: clamp(10px, 1.6vw, 28px);
      background:
        linear-gradient(180deg, rgba(14,132,223,.18), rgba(33,189,154,.10)),
        repeating-linear-gradient(180deg, transparent 0 54px, rgba(6,53,122,.08) 54px 55px);
    }
    .page-frame:after {
      right: clamp(10px, 1.6vw, 28px);
      background:
        linear-gradient(180deg, rgba(6,53,122,.13), rgba(34,184,242,.16)),
        repeating-linear-gradient(180deg, transparent 0 54px, rgba(6,53,122,.08) 54px 55px);
    }
    .shell { position: relative; z-index: 1; max-width: 1540px; margin: 0 auto; }
    .nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      padding: 14px 18px;
      margin-bottom: 24px;
      border-radius: 24px;
      background: rgba(255,255,255,.78);
      border: 1px solid rgba(207,225,242,.85);
      box-shadow: 0 18px 42px rgba(6,47,104,.10);
      backdrop-filter: blur(16px);
      position: sticky;
      top: 16px;
      z-index: 10;
    }
    .brand { display: flex; align-items: center; gap: 12px; font-weight: 900; color: var(--brand-navy); }
    .brand-mark {
      width: 46px; height: 46px; border-radius: 15px;
      display: grid; place-items: center; overflow: hidden;
      background: #fff;
      border: 1px solid rgba(14,132,223,.16);
      box-shadow: 0 10px 24px rgba(6,47,104,.10);
    }
    .brand-mark img { width: 100%; height: 100%; object-fit: cover; transform: scale(1.35); }
    .brand-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--brand-sky); display: inline-block; margin-left: 4px; }
    .nav-links { display: flex; align-items: center; gap: 20px; color: var(--text-muted); font-size: 14px; font-weight: 700; }
    .nav-actions { display: flex; align-items: center; gap: 10px; }
    .btn {
      display: inline-flex; align-items: center; justify-content: center; gap: 8px;
      min-height: 44px; padding: 0 18px; border-radius: var(--radius-full);
      border: 1px solid transparent; font-weight: 900; font-size: 14px;
      transition: transform .18s ease, opacity .18s ease, background .18s ease;
    }
    .btn:hover { transform: translateY(-1px); }
    .btn-dark { background: var(--brand-navy); color: var(--text-cream); }
    .btn-orange { background: linear-gradient(135deg, var(--brand-blue), var(--brand-sky)); color: #fff; }
    .btn-outline { border-color: var(--border-hairline); color: var(--text-dark); background: rgba(255,255,255,.42); }
    .pill {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 12px; border-radius: var(--radius-full);
      background: rgba(16, 152, 232, .12); color: var(--brand-blue);
      font-size: 12px; font-weight: 900;
    }
    .hero, .section {
      border-radius: var(--radius-lg);
      overflow: hidden;
    }
    .hero {
      position: relative;
      display: grid;
      grid-template-columns: minmax(0, .9fr) minmax(360px, .82fr) minmax(260px, .42fr);
      gap: 22px;
      min-height: 700px;
      padding: clamp(34px, 4vw, 66px);
      background:
        linear-gradient(135deg, rgba(6,47,104,.98), rgba(7,73,145,.94) 52%, rgba(11,132,213,.92)),
        var(--bg-dark);
      color: var(--text-cream);
    }
    .hero:before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(180deg, rgba(255,255,255,.05) 1px, transparent 1px);
      background-size: 64px 64px;
      mask-image: linear-gradient(110deg, #000 0%, transparent 66%);
      pointer-events: none;
    }
    .hero > * { position: relative; z-index: 1; }
    .hero h1, .section-title, .legal h1 {
      font-family: Inter, system-ui, sans-serif;
      font-weight: 900;
      line-height: 1.02;
      letter-spacing: 0;
      margin: 0;
    }
    .hero h1 { font-size: clamp(42px, 6vw, 74px); max-width: 780px; }
    .hero p { color: var(--text-muted-on-dark); font-size: 17px; max-width: 620px; margin: 22px 0 0; }
    .hero-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
    .hero-proof { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:32px; max-width:620px; }
    .proof-chip { padding:13px 14px; border-radius:18px; background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.14); color:#fff; font-weight:900; }
    .proof-chip span { display:block; margin-top:4px; color:var(--text-muted-on-dark); font-size:12px; font-weight:700; }
    .hero-media {
      min-height: 520px; border-radius: 28px;
      background:
        linear-gradient(rgba(6,47,104,.04), rgba(6,47,104,.20)),
        url('https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=1000&q=80') center / cover;
      position: relative;
      overflow: hidden;
      box-shadow: 0 30px 70px rgba(0,24,74,.26);
    }
    .hero-media:after { content:""; position:absolute; inset:auto 0 0; height:42%; background:linear-gradient(transparent, rgba(4,34,76,.78)); }
    .brand-showcase {
      position:absolute; right:22px; top:22px; width:142px; height:142px; border-radius:26px;
      background:#fff url('/uploads/brand/city-solutions-logo.jpeg') center / 152% no-repeat;
      box-shadow:var(--shadow-soft); border:1px solid rgba(255,255,255,.75);
    }
    .rating-card {
      position: absolute; left: 22px; bottom: 22px;
      background: rgba(255,255,255,.96); color: var(--text-dark);
      border-radius: 18px; padding: 14px 16px; box-shadow: var(--shadow-soft);
      font-weight: 900;
      z-index: 2;
    }
    .area-card {
      position:absolute; right:22px; bottom:22px; z-index:2;
      border-radius:18px; padding:14px 16px;
      background:rgba(6,47,104,.86); color:#fff; border:1px solid rgba(255,255,255,.20);
      font-weight:900; backdrop-filter:blur(10px);
    }
    .hero-side {
      display: grid;
      align-content: stretch;
      gap: 14px;
      min-width: 0;
    }
    .side-card {
      position: relative;
      overflow: hidden;
      border-radius: 24px;
      padding: 20px;
      background: rgba(255,255,255,.13);
      border: 1px solid rgba(255,255,255,.18);
      color: #fff;
      min-height: 150px;
      box-shadow: inset 0 1px 0 rgba(255,255,255,.10);
    }
    .side-card strong {
      display: block;
      font-size: 28px;
      line-height: 1;
      margin-bottom: 8px;
    }
    .side-card span { color: var(--text-muted-on-dark); font-weight: 750; }
    .side-card.logo-tile {
      min-height: 210px;
      background: #fff url('/uploads/brand/city-solutions-logo.jpeg') center / 142% no-repeat;
      border-color: rgba(255,255,255,.72);
    }
    .side-card.route-map {
      background:
        radial-gradient(circle at 20% 26%, rgba(255,255,255,.30) 0 7px, transparent 8px),
        radial-gradient(circle at 74% 62%, rgba(255,255,255,.28) 0 7px, transparent 8px),
        linear-gradient(135deg, rgba(255,255,255,.14), rgba(33,189,154,.16));
    }
    .side-card.route-map:after {
      content: "";
      position: absolute;
      inset: 36px 34px;
      border: 2px dashed rgba(255,255,255,.32);
      border-left: 0;
      border-bottom: 0;
      border-radius: 50%;
      transform: rotate(-12deg);
    }
    .section { margin-top: 28px; padding: 70px 62px; background: var(--bg-card-white); border: 1px solid var(--border-hairline); }
    .section.dark {
      background:
        linear-gradient(135deg, var(--brand-navy), #084d9a);
      color: var(--text-cream); border-color: transparent;
    }
    .section-title { font-size: clamp(34px, 4vw, 50px); max-width: 850px; }
    .section-copy { color: var(--text-muted); max-width: 720px; margin: 16px 0 0; }
    .dark .section-copy { color: var(--text-muted-on-dark); }
    .module-grid {
      display: grid; grid-template-columns: repeat(8, minmax(0, 1fr)); gap: 14px; margin-top: 30px;
    }
    .module-card {
      min-height: 190px; padding: 22px; border-radius: 22px; background: #fff; border: 1px solid var(--border-hairline);
      display: flex; flex-direction: column; justify-content: space-between; transition: transform .18s ease, box-shadow .18s ease;
    }
    .module-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-soft); }
    .module-icon {
      width: 46px; height: 46px; border-radius: 16px; display: grid; place-items: center;
      background: linear-gradient(135deg, rgba(16,152,232,.14), rgba(33,189,154,.14)); color: var(--brand-blue); font-weight: 900;
    }
    .module-card h3 { margin: 18px 0 6px; font-size: 18px; }
    .module-card p { margin: 0; color: var(--text-muted); font-size: 14px; }
    .feature-grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 18px; margin-top: 32px; }
    .feature-card { border-radius: 24px; padding: 26px; background: rgba(255,255,255,.09); color: var(--text-cream); min-height: 230px; border:1px solid rgba(255,255,255,.12); }
    .feature-card.light { background: #fff; color: var(--text-dark); border: 1px solid var(--border-hairline); }
    .feature-card p { color: var(--text-muted-on-dark); margin: 10px 0 0; }
    .feature-card.light p { color: var(--text-muted); }
    .split { display: grid; grid-template-columns: .9fr 1.1fr; gap: 34px; align-items: center; }
    .photo-stack { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .photo {
      min-height: 320px; border-radius: 24px; background-size: cover; background-position: center;
    }
    .photo.one { background-image: url('https://images.unsplash.com/photo-1621905251918-48416bd8575a?auto=format&fit=crop&w=900&q=80'); }
    .photo.two { background-image: url('https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&w=900&q=80'); transform: translateY(34px); }
    .legal-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
    .panel-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-top: 24px; }
    .panel-link { padding: 16px; border-radius: 18px; background: rgba(255,255,255,.08); color: var(--text-cream); border: 1px solid rgba(255,255,255,.10); font-weight: 900; }
    .footer {
      margin-top: 28px; border-radius: var(--radius-lg); padding: 48px 52px 34px;
      background: var(--bg-dark); color: var(--text-cream);
    }
    .footer-grid { display: grid; grid-template-columns: 1.3fr repeat(3, 1fr); gap: 28px; }
    .footer h3, .footer h4 { margin-top: 0; }
    .footer-logo { width: 76px; height: 76px; border-radius: 20px; object-fit: cover; background:#fff; margin-bottom:14px; }
    .footer a, .footer p { color: var(--text-muted-on-dark); }
    .footer ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 9px; }
    .footer-bottom { border-top: 1px solid rgba(255,255,255,.10); margin-top: 34px; padding-top: 22px; display: flex; justify-content: space-between; gap: 18px; flex-wrap: wrap; color: var(--text-muted-on-dark); font-size: 13px; }
    .legal {
      border-radius: var(--radius-lg); padding: 58px 62px; background: #fff; border: 1px solid var(--border-hairline);
    }
    .legal h1 { font-size: clamp(36px, 5vw, 58px); }
    .legal h2 { margin-top: 34px; font-size: 24px; }
    .legal p, .legal li { color: var(--text-muted); }
    .legal ul { padding-left: 20px; }
    .notice { padding: 16px 18px; border-radius: 18px; background: rgba(232,98,44,.10); border: 1px solid rgba(232,98,44,.22); color: var(--text-dark); margin-top: 22px; }
    @media (max-width: 960px) {
      .page-frame { padding: 12px; }
      .page-frame:before, .page-frame:after { display:none; }
      .nav { align-items: flex-start; }
      .nav-links { display: none; }
      .hero, .split, .feature-grid { grid-template-columns: 1fr; }
      .hero-side { grid-template-columns: 1fr 1fr; }
      .hero { padding: 34px 24px; min-height: unset; }
      .hero-media { min-height: 380px; }
      .hero-proof { grid-template-columns:1fr; }
      .brand-showcase { width:104px; height:104px; }
      .area-card { left:22px; right:auto; bottom:92px; }
      .section, .legal { padding: 38px 24px; }
      .module-grid, .panel-grid, .footer-grid { grid-template-columns: 1fr; }
      .photo.two { transform: none; }
    }
    @media (min-width: 961px) and (max-width: 1280px) {
      .hero { grid-template-columns: 1fr 1fr; }
      .hero-side { grid-template-columns: repeat(3, minmax(0, 1fr)); grid-column: 1 / -1; }
      .module-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    /* Desi public website redesign */
    :root {
      --desi-cream: #fff8ea;
      --desi-paper: #fffdf7;
      --desi-ink: #17213a;
      --desi-muted: #6e5f4d;
      --desi-saffron: #f97316;
      --desi-saffron-dark: #c2410c;
      --desi-green: #138a56;
      --desi-blue: #1167c9;
      --desi-yellow: #facc15;
      --desi-border: #f0dfc4;
      --desi-shadow: 0 24px 70px rgba(91, 54, 18, .14);
    }
    body {
      background:
        radial-gradient(circle at 10% 10%, rgba(250,204,21,.18), transparent 20rem),
        radial-gradient(circle at 90% 6%, rgba(19,138,86,.14), transparent 24rem),
        linear-gradient(180deg, #fff7e4 0%, #fffdf7 45%, #fff8ea 100%);
      color: var(--desi-ink);
      font-family: Inter, "Hind Siliguri", system-ui, sans-serif;
    }
    .page-frame {
      padding: 18px clamp(16px, 3vw, 36px);
    }
    .page-frame:before,
    .page-frame:after {
      width: clamp(70px, 7vw, 122px);
      top: 126px;
      bottom: 42px;
      opacity: .65;
      background:
        linear-gradient(180deg, rgba(249,115,22,.18), rgba(19,138,86,.10)),
        repeating-linear-gradient(135deg, transparent 0 12px, rgba(194,65,12,.14) 12px 14px);
    }
    .shell { max-width: 1500px; }
    .nav {
      background: rgba(255,253,247,.88);
      border-color: rgba(240,223,196,.95);
      box-shadow: 0 18px 48px rgba(120, 72, 22, .10);
    }
    .brand {
      color: var(--desi-ink);
      letter-spacing: 0;
    }
    .brand-mark {
      width: 52px;
      height: 52px;
      border-radius: 18px;
      border-color: rgba(17,103,201,.18);
      box-shadow: 0 12px 24px rgba(17,103,201,.12);
    }
    .brand-dot { background: var(--desi-saffron); }
    .nav-links { color: #766653; }
    .btn {
      min-height: 46px;
      border-radius: 16px;
      box-shadow: none;
    }
    .btn-dark {
      background: linear-gradient(135deg, #17213a, #263655);
      color: #fff;
    }
    .btn-orange {
      background: linear-gradient(135deg, var(--desi-saffron), #ef4444);
      color: #fff;
    }
    .btn-outline {
      background: #fffaf0;
      border-color: var(--desi-border);
      color: var(--desi-ink);
    }
    .pill {
      background: rgba(249,115,22,.12);
      color: var(--desi-saffron-dark);
      border: 1px solid rgba(249,115,22,.16);
      font-family: "Hind Siliguri", Inter, sans-serif;
      font-size: 13px;
      letter-spacing: 0;
    }
    .desi-hero {
      position: relative;
      display: grid;
      grid-template-columns: minmax(0, .95fr) minmax(360px, .7fr);
      gap: clamp(24px, 4vw, 52px);
      padding: clamp(34px, 5vw, 74px);
      border-radius: 34px;
      overflow: hidden;
      background:
        linear-gradient(135deg, rgba(255,248,234,.98), rgba(255,253,247,.95)),
        var(--desi-paper);
      border: 1px solid var(--desi-border);
      box-shadow: var(--desi-shadow);
    }
    .desi-hero:before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 6% 16%, rgba(249,115,22,.16), transparent 15rem),
        radial-gradient(circle at 88% 18%, rgba(17,103,201,.16), transparent 16rem),
        repeating-linear-gradient(135deg, transparent 0 28px, rgba(194,65,12,.045) 28px 30px);
      pointer-events: none;
    }
    .desi-hero > * { position: relative; z-index: 1; }
    .desi-kicker {
      display: inline-flex;
      gap: 10px;
      align-items: center;
      padding: 8px 12px;
      border-radius: 999px;
      background: #fff;
      border: 1px solid var(--desi-border);
      color: var(--desi-saffron-dark);
      font-weight: 900;
      box-shadow: 0 12px 30px rgba(120,72,22,.08);
    }
    .desi-hero h1,
    .desi-title {
      margin: 0;
      color: var(--desi-ink);
      font-family: "Hind Siliguri", Inter, sans-serif;
      font-weight: 800;
      line-height: 1.03;
      letter-spacing: 0;
    }
    .desi-hero h1 {
      margin-top: 22px;
      font-size: clamp(42px, 6.3vw, 82px);
      max-width: 850px;
    }
    .desi-hero p,
    .desi-copy {
      color: var(--desi-muted);
      font-size: 18px;
      max-width: 700px;
      margin: 18px 0 0;
    }
    .desi-hindi {
      display: block;
      margin-top: 10px;
      color: var(--desi-green);
      font-family: "Hind Siliguri", Inter, sans-serif;
      font-weight: 700;
      font-size: clamp(22px, 2.3vw, 34px);
    }
    .desi-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 30px;
    }
    .desi-proof {
      display: grid;
      grid-template-columns: repeat(3, minmax(0,1fr));
      gap: 12px;
      margin-top: 34px;
      max-width: 760px;
    }
    .desi-proof article {
      padding: 16px;
      border-radius: 20px;
      background: #fff;
      border: 1px solid var(--desi-border);
      box-shadow: 0 12px 30px rgba(120,72,22,.08);
    }
    .desi-proof strong {
      display: block;
      color: var(--desi-ink);
      font-size: 21px;
    }
    .desi-proof span {
      display: block;
      margin-top: 5px;
      color: var(--desi-muted);
      font-size: 13px;
      font-weight: 700;
    }
    .desi-phone-wrap {
      display: grid;
      place-items: center;
      min-height: 620px;
    }
    .desi-phone {
      position: relative;
      width: min(100%, 390px);
      min-height: 600px;
      padding: 18px;
      border-radius: 42px;
      background: #17213a;
      box-shadow: 0 34px 90px rgba(23,33,58,.30);
    }
    .desi-phone-screen {
      min-height: 564px;
      border-radius: 30px;
      overflow: hidden;
      background: #fff8ea;
      border: 1px solid rgba(255,255,255,.22);
    }
    .desi-app-top {
      padding: 22px;
      color: #fff;
      background: linear-gradient(135deg, var(--desi-blue), #1d4ed8 55%, var(--desi-green));
    }
    .desi-app-brand { display:flex; align-items:center; justify-content:space-between; gap:12px; font-weight:900; }
    .desi-app-brand img { width: 46px; height: 46px; border-radius: 14px; object-fit: cover; background:#fff; }
    .desi-location {
      margin-top: 18px;
      display:inline-flex;
      padding: 9px 12px;
      border-radius: 999px;
      background: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.20);
      font-weight: 800;
      font-size: 13px;
    }
    .desi-search {
      margin: -24px 18px 0;
      padding: 16px 18px;
      border-radius: 20px;
      background: #fff;
      border: 1px solid var(--desi-border);
      box-shadow: 0 14px 34px rgba(23,33,58,.14);
      color: #7b8798;
      font-weight: 700;
    }
    .desi-app-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      padding: 20px 18px;
    }
    .desi-app-tile {
      min-height: 86px;
      padding: 12px 8px;
      text-align: center;
      border-radius: 18px;
      background: #fff;
      border: 1px solid var(--desi-border);
      font-size: 12px;
      font-weight: 900;
      color: var(--desi-ink);
    }
    .desi-app-icon {
      width: 38px;
      height: 38px;
      margin: 0 auto 8px;
      border-radius: 14px;
      background: linear-gradient(135deg, rgba(249,115,22,.18), rgba(19,138,86,.15));
      display: grid;
      place-items: center;
      color: var(--desi-saffron-dark);
      font-weight: 900;
    }
    .desi-band {
      margin-top: 26px;
      padding: clamp(32px, 5vw, 64px);
      border-radius: 32px;
      background: linear-gradient(135deg, #17213a, #123c70);
      color: #fff;
      overflow: hidden;
      position: relative;
    }
    .desi-band:after {
      content: "";
      position: absolute;
      right: -90px;
      top: -90px;
      width: 280px;
      height: 280px;
      border-radius: 50%;
      background: rgba(249,115,22,.26);
    }
    .desi-band > * { position: relative; z-index: 1; }
    .desi-band .desi-title,
    .desi-band h2 { color: #fff; }
    .desi-band .desi-copy { color: #dbe7f6; }
    .desi-section {
      margin-top: 26px;
      padding: clamp(34px, 5vw, 66px);
      border-radius: 32px;
      background: var(--desi-paper);
      border: 1px solid var(--desi-border);
      box-shadow: 0 20px 58px rgba(120,72,22,.08);
    }
    .desi-title { font-size: clamp(34px, 4.6vw, 58px); max-width: 900px; }
    .desi-module-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
      margin-top: 30px;
    }
    .desi-module-card {
      min-height: 230px;
      border-radius: 26px;
      padding: 22px;
      background: #fff;
      border: 1px solid var(--desi-border);
      box-shadow: 0 14px 38px rgba(120,72,22,.08);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
      position: relative;
    }
    .desi-module-card:after {
      content: "";
      position: absolute;
      right: -28px;
      top: -28px;
      width: 94px;
      height: 94px;
      border-radius: 50%;
      background: rgba(249,115,22,.12);
    }
    .desi-module-card:nth-child(2n):after { background: rgba(19,138,86,.12); }
    .desi-module-card:nth-child(3n):after { background: rgba(17,103,201,.12); }
    .desi-module-icon {
      width: 52px;
      height: 52px;
      border-radius: 18px;
      display: grid;
      place-items: center;
      background: #fff7e4;
      border: 1px solid var(--desi-border);
      color: var(--desi-saffron-dark);
      font-weight: 900;
      position: relative;
      z-index: 1;
    }
    .desi-module-card h3 { margin: 18px 0 8px; font-size: 21px; }
    .desi-module-card p { margin: 0; color: var(--desi-muted); font-size: 14px; }
    .desi-catalog-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 1px;
      margin-top: 30px;
      overflow: hidden;
      border: 1px solid var(--desi-border);
      border-radius: 24px;
      background: var(--desi-border);
    }
    .desi-catalog-item { min-height: 180px; padding: 24px; background: var(--desi-paper); }
    .desi-catalog-item h3 { margin: 12px 0; font-size: 21px; }
    .desi-catalog-item p { color: var(--desi-muted); font-size: 14px; }
    .desi-catalog-module { color: var(--desi-saffron-dark); font-size: 11px; font-weight: 900; text-transform: uppercase; }
    .desi-subcategory-list { display: flex; flex-wrap: wrap; gap: 7px; }
    .desi-subcategory-list span { padding: 7px 10px; border-radius: 999px; background: #fff4df; border: 1px solid #f4d7ae; color: var(--desi-muted); font-size: 12px; font-weight: 800; }
    .desi-local-grid {
      display: grid;
      grid-template-columns: 1.1fr .9fr;
      gap: 18px;
      margin-top: 30px;
    }
    .desi-story,
    .desi-photo-card {
      border-radius: 28px;
      padding: 28px;
      background: #fff;
      border: 1px solid var(--desi-border);
      box-shadow: 0 14px 38px rgba(120,72,22,.08);
    }
    .desi-photo-card {
      min-height: 350px;
      background:
        linear-gradient(180deg, rgba(23,33,58,.02), rgba(23,33,58,.28)),
        url('/uploads/demo-assets/contact_sheet.jpg') center / cover;
      display: flex;
      align-items: flex-end;
      color: #fff;
      overflow: hidden;
    }
    .desi-photo-card div {
      padding: 18px;
      border-radius: 20px;
      background: rgba(23,33,58,.78);
      backdrop-filter: blur(10px);
    }
    .desi-community-grid,
    .panel-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 14px;
      margin-top: 28px;
    }
    .desi-community-card,
    .panel-link {
      border-radius: 22px;
      padding: 20px;
      background: rgba(255,255,255,.10);
      border: 1px solid rgba(255,255,255,.16);
      color: #fff;
      font-weight: 800;
    }
    .desi-community-card span,
    .panel-link span {
      display: block;
      margin-top: 8px;
      color: #dbe7f6;
      font-weight: 600;
      font-size: 13px;
    }
    .footer {
      background:
        linear-gradient(135deg, #17213a, #25395f);
      border-radius: 32px;
    }
    .legal {
      background: var(--desi-paper);
      border-color: var(--desi-border);
      box-shadow: 0 20px 58px rgba(120,72,22,.08);
    }
    .legal h1 {
      font-family: "Hind Siliguri", Inter, sans-serif;
      color: var(--desi-ink);
      font-weight: 800;
    }
    .legal p,
    .legal li { color: var(--desi-muted); }
    .notice {
      background: rgba(249,115,22,.10);
      border-color: rgba(249,115,22,.25);
    }
    @media (max-width: 960px) {
      .desi-hero,
      .desi-local-grid {
        grid-template-columns: 1fr;
      }
      .desi-phone-wrap { min-height: auto; }
      .desi-phone { width: min(100%, 360px); }
      .desi-proof,
      .desi-module-grid,
      .desi-community-grid,
      .panel-grid {
        grid-template-columns: 1fr;
      }
      .nav-actions .btn-outline { display: none; }
      .nav-actions .btn-dark { padding: 0 12px; font-size: 13px; }
    }
    @media (min-width: 961px) and (max-width: 1280px) {
      .desi-module-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .desi-catalog-grid { grid-template-columns: 1fr; }
      .desi-community-grid,
      .panel-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    /* Public website refinement: cleaner Indian local brand, no floating-box clutter */
    :root {
      --cs-blue: #0b63c7;
      --cs-blue-dark: #073b83;
      --cs-blue-soft: #eaf5ff;
      --cs-ink: #12213b;
      --cs-muted: #5f7087;
      --cs-line: #d8e8f7;
      --cs-saffron: #f97316;
      --cs-bg: #f5fbff;
    }
    body {
      background:
        linear-gradient(180deg, #f4fbff 0%, #ffffff 42%, #f7fbff 100%);
      color: var(--cs-ink);
    }
    .page-frame {
      padding: 18px clamp(16px, 3vw, 38px);
    }
    .page-frame:before,
    .page-frame:after {
      display: none;
    }
    .nav {
      border-radius: 18px;
      background: rgba(255,255,255,.94);
      border-color: var(--cs-line);
      box-shadow: 0 16px 42px rgba(7,59,131,.08);
    }
    .brand-mark {
      width: 50px;
      height: 50px;
      border-radius: 14px;
    }
    .brand-dot {
      background: var(--cs-saffron);
    }
    .btn {
      border-radius: 12px;
      min-height: 44px;
    }
    .btn-dark {
      background: var(--cs-blue-dark);
    }
    .btn-orange {
      background: var(--cs-blue);
    }
    .btn-outline {
      background: #fff;
      border-color: var(--cs-line);
      color: var(--cs-ink);
    }
    .pill,
    .desi-kicker {
      color: var(--cs-blue-dark);
      background: var(--cs-blue-soft);
      border-color: var(--cs-line);
      box-shadow: none;
      border-radius: 12px;
    }
    .desi-hero {
      grid-template-columns: minmax(0, 1fr) minmax(360px, .72fr);
      border-radius: 26px;
      background:
        linear-gradient(135deg, rgba(255,255,255,.97), rgba(234,245,255,.88)),
        #fff;
      border-color: var(--cs-line);
      box-shadow: 0 22px 60px rgba(7,59,131,.10);
    }
    .desi-hero:before {
      background:
        linear-gradient(135deg, transparent 0 55%, rgba(11,99,199,.08) 55% 100%),
        radial-gradient(circle at 12% 20%, rgba(249,115,22,.08), transparent 20rem);
    }
    .desi-hero h1,
    .desi-title {
      color: var(--cs-ink);
    }
    .desi-hindi {
      color: var(--cs-blue-dark);
    }
    .desi-hero p,
    .desi-copy {
      color: var(--cs-muted);
    }
    .desi-proof {
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0;
      border: 1px solid var(--cs-line);
      border-radius: 18px;
      overflow: hidden;
      background: #fff;
      box-shadow: none;
    }
    .desi-proof article {
      border: 0;
      border-right: 1px solid var(--cs-line);
      border-radius: 0;
      box-shadow: none;
      background: transparent;
    }
    .desi-proof article:last-child {
      border-right: 0;
    }
    .desi-phone {
      border-radius: 30px;
      background: var(--cs-blue-dark);
      box-shadow: 0 22px 54px rgba(7,59,131,.20);
    }
    .desi-phone-screen {
      border-radius: 22px;
      background: #f8fbff;
    }
    .desi-app-top {
      background: linear-gradient(135deg, var(--cs-blue), var(--cs-blue-dark));
    }
    .desi-search {
      box-shadow: none;
      border-color: var(--cs-line);
    }
    .desi-app-tile {
      border-radius: 14px;
      border-color: var(--cs-line);
      box-shadow: none;
    }
    .desi-app-icon {
      background: var(--cs-blue-soft);
      color: var(--cs-blue-dark);
    }
    .desi-section {
      border-radius: 24px;
      background: #fff;
      border-color: var(--cs-line);
      box-shadow: none;
    }
    .desi-module-grid {
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 0;
      border: 1px solid var(--cs-line);
      border-radius: 22px;
      overflow: hidden;
      background: #fff;
    }
    .desi-module-card {
      min-height: 190px;
      border: 0;
      border-right: 1px solid var(--cs-line);
      border-bottom: 1px solid var(--cs-line);
      border-radius: 0;
      box-shadow: none;
      background: #fff;
    }
    .desi-module-card:nth-child(4n) {
      border-right: 0;
    }
    .desi-module-card:nth-child(n+5) {
      border-bottom: 0;
    }
    .desi-module-card:after {
      display: none;
    }
    .desi-module-icon {
      border-color: var(--cs-line);
      background: var(--cs-blue-soft);
      color: var(--cs-blue-dark);
      border-radius: 14px;
    }
    .desi-band {
      border-radius: 24px;
      background: linear-gradient(135deg, var(--cs-blue-dark), var(--cs-blue));
    }
    .desi-band:after {
      display: none;
    }
    .desi-community-grid,
    .panel-grid {
      gap: 0;
      border: 1px solid rgba(255,255,255,.22);
      border-radius: 20px;
      overflow: hidden;
    }
    .desi-community-card,
    .panel-link {
      border: 0;
      border-right: 1px solid rgba(255,255,255,.18);
      border-radius: 0;
      background: rgba(255,255,255,.08);
    }
    .desi-community-card:last-child,
    .panel-link:last-child {
      border-right: 0;
    }
    .desi-local-grid {
      grid-template-columns: 1fr;
    }
    .desi-story,
    .desi-photo-card {
      border-radius: 20px;
      border-color: var(--cs-line);
      box-shadow: none;
    }
    .desi-photo-card {
      display: none;
    }
    .footer {
      border-radius: 24px;
      background: var(--cs-blue-dark);
    }
    @media (max-width: 960px) {
      .desi-hero {
        grid-template-columns: 1fr;
      }
      .desi-proof {
        grid-template-columns: 1fr;
      }
      .desi-proof article,
      .desi-proof article:last-child {
        border-right: 0;
        border-bottom: 1px solid var(--cs-line);
      }
      .desi-proof article:last-child {
        border-bottom: 0;
      }
      .desi-module-grid {
        grid-template-columns: 1fr;
      }
      .desi-module-card,
      .desi-module-card:nth-child(4n),
      .desi-module-card:nth-child(n+5) {
        border-right: 0;
        border-bottom: 1px solid var(--cs-line);
      }
      .desi-module-card:last-child {
        border-bottom: 0;
      }
      .desi-community-grid,
      .panel-grid {
        grid-template-columns: 1fr;
      }
      .desi-community-card,
      .panel-link {
        border-right: 0;
        border-bottom: 1px solid rgba(255,255,255,.18);
      }
      .desi-community-card:last-child,
      .panel-link:last-child {
        border-bottom: 0;
      }
    }

    /* Viewport-relevant public layout: full-width sections, no dead side gutters */
    .page-frame {
      padding: 0;
      overflow: clip;
    }
    .shell {
      width: 100%;
      max-width: none;
      margin: 0;
    }
    .nav {
      width: min(calc(100% - clamp(32px, 5vw, 96px)), 1680px);
      margin: 18px auto 0;
    }
    .desi-hero,
    .desi-section,
    .desi-band,
    .footer,
    .legal {
      width: 100%;
      border-left: 0;
      border-right: 0;
      border-radius: 0;
      margin-left: 0;
      margin-right: 0;
    }
    .desi-hero {
      min-height: calc(100vh - 98px);
      margin-top: 10px;
      padding: clamp(44px, 6vw, 92px) clamp(32px, 8vw, 150px);
      grid-template-columns: minmax(0, 1fr) minmax(360px, 440px);
      align-items: center;
      box-shadow: none;
      background:
        linear-gradient(115deg, #ffffff 0%, #f7fbff 52%, #e7f3ff 52%, #f4faff 100%);
    }
    .desi-hero:before {
      background:
        linear-gradient(90deg, rgba(11,99,199,.045) 1px, transparent 1px),
        linear-gradient(180deg, rgba(11,99,199,.035) 1px, transparent 1px);
      background-size: 44px 44px;
      mask-image: linear-gradient(90deg, #000 0%, rgba(0,0,0,.72) 55%, transparent 100%);
    }
    .desi-hero h1 {
      max-width: 820px;
      font-size: clamp(48px, 5.4vw, 88px);
    }
    .desi-hindi {
      max-width: 860px;
      font-size: clamp(24px, 2vw, 34px);
    }
    .desi-hero p {
      max-width: 760px;
    }
    .desi-proof {
      max-width: 860px;
    }
    .desi-proof.service-line {
      display: flex;
      flex-wrap: wrap;
      border: 0;
      background: transparent;
      gap: 10px;
      overflow: visible;
    }
    .desi-proof.service-line article,
    .desi-proof.service-line article:last-child {
      border: 1px solid var(--cs-line);
      border-radius: 14px;
      background: rgba(255,255,255,.82);
      min-width: 128px;
    }
    .desi-proof.service-line strong {
      font-size: 17px;
    }
    .desi-phone-wrap {
      justify-content: end;
      min-height: auto;
    }
    .desi-phone {
      width: min(100%, 390px);
      min-height: 560px;
      box-shadow: 0 26px 64px rgba(7,59,131,.20);
    }
    .desi-phone-screen {
      min-height: 524px;
    }
    .desi-section,
    .desi-band,
    .footer,
    .legal {
      padding-left: clamp(32px, 8vw, 150px);
      padding-right: clamp(32px, 8vw, 150px);
    }
    .desi-section {
      margin-top: 0;
      padding-top: clamp(54px, 6vw, 92px);
      padding-bottom: clamp(54px, 6vw, 92px);
      background: #fff;
    }
    .desi-band {
      margin-top: 0;
      padding-top: clamp(54px, 6vw, 92px);
      padding-bottom: clamp(54px, 6vw, 92px);
    }
    .desi-module-grid,
    .desi-community-grid,
    .panel-grid {
      max-width: 1540px;
    }
    .desi-local-grid {
      max-width: 1540px;
    }
    .footer {
      margin-top: 0;
    }
    @media (max-width: 960px) {
      .nav {
        width: calc(100% - 24px);
        margin-top: 12px;
      }
      .desi-hero {
        min-height: auto;
        margin-top: 12px;
        padding: 34px 20px 44px;
        grid-template-columns: 1fr;
      }
      .desi-phone-wrap {
        justify-content: center;
      }
      .desi-section,
      .desi-band,
      .footer,
      .legal {
        padding-left: 20px;
        padding-right: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="page-frame">
    <div class="shell">
      <nav class="nav" aria-label="Main navigation">
        <a class="brand" href="/">
          <span class="brand-mark"><img src="/uploads/brand/city-solutions-logo.jpeg" alt=""></span>
          <span>City Solutions<span class="brand-dot"></span></span>
        </a>
        <div class="nav-links">
          <a href="/#modules">App Services</a>
          <a href="/#community">Community</a>
          <a href="/trust-safety">Trust & Safety</a>
          <a href="/medical-compliance">Medical Compliance</a>
          <a href="/contact">Contact</a>
        </div>
        <div class="nav-actions">
          <a class="btn btn-outline" href="/#modules">Dekhiye App</a>
          <a class="btn btn-dark" href="/contact#join">Partner Baniye</a>
        </div>
      </nav>
      <?php require $viewPath; ?>
      <footer class="footer">
        <div class="footer-grid">
          <div>
            <img class="footer-logo" src="/uploads/brand/city-solutions-logo.jpeg" alt="City Solutions logo">
            <h3><?= htmlspecialchars($publicBusinessName, ENT_QUOTES) ?></h3>
            <p>One city platform for shopping, mart, services, medical orders, hotels, restaurants, and real estate.</p>
            <p><?= nl2br(htmlspecialchars($publicBusinessAddress, ENT_QUOTES)) ?><br><?= htmlspecialchars($publicSupportEmail, ENT_QUOTES) ?><?= $publicSupportPhone !== '' ? '<br>' . htmlspecialchars($publicSupportPhone, ENT_QUOTES) : '' ?></p>
          </div>
          <div>
            <h4>Company</h4>
            <ul>
              <li><a href="/about">About</a></li>
              <li><a href="/trust-safety">Trust & Safety</a></li>
              <li><a href="/contact">Contact</a></li>
            </ul>
          </div>
          <div>
            <h4>Services</h4>
            <ul>
              <li><a href="/#modules">Modules</a></li>
              <li><a href="/medical-compliance">Medical compliance</a></li>
              <li><a href="/refund-cancellation">Refunds & cancellations</a></li>
            </ul>
          </div>
          <div>
            <h4>Legal</h4>
            <ul>
              <li><a href="/privacy-policy">Privacy Policy</a></li>
              <li><a href="/terms">Terms of Use</a></li>
              <li><a href="/refund-cancellation">Refund Policy</a></li>
              <li><a href="/account-deletion">Account Deletion</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <span>© <?= date('Y') ?> <?= htmlspecialchars($publicLegalEntity, ENT_QUOTES) ?>. All rights reserved.</span>
          <span>Built for local customers, trusted businesses, and city communities.</span>
        </div>
      </footer>
    </div>
  </div>
</body>
</html>
