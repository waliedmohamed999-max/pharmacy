# صيدلية د. محمد رمضان

منصة صيدلية إلكترونية مبنية باستخدام Laravel وVite، مع واجهة متجر حديثة تعتمد على React داخل صفحة المتجر الرئيسية.

## نظرة عامة

المشروع يوفر متجر صيدلية متكامل لإدارة وعرض المنتجات الطبية، التصنيفات، البنرات، الطلبات، المخزون، نقطة البيع، الحسابات، والتقارير المالية.

الواجهة الرئيسية تم تحديثها لتجربة Ecommerce حديثة تشمل:

- تصميم عربي RTL.
- Hero slider للعروض.
- أقسام وتصنيفات الصيدلية.
- منتجات مميزة وعروض اليوم والأكثر مبيعا.
- بطاقات منتجات حديثة مع تقييمات وخصومات ومعاينة سريعة.
- Mega menu.
- Dark mode.
- Mobile bottom navigation.
- Newsletter وFooter حديث.

## التقنيات المستخدمة

- Laravel 12
- PHP 8.2+
- Vite
- TailwindCSS
- React
- Framer Motion
- Swiper
- Lucide Icons
- React Query
- Zustand
- SQLite للتشغيل المحلي الحالي
- MySQL/MariaDB مدعوم عند ضبط الإعدادات

## المتطلبات

- PHP 8.2 أو أحدث
- Composer
- Node.js و npm
- SQLite أو MySQL/MariaDB

## التشغيل المحلي

ثبت الاعتماديات:

```bash
composer install
npm install
```

انسخ ملف البيئة إذا لم يكن موجودا:

```bash
cp .env.example .env
php artisan key:generate
```

شغل migrations:

```bash
php artisan migrate
```

ابن ملفات الواجهة:

```bash
npm run build
```

شغل السيرفر:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

افتح:

```text
http://127.0.0.1:8000
```

## ملاحظات قاعدة البيانات

إعدادات `.env` الأصلية تشير إلى MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doctor
DB_USERNAME=root
DB_PASSWORD=1234
```

إذا كان MySQL غير مضبوط محليا، يمكن تشغيل المشروع مؤقتا على SQLite:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='database/database.sqlite'
php artisan serve --host=127.0.0.1 --port=8000
```

## أوامر مفيدة

تشغيل الاختبارات:

```bash
composer test
```

فحص الراوتات:

```bash
php artisan route:list
```

بناء الواجهة:

```bash
npm run build
```

تشغيل Vite أثناء التطوير:

```bash
npm run dev
```

## أقسام النظام

- المتجر العام.
- السلة والدفع.
- لوحة تحكم الإدارة.
- إدارة المنتجات والتصنيفات والبنرات.
- إدارة الطلبات والعملاء.
- إدارة المخزون والمستودعات.
- نقطة البيع POS.
- الحسابات والتقارير المالية.
- صلاحيات المستخدمين.

## بيانات الدخول الافتراضية

إذا تم تشغيل الـ seeder، يمكن استخدام:

```text
Email: admin@drpharmacy.test
Password: password
```

## بنية الواجهة الرئيسية

أهم الملفات:

```text
resources/views/store/home.blade.php
resources/js/storefront/HomePage.jsx
resources/js/storefront/store.js
resources/js/components/ui/
resources/css/app.css
```

## ملاحظات تطوير

- الصفحة الرئيسية تستخدم React mounted داخل Laravel Blade.
- باقي صفحات المتجر والإدارة ما زالت Blade.
- ملفات الإنتاج يتم إخراجها في `public/build`.
- المشروع ليس repository Git حاليا داخل هذا المسار.

