# Pharmacy Client Mobile App

تطبيق Flutter للعملاء مرتبط بواجهة Laravel API:

- الرئيسية بنفس هوية المتجر.
- الأقسام والمنتجات والعروض.
- تفاصيل المنتج والصور والأسعار والمخزون.
- سلة محلية داخل التطبيق.
- إنشاء طلب عميل بالاسم ورقم الجوال والعنوان.
- تتبع طلبات العميل برقم الجوال.

## التشغيل

```bash
cd mobile_client
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/mobile
```

على جهاز حقيقي استبدل `10.0.2.2` بعنوان جهاز السيرفر داخل الشبكة، مثل:

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000/api/mobile
```

إذا كان المجلد لا يحتوي منصات Android/iOS بعد، شغل:

```bash
flutter create .
flutter pub get
```
