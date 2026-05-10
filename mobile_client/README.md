# تطبيق العميل Flutter

تطبيق Flutter للعملاء مربوط بواجهة Laravel API.

## المتطلبات

- شغل Laravel أولا على `http://127.0.0.1:8000`.
- تأكد أن API يعمل من:

```bash
http://127.0.0.1:8000/api/mobile/home
```

## التشغيل على Chrome

```bash
cd mobile_client
..\flutter_sdk\bin\flutter.bat pub get
..\flutter_sdk\bin\flutter.bat run -d chrome
```

التطبيق يستخدم تلقائيا:

```bash
http://127.0.0.1:8000/api/mobile
```

## التشغيل على محاكي Android

```bash
..\flutter_sdk\bin\flutter.bat run -d emulator --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/mobile
```

## التشغيل على موبايل حقيقي

استبدل IP بعنوان جهاز السيرفر داخل نفس الشبكة:

```bash
..\flutter_sdk\bin\flutter.bat run --dart-define=API_BASE_URL=http://192.168.1.10:8000/api/mobile
```
