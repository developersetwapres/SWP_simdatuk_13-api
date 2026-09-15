# SIMDATUK API — Laravel 13

Backend API for SIMDATUK (Sistem Manajemen Data Kepegawaian), migrated from Laravel 10 onto a fresh Laravel 13 foundation.

## Database safety

The authoritative schema and data come from the imported SIMDATUK SQL database. Laravel migration files in this repository are framework/package artifacts; they are **not** the schema source of truth.

> **DO NOT RUN `php artisan migrate`, `migrate:fresh`, `db:wipe`, seeders, or any schema-rebuilding command against an imported SIMDATUK database.**

Composer lifecycle scripts do not run migrations. Deployment automation must preserve this rule.

## Required environment

Copy `.env.example` and configure deployment-specific values. At minimum verify:

- `APP_ENV=production`, `APP_DEBUG=false`, a stable `APP_KEY`, and the public HTTPS `APP_URL`;
- `DB_CONNECTION=mysql` and the exact imported SIMDATUK database name/credentials;
- the exact reverse-proxy IP addresses/CIDR ranges in `TRUSTED_PROXIES`, an ingress/network boundary that prevents clients from bypassing them, and `SANCTUM_STATEFUL_DOMAINS` when cookie authentication is used;
- SMTP transport, sender identity, and synchronous mail delivery;
- S3 bucket, region, endpoint/path-style mode, and application read/write/metadata permissions; list/delete should be granted only to an authorized smoke-test identity when required;
- SIMSDM URL/client credentials and Google reCAPTCHA secret;
- cache/session/queue choices. Initial parity defaults are file cache, file session, and synchronous queue.

Never commit `.env` or credentials.

## Safe install and deployment

```bash
composer install --no-dev --classmap-authoritative --no-interaction --no-scripts
php artisan package:discover --ansi
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan about --only=environment
php artisan route:list --path=api
php artisan schedule:list
```

No database migration or seeding command belongs in this sequence. Build frontend assets only if the deployment actually serves the optional Laravel/Vite scaffold.

Run the production scheduler every minute:

```cron
* * * * * cd /path/to/swp_simdatuk-13 && php artisan schedule:run >> /dev/null 2>&1
```

The active application mail flows are synchronous; no queue worker is required for parity while `QUEUE_CONNECTION=sync`.

## Writable paths

The web/PHP-FPM user needs write access to `storage/`, `bootstrap/cache/`, and the operating-system temporary directory used for XLSX/PDF/ZIP/template materialization. Use deployment owner/group permissions; do not make directories world-writable.

## Tests

Default suite (clone-backed tests skip safely):

```bash
php artisan test --compact
```

Authorized clone verification requires both opt-in variables and the exact guarded clone name:

```bash
RUN_SIMDATUK_MYSQL_CLONE_TESTS=1 \
SIMDATUK_CLONE_DATABASE=SWP_simdatuk_test13 \
php artisan test --compact
```

Do not point the guarded suite at production.
