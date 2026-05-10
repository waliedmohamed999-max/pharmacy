# Full Laravel Deployment On Vercel

This project is now configured to run Laravel through Vercel PHP runtime instead of exporting a static storefront only.

## Required Vercel Environment Variables

Set these in Vercel Project Settings before redeploying:

```env
APP_NAME="Dr Ramadan Pharmacy"
APP_ENV=production
APP_KEY=base64:REPLACE_WITH_REAL_KEY
APP_DEBUG=false
APP_URL=https://your-vercel-domain.vercel.app

DB_CONNECTION=mysql
DB_HOST=your-external-mysql-host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_strong_password

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

ADMIN_NAME="System Administrator"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=replace_with_strong_initial_password
```

## Important Notes

- Vercel does not provide MySQL. Use an external database such as PlanetScale, Railway, Aiven, Neon MySQL-compatible service, or a VPS MySQL server.
- Run migrations against the production database once:

```bash
php artisan migrate --force
php artisan db:seed --force
```

- Uploaded files on Vercel serverless storage are not persistent. For real production uploads, switch `FILESYSTEM_DISK` to S3-compatible storage.
- For the Flutter app release build, pass:

```bash
..\flutter_sdk\bin\flutter.bat build web --release --dart-define=API_BASE_URL=https://your-domain.com/api/mobile
```

## Current Vercel Routing

- `/build/*`, `/images/*`, `/storage/*`, `/favicon.ico`, `/robots.txt` are served as static assets.
- All other paths are handled by `api/index.php`, which boots Laravel.
