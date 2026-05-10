const fs = require('fs');
const path = require('path');

const root = process.cwd();
const outDir = path.join(root, 'vercel-static');
const dataPath = path.join(root, 'resources', 'data', 'storefront-static.json');

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
      continue;
    }

    if (entry.isSymbolicLink()) {
      const real = fs.realpathSync(source);
      if (fs.statSync(real).isDirectory()) copyDir(real, target);
      else fs.copyFileSync(real, target);
      continue;
    }

    fs.copyFileSync(source, target);
  }
};

copyDir(path.join(root, 'public', 'images'), path.join(outDir, 'images'));
copyDir(path.join(root, 'public', 'build'), path.join(outDir, 'build'));
copyDir(path.join(root, 'public', 'storage'), path.join(outDir, 'storage'));

const fallback = {
  store: {
    name: 'صيدلية د. محمد رمضان',
    tagline: 'رعاية موثوقة وتسوق أسرع',
    phone: '0509095816',
  },
  hero: {
    title: 'عروض الصيدلية',
    subtitle: 'خصومات على منتجات العناية والصحة وأدوية موثوقة ومنتجات طبية.',
    image: '/images/categories/medicine-prescription.svg',
  },
  categories: [
    { name: 'الأدوية والروشتات', count: 0, image: '/images/categories/medicine-prescription.svg', url: '#' },
    { name: 'الفيتامينات والمكملات', count: 0, image: '/images/categories/vitamins-supplements.svg', url: '#' },
    { name: 'العناية بالبشرة', count: 0, image: '/images/categories/skincare.svg', url: '#' },
    { name: 'العناية بالطفل', count: 0, image: '/images/categories/baby-care.svg', url: '#' },
  ],
  products: [],
  deals: [],
  brands: ['Bioderma', 'La Roche-Posay', 'Vichy', 'Centrum', 'Mustela', 'Accu-Chek'],
};

const readData = () => {
  if (!fs.existsSync(dataPath)) return fallback;

  try {
    const exported = JSON.parse(fs.readFileSync(dataPath, 'utf8'));
    return {
      ...fallback,
      ...exported,
      store: { ...fallback.store, ...(exported.store || {}) },
      hero: { ...fallback.hero, ...(exported.hero || {}) },
      categories: exported.categories?.length ? exported.categories : fallback.categories,
      products: exported.products?.length ? exported.products : fallback.products,
      deals: exported.deals?.length ? exported.deals : exported.products?.filter((product) => product.discount_percent > 0) || [],
      brands: exported.brands?.length ? exported.brands : fallback.brands,
    };
  } catch (error) {
    console.warn(`Could not parse ${dataPath}: ${error.message}`);
    return fallback;
  }
};

const data = readData();

const esc = (value = '') => String(value)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#039;');

const money = (value) => {
  const number = Number(value || 0);
  return `${number.toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ج.م`;
};

const image = (src, fallbackSrc = '/images/placeholder.png') => esc(src || fallbackSrc);
const url = (href) => esc(href || '#');

const nav = [
  ['الأدوية', '#categories'],
  ['الفيتامينات', '#products'],
  ['المكملات', '#products'],
  ['العناية بالطفل', '#categories'],
  ['العناية بالبشرة', '#categories'],
  ['أجهزة طبية', '#categories'],
  ['السكري', '#categories'],
  ['العروض', '#deals'],
  ['كل المنتجات', '#products'],
];

const categoryCards = data.categories.slice(0, 12).map((category) => `
  <a class="category-card" href="${url(category.url)}">
    <span class="category-image"><img src="${image(category.image)}" alt="${esc(category.name)}"></span>
    <strong>${esc(category.name)}</strong>
    <small>${Number(category.count || 0).toLocaleString('ar-EG')} منتج</small>
  </a>
`).join('');

const productCard = (product) => `
  <article class="product-card">
    ${product.discount_percent ? `<span class="discount">خصم ${esc(product.discount_percent)}%</span>` : ''}
    <a class="product-media" href="${url(product.url)}">
      <img src="${image(product.image)}" alt="${esc(product.name)}" loading="lazy">
    </a>
    <div class="product-meta">${esc(product.category || 'منتج صيدلي')}</div>
    <a class="product-title" href="${url(product.url)}">${esc(product.name)}</a>
    <div class="product-row">
      <strong>${money(product.price)}</strong>
      ${product.compare_price ? `<del>${money(product.compare_price)}</del>` : ''}
    </div>
    <div class="stock">${Number(product.quantity || 0).toLocaleString('ar-EG')} متوفر</div>
    <a class="cart-button" href="${url(product.url)}">أضف للسلة</a>
  </article>
`;

