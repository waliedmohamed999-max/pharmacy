# Production Deployment Guide

## Laravel Backend

1. Provision PHP 8.3, MySQL 8, Redis, HTTPS, and a private writable storage volume.
2. Copy `.env.example` to `.env` and set real secrets:
   - `APP_KEY`
   - `DB_PASSWORD`
   - `REDIS_PASSWORD`
   - `ADMIN_EMAIL`
   - `ADMIN_PASSWORD`
3. Install and build:
   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. Use the nginx config in `deploy/nginx/pharmacy.conf` as the baseline.

## Docker

Use:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Set `DB_ROOT_PASSWORD` and `REDIS_PASSWORD` before running the stack.

## Flutter Customer App

Analyze:

```bash
..\flutter_sdk\bin\flutter.bat analyze
```

Build web:

```bash
..\flutter_sdk\bin\flutter.bat build web --release --dart-define=API_BASE_URL=https://your-domain.com/api/mobile
```

Build Android release:

```bash
..\flutter_sdk\bin\flutter.bat build apk --release --obfuscate --split-debug-info=build/symbols --dart-define=API_BASE_URL=https://your-domain.com/api/mobile
```

## Monitoring

- App logs: Laravel `stack` to centralized log collector.
- Metrics: request latency, failed jobs, queue length, DB slow queries.
- Alerts: failed login spikes, 403 spikes, 500 spikes, stock negative movement, failed payments.

## Backup

- MySQL daily full backup and hourly binary log or point-in-time recovery.
- Storage backup for `storage/app/public`.
- Test restore monthly.
