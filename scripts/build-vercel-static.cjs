const fs = require('fs');
const path = require('path');

const root = process.cwd();
const outDir = path.join(root, 'vercel-static');

fs.rmSync(outDir, { recursive: true, force: true });
fs.mkdirSync(outDir, { recursive: true });

const copyDir = (from, to) => {
  if (!fs.existsSync(from)) return;
  fs.mkdirSync(to, { recursive: true });
  for (const entry of fs.readdirSync(from, { withFileTypes: true })) {
    const source = path.join(from, entry.name);
    const target = path.join(to, entry.name);
    if (entry.isDirectory()) {
      copyDir(source, target);
    } else {
      fs.copyFileSync(source, target);
    }
  }
};

copyDir(path.join(root, 'public', 'images'), path.join(outDir, 'images'));
copyDir(path.join(root, 'public', 'build'), path.join(outDir, 'build'));

const html = String.raw`<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>صيدلية د. محمد رمضان | متجر صيدلي</title>
  <meta name="description" content="واجهة متجر صيدلية د. محمد رمضان للتسوق الصحي والمنتجات الطبية.">
  <style>
    :root {
      --green: #07845f;
      --green-dark: #064e3b;
      --mint: #dffbf0;
      --teal: #14b8a6;
      --ink: #0f172a;
      --muted: #64748b;
      --line: #dbe7ef;
      --bg: #f3f8fb;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Cairo", "Tajawal", "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
      background: var(--bg);
      color: var(--ink);
    }
    a { color: inherit; text-decoration: none; }
    .top {
      background: var(--green-dark);
      color: white;
      font-size: 13px;
      font-weight: 800;
    }
    .top-inner, .header, .nav, .section, .hero, .footer-inner {
      width: min(1240px, calc(100% - 32px));
      margin: 0 auto;
    }
    .top-inner {
      min-height: 34px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      white-space: nowrap;
      overflow-x: auto;
    }
    .header {
      display: grid;
      grid-template-columns: 280px minmax(260px, 1fr) 240px;
      align-items: center;
      gap: 18px;
      padding: 18px 0;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 1000;
      font-size: 22px;
    }
    .brand-icon {
      width: 56px;
      height: 56px;
      display: grid;
      place-items: center;
      border-radius: 18px;
      background: linear-gradient(135deg, var(--green), var(--teal));
      color: white;
      font-size: 28px;
      box-shadow: 0 18px 40px rgba(7,132,95,.24);
    }
    .brand small {
      display: block;
      margin-top: 4px;
      color: var(--green);
      font-size: 13px;
      font-weight: 900;
    }
    .search {
      display: flex;
      align-items: center;
      gap: 10px;
      min-height: 58px;
      border: 1px solid var(--line);
      border-radius: 22px;
      background: white;
      padding: 0 16px;
      box-shadow: 0 10px 28px rgba(15,23,42,.04);
      color: var(--muted);
      font-weight: 800;
    }
    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .icon-btn {
      width: 48px;
      height: 48px;
      display: grid;
      place-items: center;
      border: 1px solid var(--line);
      border-radius: 17px;
      background: white;
      font-size: 20px;
      font-weight: 900;
    }
    .nav {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0 0 18px;
      overflow-x: auto;
    }
    .nav a {
      flex: 0 0 auto;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: white;
      padding: 11px 17px;
      color: #24324a;
      font-size: 14px;
      font-weight: 1000;
    }
    .nav a:first-child {
      background: var(--mint);
      border-color: #a7f3d0;
      color: var(--green-dark);
    }
    .hero {
      position: relative;
      overflow: hidden;
      min-height: 440px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      align-items: center;
      gap: 36px;
      margin-top: 4px;
      padding: 54px 42px 72px;
      border-radius: 36px;
      background: radial-gradient(circle at 20% 20%, rgba(255,255,255,.35), transparent 30%),
        linear-gradient(135deg, #065f46, #10b981 72%, #34d399);
      color: white;
      box-shadow: 0 28px 70px rgba(6,95,70,.18);
    }
    .hero h1 {
      margin: 0;
      max-width: 580px;
      font-size: clamp(42px, 6vw, 76px);
      line-height: 1.05;
      font-weight: 1000;
      letter-spacing: 0;
    }
    .hero p {
      max-width: 520px;
      margin: 18px 0 28px;
      color: rgba(255,255,255,.86);
      font-size: 18px;
      line-height: 1.9;
      font-weight: 800;
    }
    .hero-card {
      position: relative;
      min-height: 230px;
      border-radius: 34px;
      background: rgba(255,255,255,.16);
      padding: 32px;
      backdrop-filter: blur(10px);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
    }
    .product-visual {
      height: 210px;
      border-radius: 32px;
      background: linear-gradient(135deg, rgba(6,95,70,.7), rgba(52,211,153,.5));
      display: grid;
      place-items: center;
    }
    .pack {
      width: 165px;
      height: 130px;
      border-radius: 20px;
      background: white;
      position: relative;
      box-shadow: 0 24px 45px rgba(0,0,0,.16);
    }
    .pack:before, .pack:after {
      content: "";
      position: absolute;
      right: 24px;
      left: 24px;
      height: 13px;
      border-radius: 999px;
      background: var(--green);
    }
    .pack:before { top: 32px; }
    .pack:after { top: 58px; width: 72px; background: #67e8f9; }
    .discount {
      position: absolute;
      right: 30px;
      bottom: 18px;
      border-radius: 24px;
      background: white;
      color: #e11d48;
      padding: 13px 18px;
      text-align: center;
      font-weight: 1000;
      box-shadow: 0 16px 34px rgba(15,23,42,.16);
    }
    .btns {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .btn {
      border: 0;
      border-radius: 999px;
      padding: 14px 24px;
      font-weight: 1000;
      font-size: 15px;
      cursor: pointer;
    }
    .btn.primary { background: white; color: var(--green-dark); }
    .btn.ghost { background: rgba(255,255,255,.15); color: white; border: 1px solid rgba(255,255,255,.35); }
    .badges {
      width: min(1160px, calc(100% - 64px));
      margin: -42px auto 0;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      position: relative;
      z-index: 2;
    }
    .badge, .card, .brand-tile, .concern, .review {
      background: white;
      border: 1px solid var(--line);
      border-radius: 24px;
      box-shadow: 0 18px 40px rgba(15,23,42,.06);
    }
    .badge {
      display: flex;
      align-items: center;
      gap: 14px;
      min-height: 76px;
      padding: 16px;
      font-weight: 1000;
    }
    .badge span {
      width: 44px;
      height: 44px;
      display: grid;
      place-items: center;
      border-radius: 16px;
      background: var(--mint);
      color: var(--green-dark);
    }
    .section {
      padding: 54px 0 0;
    }
    .section-head {
      display: flex;
      justify-content: space-between;
      align-items: end;
      gap: 16px;
      margin-bottom: 20px;
    }
    .eyebrow {
      color: var(--green);
      font-size: 13px;
      font-weight: 1000;
    }
    h2 {
      margin: 4px 0 0;
      font-size: clamp(28px, 3.2vw, 42px);
      line-height: 1.1;
      font-weight: 1000;
    }
    .grid {
      display: grid;
      gap: 14px;
    }
    .categories { grid-template-columns: repeat(6, 1fr); }
    .products { grid-template-columns: repeat(4, 1fr); }
    .concerns { grid-template-columns: repeat(3, 1fr); }
    .category {
      min-height: 174px;
      padding: 16px;
      text-align: center;
    }
    .category img {
      width: 92px;
      height: 92px;
      object-fit: contain;
      margin: 0 auto 12px;
    }
    .category strong, .product strong, .concern strong {
      display: block;
      font-size: 16px;
      font-weight: 1000;
      line-height: 1.35;
    }
    .category small, .product small, .concern small, .review p {
      color: var(--muted);
      font-weight: 800;
    }
    .product {
      overflow: hidden;
    }
    .product-image {
      height: 170px;
      margin: 14px;
      border-radius: 22px;
      background: linear-gradient(135deg, #eefdf6, #e0f2fe);
      display: grid;
      place-items: center;
    }
    .product-body { padding: 0 18px 18px; }
    .price {
      margin-top: 14px;
      color: #04799b;
      font-size: 20px;
      font-weight: 1000;
    }
    .add {
      margin-top: 14px;
      display: block;
      width: 100%;
      border-radius: 16px;
      background: var(--green);
      color: white;
      padding: 12px;
      text-align: center;
      font-weight: 1000;
    }
    .brand-row {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 14px;
    }
    .brand-tile {
      height: 92px;
      display: grid;
      place-items: center;
      color: #5b6472;
      font-size: 18px;
      font-weight: 1000;
      filter: grayscale(1);
      transition: .2s ease;
    }
    .brand-tile:hover { filter: grayscale(0); color: var(--green); transform: translateY(-2px); }
    .concern {
      min-height: 154px;
      padding: 26px;
    }
    .concern-icon {
      width: 56px;
      height: 56px;
      display: grid;
      place-items: center;
      border-radius: 20px;
      background: var(--mint);
      color: var(--green);
      font-size: 28px;
      margin-bottom: 20px;
    }
    .reviews {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }
    .review { padding: 22px; }
    .stars { color: #f59e0b; letter-spacing: 2px; margin: 12px 0; }
    .app-banner {
      margin-top: 54px;
      border-radius: 34px;
      padding: 34px;
      display: grid;
      grid-template-columns: 1.2fr .8fr;
      gap: 20px;
      align-items: center;
      background: linear-gradient(135deg, #062f2a, #0f766e);
      color: white;
      overflow: hidden;
    }
    .phone {
      width: 210px;
      height: 360px;
      margin: auto;
      border: 10px solid #020617;
      border-radius: 42px;
      background: #020617;
      padding: 8px;
      box-shadow: 0 28px 60px rgba(0,0,0,.24);
      overflow: hidden;
    }
    .phone-screen {
      height: 100%;
      overflow: hidden;
      border-radius: 32px;
      background: #f4f8fb;
      color: #0f172a;
      direction: rtl;
    }
    .phone-top {
      padding: 16px 12px 12px;
      background: linear-gradient(135deg, #065f46, #10b981);
      color: white;
    }
    .phone-brand {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 10px;
      font-weight: 1000;
    }
    .phone-brand span:first-child {
      width: 28px;
      height: 28px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,.16);
    }
    .phone-search {
      margin-top: 10px;
      border-radius: 16px;
      background: white;
      color: #64748b;
      padding: 8px 10px;
      font-size: 8px;
      font-weight: 900;
    }
    .phone-body { padding: 10px; }
    .phone-hero {
      border-radius: 24px;
      background: linear-gradient(135deg, #065f46, #10b981 70%, #34d399);
      color: white;
      padding: 12px;
      min-height: 118px;
    }
    .phone-hero small { font-size: 7px; font-weight: 1000; opacity: .85; }
    .phone-hero strong { display: block; margin-top: 4px; font-size: 18px; line-height: 1.08; font-weight: 1000; }
    .phone-hero p { margin: 4px 0 0; font-size: 7px; font-weight: 800; color: rgba(255,255,255,.82); }
    .phone-product-pack {
      width: 54px;
      height: 42px;
      margin: 10px auto 0;
      border-radius: 12px;
      background: white;
      position: relative;
      box-shadow: 0 12px 24px rgba(0,0,0,.14);
    }
    .phone-product-pack:before,
    .phone-product-pack:after {
      content: "";
      position: absolute;
      right: 12px;
      height: 5px;
      border-radius: 99px;
    }
    .phone-product-pack:before { top: 12px; left: 12px; background: #047857; }
    .phone-product-pack:after { top: 23px; width: 24px; background: #67e8f9; }
    .phone-badges {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 7px;
      margin-top: 8px;
    }
    .phone-badges div,
    .phone-cat,
    .phone-mini-product {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 16px rgba(15,23,42,.06);
    }
    .phone-badges div { padding: 8px; font-size: 8px; font-weight: 1000; }
    .phone-title { margin: 10px 0 7px; font-size: 12px; font-weight: 1000; }
    .phone-cats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; }
    .phone-cat { padding: 7px; text-align: center; font-size: 7px; font-weight: 1000; }
    .phone-cat img { width: 30px; height: 30px; object-fit: contain; display: block; margin: 0 auto 4px; }
    .phone-products { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; margin-top: 8px; }
    .phone-mini-product { padding: 7px; font-size: 7px; font-weight: 1000; }
    .phone-mini-product div:first-child { height: 34px; border-radius: 12px; background: #ecfdf5; display: grid; place-items: center; color: #047857; font-size: 18px; }
    .phone-mini-product span { display: block; margin-top: 3px; color: #04799b; font-size: 8px; }
    .footer {
      margin-top: 64px;
      background: #083d52;
      color: white;
    }
    .footer-inner {
      padding: 38px 0;
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr 1fr;
      gap: 18px;
    }
    .footer-card {
      border-radius: 24px;
      background: rgba(255,255,255,.1);
      padding: 20px;
      border: 1px solid rgba(255,255,255,.14);
    }
    .footer h3, .footer h4 { margin: 0 0 12px; font-weight: 1000; }
    .footer p, .footer li { color: rgba(255,255,255,.76); font-weight: 700; line-height: 1.8; }
    @media (max-width: 980px) {
      .header { grid-template-columns: 1fr; }
      .actions { justify-content: start; }
      .hero { grid-template-columns: 1fr 1fr; padding: 32px 20px 64px; gap: 16px; min-height: 380px; }
      .hero h1 { font-size: clamp(34px, 9vw, 52px); }
      .hero p { font-size: 14px; }
      .badges { display: flex; overflow-x: auto; width: calc(100% - 28px); }
      .badge { min-width: 220px; }
      .categories { grid-template-columns: repeat(2, 1fr); }
      .products { grid-template-columns: repeat(2, 1fr); }
      .concerns, .reviews, .app-banner, .footer-inner { grid-template-columns: 1fr; }
      .brand-row { display: flex; overflow-x: auto; }
      .brand-tile { min-width: 150px; }
    }
    @media (max-width: 560px) {
      .hero { grid-template-columns: 1fr; }
      .hero-card { min-height: 190px; }
      .product-visual { height: 170px; }
      .products { grid-template-columns: 1fr; }
      .top-inner, .header, .nav, .section, .hero, .footer-inner { width: min(100% - 20px, 1240px); }
    }
  </style>
</head>
<body>
  <div class="top">
    <div class="top-inner">
      <span>شحن مجاني للطلبات فوق 500 ج.م</span>
      <span>الدعم: 0509095816</span>
      <span>توصيل خلال 24-48 ساعة</span>
      <span>English</span>
    </div>
  </div>

  <header class="header">
    <a class="brand" href="/">
      <span class="brand-icon">💊</span>
      <span>صيدلية د. محمد رمضان<small>رعاية موثوقة وتسوق أسرع</small></span>
    </a>
    <div class="search">🔎 ابحث عن دواء، فيتامين، باركود أو منتج صحي</div>
    <div class="actions">
      <span class="icon-btn">♡</span>
      <span class="icon-btn">🔔</span>
      <span class="icon-btn">🛒</span>
      <span class="icon-btn">👤</span>
    </div>
  </header>

  <nav class="nav">
    <a href="#">الأدوية</a>
    <a href="#">الفيتامينات</a>
    <a href="#">المكملات</a>
    <a href="#">العناية بالطفل</a>
    <a href="#">العناية بالبشرة</a>
    <a href="#">العناية بالشعر</a>
    <a href="#">أجهزة طبية</a>
    <a href="#">السكري</a>
    <a href="#">العروض</a>
    <a href="#">كل المنتجات</a>
  </nav>

  <main>
    <section class="hero">
      <div>
        <strong>منتجات أصلية 100%</strong>
        <h1>عروض الصيدلية</h1>
        <p>خصومات على منتجات العناية والصحة، أدوية موثوقة، فيتامينات، مستلزمات طبية، وتجربة شراء سريعة من أي جهاز.</p>
        <div class="btns">
          <a class="btn primary" href="#products">تسوق الآن</a>
          <a class="btn ghost" href="#deals">اكتشف العروض</a>
        </div>
      </div>
      <div class="hero-card">
        <div class="product-visual"><div class="pack"></div></div>
        <div class="discount"><small>خصم حتى</small><br><span style="font-size:32px">40%</span></div>
      </div>
    </section>

    <div class="badges">
      <div class="badge"><span>✓</span><div>منتجات أصلية<br><small>مصادر موثوقة</small></div></div>
      <div class="badge"><span>🚚</span><div>توصيل سريع<br><small>خلال 24-48 ساعة</small></div></div>
      <div class="badge"><span>💳</span><div>دفع آمن<br><small>حماية كاملة</small></div></div>
      <div class="badge"><span>☎</span><div>دعم 24/7<br><small>متابعة مستمرة</small></div></div>
    </div>

    <section class="section">
      <div class="section-head"><div><div class="eyebrow">تسوق أسرع</div><h2>أقسام الصيدلية</h2></div><a class="eyebrow" href="#">كل الأقسام</a></div>
      <div class="grid categories">
        <div class="card category"><img src="/images/categories/medicine-prescription.svg" alt=""><strong>الأدوية والروشتات</strong><small>منتجات طبية</small></div>
        <div class="card category"><img src="/images/categories/vitamins-supplements.svg" alt=""><strong>الفيتامينات والمكملات</strong><small>صحة يومية</small></div>
        <div class="card category"><img src="/images/categories/skin-care.svg" alt=""><strong>العناية بالبشرة</strong><small>حلول طبية</small></div>
        <div class="card category"><img src="/images/categories/mother-baby.svg" alt=""><strong>الأم والطفل</strong><small>احتياجات الأسرة</small></div>
        <div class="card category"><img src="/images/categories/medical-devices.svg" alt=""><strong>أجهزة ومستلزمات طبية</strong><small>قياس ومتابعة</small></div>
        <div class="card category"><img src="/images/categories/diabetes-pressure.svg" alt=""><strong>السكري والضغط</strong><small>متابعة منزلية</small></div>
      </div>
    </section>

    <section id="products" class="section">
      <div class="section-head"><div><div class="eyebrow">اختيارات موثوقة</div><h2>منتجات مميزة</h2></div></div>
      <div class="grid products">
        ${[1,2,3,4].map((item) => `
          <div class="card product">
            <div class="product-image"><div class="pack" style="transform:scale(.75)"></div></div>
            <div class="product-body">
              <small>متوفر الآن</small>
              <strong>منتج صيدلي مميز ${item}</strong>
              <div class="price">${(48 + item * 21).toFixed(2)} ج.م</div>
              <a class="add" href="#">أضف للسلة</a>
            </div>
          </div>
        `).join('')}
      </div>
    </section>

    <section id="deals" class="section">
      <div class="section-head"><div><div class="eyebrow">شركاء موثوقون</div><h2>أشهر الماركات الطبية</h2></div></div>
      <div class="brand-row">
        ${['Bioderma','La Roche-Posay','Vichy','Centrum','Mustela','Accu-Chek'].map((brand) => `<div class="brand-tile">${brand}</div>`).join('')}
      </div>
    </section>

    <section class="section">
      <div class="section-head"><div><div class="eyebrow">تسوق حسب احتياجك</div><h2>تسوق حسب الاحتياج</h2></div></div>
      <div class="grid concerns">
        <div class="concern"><div class="concern-icon">💊</div><strong>المناعة</strong><small>دعم يومي لصحة أقوى</small></div>
        <div class="concern"><div class="concern-icon">🩺</div><strong>السكري</strong><small>قياس ومتابعة ومنتجات أساسية</small></div>
        <div class="concern"><div class="concern-icon">❤</div><strong>ضغط الدم</strong><small>أجهزة ومنتجات متابعة منزلية</small></div>
      </div>
    </section>

    <section class="section">
      <div class="section-head"><div><div class="eyebrow">آراء العملاء</div><h2>ثقة يومية من عملائنا</h2></div></div>
      <div class="reviews">
        <div class="review"><strong>أحمد</strong><div class="stars">★★★★★</div><p>تجربة شراء ممتازة والتوصيل كان سريع جدا.</p></div>
        <div class="review"><strong>منى</strong><div class="stars">★★★★★</div><p>المنتجات وصلت مغلفة ونظيفة والأسعار واضحة.</p></div>
        <div class="review"><strong>كريم</strong><div class="stars">★★★★★</div><p>واجهة سهلة والعروض واضحة. طلبت في دقائق.</p></div>
      </div>
    </section>

    <section class="section">
      <div class="app-banner">
        <div>
          <div class="eyebrow" style="color:#a7f3d0">تطبيق العملاء</div>
          <h2>تسوق من الموبايل بسهولة</h2>
          <p style="color:rgba(255,255,255,.78);font-weight:800;line-height:1.9">تطبيق Flutter للعميل جاهز داخل المشروع ومربوط بالمنتجات والطلبات والواجهة.</p>
          <div class="btns"><a class="btn primary" href="#">App Store</a><a class="btn ghost" href="#">Google Play</a></div>
        </div>
        <div class="phone">
          <div class="phone-screen">
            <div class="phone-top">
              <div class="phone-brand"><span>💊</span><span>صيدلية د. محمد رمضان<br><small>رعاية موثوقة وتسوق أسرع</small></span></div>
              <div class="phone-search">🔎 ابحث عن دواء أو منتج صحي</div>
            </div>
            <div class="phone-body">
              <div class="phone-hero">
                <small>منتجات أصلية 100%</small>
                <strong>عروض الصيدلية</strong>
                <p>خصومات على منتجات العناية والصحة</p>
                <div class="phone-product-pack"></div>
              </div>
              <div class="phone-badges"><div>✓ منتجات أصلية</div><div>🚚 توصيل سريع</div></div>
              <div class="phone-title">أقسام الصيدلية</div>
              <div class="phone-cats">
                <div class="phone-cat"><img src="/images/categories/medicine-prescription.svg" alt="">الأدوية</div>
                <div class="phone-cat"><img src="/images/categories/skin-care.svg" alt="">البشرة</div>
                <div class="phone-cat"><img src="/images/categories/mother-baby.svg" alt="">الطفل</div>
              </div>
              <div class="phone-products">
                <div class="phone-mini-product"><div>💊</div>منتج صيدلي<span>48.00 ج.م</span></div>
                <div class="phone-mini-product"><div>💊</div>عرض مميز<span>69.00 ج.م</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-card"><h3>صيدلية د. محمد رمضان</h3><p>متجر صيدلي حديث يدمج التجارة الإلكترونية وإدارة الصيدلية في منصة واحدة.</p></div>
      <div class="footer-card"><h4>روابط مفيدة</h4><p>الأقسام<br>العروض<br>المنتجات<br>تواصل معنا</p></div>
      <div class="footer-card"><h4>الدعم</h4><p>0509095816<br>توصيل خلال 24-48 ساعة<br>دعم مستمر</p></div>
      <div class="footer-card"><h4>المدفوعات</h4><p>دفع آمن<br>منتجات أصلية<br>سياسات واضحة</p></div>
    </div>
  </footer>
</body>
</html>`;

fs.writeFileSync(path.join(outDir, 'index.html'), html, 'utf8');