const productCards = (data.products.length ? data.products : fallback.categories.map((category, index) => ({
  name: category.name,
  category: 'منتج صيدلي',
  price: 48 + (index * 21),
  quantity: 0,
  image: category.image,
  url: category.url,
}))).slice(0, 8).map(productCard).join('');

const dealCards = (data.deals.length ? data.deals : data.products.filter((product) => product.discount_percent > 0))
  .slice(0, 4)
  .map(productCard)
  .join('');

const brandCards = data.brands.slice(0, 12).map((brand) => {
  const item = typeof brand === 'string' ? { name: brand } : brand;
  return `
    <a class="brand-card" href="${url(item.url)}">
      ${item.logo ? `<img src="${image(item.logo)}" alt="${esc(item.name)}" loading="lazy">` : `<strong>${esc(item.name)}</strong>`}
    </a>
  `;
}).join('');

const appScreenshot = image(data.mobile_app?.screenshot || '/images/app-home-preview.svg');

const html = `<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${esc(data.store.name)} | متجر صيدلي</title>
  <meta name="description" content="${esc(data.store.name)} - ${esc(data.store.tagline)}">
  <style>
    :root {
      --green: #059669;
      --green-dark: #064e3b;
      --teal: #14b8a6;
      --mint: #dcfce7;
      --ink: #0f172a;
      --muted: #64748b;
      --line: #dbe7ef;
      --surface: #ffffff;
      --bg: #f1f7fa;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: "Cairo", "Tajawal", "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
      background: var(--bg);
      color: var(--ink);
    }
    a { color: inherit; text-decoration: none; }
    img { max-width: 100%; display: block; }
    .container { width: min(1240px, calc(100% - 32px)); margin-inline: auto; }
    .topbar { background: var(--green-dark); color: white; font-size: 13px; font-weight: 800; }
    .topbar .container { min-height: 34px; display: flex; align-items: center; justify-content: space-between; gap: 18px; overflow-x: auto; white-space: nowrap; }
    .header { padding: 18px 0 16px; }
    .header-grid { display: grid; grid-template-columns: 300px minmax(280px, 1fr) 230px; align-items: center; gap: 18px; }
    .brand { display: flex; align-items: center; gap: 14px; font-size: 23px; font-weight: 1000; line-height: 1.25; }
    .brand-icon { width: 58px; height: 58px; border-radius: 20px; display: grid; place-items: center; color: white; background: linear-gradient(135deg, var(--green), var(--teal)); box-shadow: 0 18px 40px rgba(5,150,105,.24); }
    .brand-icon svg { width: 30px; height: 30px; }
    .brand small { display: block; color: var(--green); font-size: 13px; font-weight: 900; margin-top: 3px; }
    .search { height: 58px; border: 1px solid var(--line); border-radius: 22px; background: white; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 18px; color: var(--muted); font-weight: 800; box-shadow: 0 12px 35px rgba(15,23,42,.05); }
    .actions { display: flex; justify-content: flex-end; gap: 10px; }
    .icon-btn { width: 50px; height: 50px; border: 1px solid var(--line); border-radius: 18px; background: white; display: grid; place-items: center; font-weight: 1000; box-shadow: 0 10px 26px rgba(15,23,42,.04); }
    .nav { display: flex; gap: 10px; padding: 0 0 18px; overflow-x: auto; scrollbar-width: none; }
    .nav a { flex: 0 0 auto; border: 1px solid var(--line); border-radius: 999px; padding: 12px 19px; background: white; color: #24324a; font-size: 15px; font-weight: 1000; transition: .2s ease; }
    .nav a:hover, .nav a:first-child { background: var(--mint); border-color: #a7f3d0; color: var(--green-dark); }
    .hero { position: relative; overflow: hidden; min-height: 438px; display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 36px; padding: 56px 42px 80px; border-radius: 36px; background: radial-gradient(circle at 20% 20%, rgba(255,255,255,.36), transparent 31%), linear-gradient(135deg, #065f46, #10b981 70%, #34d399); color: white; box-shadow: 0 28px 70px rgba(6,95,70,.18); }
    .hero h1 { margin: 0; max-width: 620px; font-size: clamp(44px, 6vw, 78px); line-height: 1.06; font-weight: 1000; letter-spacing: 0; }
    .hero p { max-width: 590px; margin: 18px 0 28px; color: rgba(255,255,255,.88); font-size: 18px; line-height: 1.9; font-weight: 800; }
    .eyebrow { display: inline-flex; padding: 7px 13px; border-radius: 999px; background: rgba(255,255,255,.16); color: inherit; font-size: 13px; font-weight: 1000; margin-bottom: 14px; }
    .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
    .button { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 22px; border-radius: 999px; border: 1px solid rgba(255,255,255,.32); font-weight: 1000; }
    .button.primary { background: white; color: var(--green-dark); border-color: white; }
    .hero-card { position: relative; min-height: 260px; border-radius: 34px; background: rgba(255,255,255,.16); padding: 32px; backdrop-filter: blur(10px); box-shadow: inset 0 0 0 1px rgba(255,255,255,.18); }
    .hero-image { height: 250px; border-radius: 30px; display: grid; place-items: center; background: linear-gradient(135deg, rgba(6,95,70,.7), rgba(52,211,153,.5)); overflow: hidden; }
    .hero-image img { width: min(80%, 360px); max-height: 230px; object-fit: contain; filter: drop-shadow(0 25px 35px rgba(0,0,0,.18)); }
    .hero-discount { position: absolute; bottom: 24px; left: 32px; width: 92px; height: 92px; border-radius: 24px; display: grid; place-items: center; background: white; color: #e11d48; font-weight: 1000; font-size: 28px; box-shadow: 0 22px 45px rgba(0,0,0,.15); }
    .trust { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: -44px; position: relative; z-index: 2; }
    .trust-card { min-height: 92px; border-radius: 22px; background: white; border: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; box-shadow: 0 18px 42px rgba(15,23,42,.08); font-weight: 1000; }
    .trust-card small { display: block; margin-top: 5px; color: var(--muted); font-weight: 800; }
    .section { padding: 74px 0 0; }
    .section-head { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
    .section-head h2 { margin: 0; font-size: clamp(32px, 4vw, 48px); line-height: 1.15; font-weight: 1000; }
    .section-head span { color: var(--green); font-weight: 1000; }
    .category-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; }
    .category-card { min-height: 184px; border-radius: 24px; background: white; border: 1px solid var(--line); padding: 18px; text-align: center; display: grid; place-items: center; align-content: center; gap: 10px; box-shadow: 0 16px 40px rgba(15,23,42,.05); transition: .22s ease; }
    .category-card:hover, .product-card:hover, .brand-card:hover { transform: translateY(-4px); box-shadow: 0 24px 55px rgba(15,23,42,.1); }
    .category-image { width: 92px; height: 92px; border-radius: 26px; display: grid; place-items: center; background: #ecfdf5; overflow: hidden; }
    .category-image img { width: 76px; height: 76px; object-fit: contain; }
    .category-card strong { font-size: 17px; font-weight: 1000; }
    .category-card small, .stock, .product-meta { color: var(--muted); font-weight: 800; }
    .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
    .product-card { position: relative; background: white; border: 1px solid var(--line); border-radius: 26px; padding: 14px; box-shadow: 0 18px 45px rgba(15,23,42,.06); transition: .22s ease; }
    .discount { position: absolute; top: 24px; left: 24px; z-index: 2; background: #e11d48; color: white; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 1000; }
    .product-media { height: 210px; display: grid; place-items: center; border-radius: 22px; background: linear-gradient(135deg, #ecfdf5, #e0f2fe); overflow: hidden; }
    .product-media img { width: 86%; height: 86%; object-fit: contain; transition: .25s ease; }
    .product-card:hover .product-media img { transform: scale(1.05); }
    .product-title { display: block; min-height: 56px; margin: 14px 4px 8px; font-size: 18px; line-height: 1.55; font-weight: 1000; }
    .product-row { display: flex; align-items: center; gap: 10px; margin: 8px 4px; }
    .product-row strong { color: #087f9a; font-size: 20px; font-weight: 1000; }
    .product-row del { color: var(--muted); font-size: 13px; }
    .cart-button { margin-top: 14px; min-height: 52px; border-radius: 16px; display: grid; place-items: center; background: var(--green); color: white; font-weight: 1000; }
    .brand-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; }
    .brand-card { min-height: 92px; border-radius: 20px; background: white; border: 1px solid var(--line); display: grid; place-items: center; padding: 16px; filter: grayscale(1); color: #666; font-size: 18px; font-weight: 1000; box-shadow: 0 14px 34px rgba(15,23,42,.04); transition: .22s ease; }
    .brand-card:hover { filter: grayscale(0); color: var(--green-dark); }
    .brand-card img { max-height: 54px; max-width: 150px; object-fit: contain; }
    .app { display: grid; grid-template-columns: 1fr 1.2fr; align-items: center; gap: 36px; border-radius: 34px; padding: 42px; background: linear-gradient(135deg, #031826, #065f46 75%, #0f766e); color: white; overflow: hidden; }
    .app h2 { margin: 0; font-size: clamp(36px, 5vw, 62px); line-height: 1.12; font-weight: 1000; }
    .phone { width: 245px; height: 420px; margin-inline: auto; border: 10px solid #0f172a; border-radius: 40px; background: #ecfdf5; overflow: hidden; box-shadow: 0 30px 70px rgba(0,0,0,.28); }
    .phone img { width: 100%; height: 100%; object-fit: cover; object-position: top; }
    .footer { margin-top: 76px; background: #073f4d; color: white; }
    .footer-grid { padding: 42px 0; display: grid; grid-template-columns: 1.2fr 1fr 1fr 1fr; gap: 20px; }
    .footer-card { min-height: 150px; border-radius: 20px; padding: 22px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.14); }
    .footer h3 { margin: 0 0 12px; font-size: 24px; }
    .footer p, .footer a { color: rgba(255,255,255,.75); font-weight: 800; line-height: 1.9; }
    .copy { border-top: 1px solid rgba(255,255,255,.14); padding: 18px 0; text-align: center; color: rgba(255,255,255,.65); font-weight: 800; }
    @media (max-width: 980px) {
      .header-grid { grid-template-columns: 1fr; }
      .actions { justify-content: flex-start; }
      .hero, .app { grid-template-columns: 1fr; }
      .trust, .products-grid { grid-template-columns: repeat(2, 1fr); }
      .category-grid, .brand-row { grid-template-columns: repeat(3, 1fr); }
      .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 620px) {
      .container { width: min(100% - 22px, 1240px); }
      .topbar .container { justify-content: flex-start; }
      .brand { font-size: 20px; }
      .search { height: 54px; font-size: 13px; }
      .hero { min-height: 410px; padding: 28px 22px 72px; border-radius: 28px; gap: 18px; }
      .hero h1 { font-size: 42px; }
      .hero p { font-size: 15px; }
      .hero-card { min-height: 190px; padding: 18px; }
      .hero-image { height: 190px; }
      .trust { grid-template-columns: repeat(2, minmax(160px, 1fr)); overflow-x: auto; }
      .category-grid, .products-grid, .brand-row { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 10px; }
      .category-card { flex: 0 0 170px; }
      .product-card { flex: 0 0 260px; }
      .brand-card { flex: 0 0 180px; }
      .footer-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="container">
      <span>شحن مجاني للطلبات فوق 500 ج.م</span>
      <span>الدعم: ${esc(data.store.phone)}</span>
      <span>توصيل خلال 24-48 ساعة</span>
      <span>English</span>
    </div>
  </div>

  <header class="header">
    <div class="container header-grid">
      <a class="brand" href="/">
        <span class="brand-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m10.5 20.5 10-10a4.95 4.95 0 0 0-7-7l-10 10a4.95 4.95 0 0 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
        </span>
        <span>${esc(data.store.name)}<small>${esc(data.store.tagline)}</small></span>
      </a>
      <div class="search">ابحث عن دواء، فيتامين، باركود أو منتج صحي <span>⌕</span></div>
      <div class="actions">
        <a class="icon-btn" href="#products">♡</a>
        <a class="icon-btn" href="#products">🛒</a>
        <a class="icon-btn" href="#products">🔔</a>
        <a class="icon-btn" href="#products">👤</a>
      </div>
    </div>
  </header>

  <nav class="container nav">
    ${nav.map(([label, href]) => `<a href="${href}">${label}</a>`).join('')}
  </nav>

  <main>
    <section class="container hero">
      <div>
        <span class="eyebrow">منتجات أصلية 100%</span>
        <h1>${esc(data.hero.title)}</h1>
        <p>${esc(data.hero.subtitle)}</p>
        <div class="hero-actions">
          <a class="button primary" href="#products">تسوق الآن</a>
          <a class="button" href="#deals">اكتشف العروض</a>
        </div>
      </div>
      <div class="hero-card">
        <div class="hero-image"><img src="${image(data.hero.image)}" alt="${esc(data.hero.title)}"></div>
        <div class="hero-discount">40%</div>
      </div>
    </section>

    <div class="container trust">
      <div class="trust-card"><span>منتجات أصلية<small>مصادر موثوقة</small></span><b>✓</b></div>
      <div class="trust-card"><span>توصيل سريع<small>خلال 24-48 ساعة</small></span><b>🚚</b></div>
      <div class="trust-card"><span>دفع آمن<small>حماية كاملة</small></span><b>💳</b></div>
      <div class="trust-card"><span>دعم 24/7<small>متابعة مستمرة</small></span><b>☎</b></div>
    </div>

    <section class="container section" id="categories">
      <div class="section-head"><div><span>تسوق أسرع</span><h2>أقسام الصيدلية</h2></div><a href="#products">كل الأقسام</a></div>
      <div class="category-grid">${categoryCards}</div>
    </section>

    ${dealCards ? `
    <section class="container section" id="deals">
      <div class="section-head"><div><span>خصومات مباشرة</span><h2>عروض اليوم</h2></div><a href="#products">كل العروض</a></div>
      <div class="products-grid">${dealCards}</div>
    </section>` : ''}

    <section class="container section" id="products">
      <div class="section-head"><div><span>اختيارات موثوقة</span><h2>منتجات مميزة</h2></div><a href="#products">كل المنتجات</a></div>
      <div class="products-grid">${productCards}</div>
    </section>

    <section class="container section">
      <div class="section-head"><div><span>شركاء موثوقون</span><h2>أشهر الماركات الطبية</h2></div></div>
      <div class="brand-row">${brandCards}</div>
    </section>

    <section class="container section">
      <div class="app">
        <div class="phone"><img src="${appScreenshot}" alt="واجهة تطبيق الصيدلية"></div>
        <div>
          <span class="eyebrow">تطبيق الصيدلية</span>
          <h2>اطلب أدويتك من الموبايل أسرع</h2>
          <p>نفس تجربة الواجهة الرئيسية مع العروض والمنتجات والتصنيفات وسلة شراء مخصصة للعملاء.</p>
          <div class="hero-actions">
            <a class="button primary" href="#">App Store</a>
            <a class="button primary" href="#">Google Play</a>
            <a class="button" href="#">QR</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer-grid">
      <div class="footer-card"><h3>${esc(data.store.name)}</h3><p>${esc(data.store.tagline)}. منصة صيدلية إلكترونية لعرض المنتجات والطلبات والعروض.</p></div>
      <div class="footer-card"><h3>روابط مفيدة</h3><p><a href="#categories">الأقسام</a><br><a href="#products">المنتجات</a><br><a href="#deals">العروض</a></p></div>
      <div class="footer-card"><h3>النشرة الإخبارية</h3><p>تابع أحدث العروض والمنتجات الصحية.</p></div>
      <div class="footer-card"><h3>اتصل بنا</h3><p>الدعم: ${esc(data.store.phone)}<br>توصيل سريع وخدمة موثوقة.</p></div>
    </div>
    <div class="container copy">© ${new Date().getFullYear()} ${esc(data.store.name)}</div>
  </footer>
</body>
</html>`;

fs.writeFileSync(path.join(outDir, 'index.html'), html);
console.log(`Built static storefront from ${fs.existsSync(dataPath) ? dataPath : 'fallback data'} into ${outDir}`);
