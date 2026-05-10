# Enterprise Security Audit

Date: 2026-05-10

## Executive Summary

The project was reviewed across Laravel backend, React storefront/admin assets, Flutter client, deployment files, environment defaults, uploads, API routes, authorization, dependency supply chain, and production runtime configuration.

This pass fixed the highest-risk issues that were visible in the repository without changing business behavior.

## Fixed Findings

| Severity | Area | Finding | Fix |
| --- | --- | --- | --- |
| Critical | Authorization | Admin routes were mostly protected by authentication only, allowing any signed-in user to reach sensitive ERP modules. | Added centralized `admin.access` middleware mapping admin route names to RBAC permissions. |
| High | CSRF | Login route was excluded from CSRF validation. | Removed CSRF exception from bootstrap middleware configuration. |
| High | API abuse | Mobile API had no explicit rate limit. | Added `throttle:mobile-api` with IP and phone-based limits. |
| High | Supply chain | npm audit found high vulnerabilities in Vite/Rollup/Axios/Picomatch and related packages. | Ran `npm audit fix`; audit now reports zero vulnerabilities. |
| Medium | Supply chain | Composer audit found `league/commonmark` advisories. | Updated `league/commonmark` to 2.8.2; Composer audit now clean. |
| Medium | Security headers | Missing production security headers on Laravel/Vercel/Apache paths. | Added Laravel `SecurityHeaders`, Vercel headers, and Apache headers. |
| Medium | Session defaults | `.env.example` defaulted to local/debug/insecure session settings. | Rewrote production-safe `.env.example` defaults. |
| Medium | File uploads | Product/gallery/banner/category image limits and URL handling were too loose. | Restricted image types, size, gallery count, and product image URLs to HTTPS. |
| Medium | Open redirect / unsafe link | Footer custom links accepted any URL. | Restricted footer links to `/`, `https://`, `mailto:`, and `tel:` schemes. |
| Medium | Mobile transport | Flutter defaulted to HTTP when no API URL was supplied. | Release mode now defaults to HTTPS production API and rejects non-HTTPS remote URLs. |
| Low | Docker/runtime | No secure production container scaffold. | Added Dockerfile, production compose, nginx config, and dockerignore. |

## Residual Risks

- Vercel current configuration is static-only. It cannot run the full Laravel admin/database system unless you deploy with a PHP-capable runtime or a VPS/container setup.
- MFA is not implemented yet. The auth flow is ready for RBAC but not for TOTP/WebAuthn.
- Payment gateway integration is not present in the inspected code. PCI controls must be added before real card processing.
- Mobile SSL certificate pinning is not implemented because the app currently uses `package:http`; add a pinned HTTP client before store release.
- Centralized audit logging is not fully implemented. Add immutable audit events for destructive admin actions.

## Production Checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`.
- Use HTTPS-only domain and set `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`.
- Use a non-root DB user with least privileges.
- Store secrets in the host secret manager, not Git.
- Run `php artisan migrate --force`.
- Run `php artisan config:cache route:cache view:cache`.
- Run `npm run build`.
- Configure scheduled backups for database and uploaded files.
- Enable centralized logs and alerts for 4xx/5xx spikes, failed logins, and inventory/accounting writes.
- Build Flutter release with `--dart-define=API_BASE_URL=https://your-domain.com/api/mobile`.
