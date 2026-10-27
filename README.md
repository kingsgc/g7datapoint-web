<p align="center">
  <img src="public/brand/g7-logo-horizontal.png" alt="G7DataPoint logo" width="360" />
</p>

<h1 align="center">G7DataPoint Web</h1>

<p align="center">
  Laravel backend &amp; admin panel for <a href="https://g7datapoint.com">g7datapoint.com</a> — powers the G7DataPoint mobile app, website and API.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-10-ff2d20" alt="Laravel 10" />
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777bb4" alt="PHP 8.1+" />
  <img src="https://img.shields.io/badge/Filament-3-FFC638" alt="Filament 3" />
  <img src="https://img.shields.io/badge/Livewire-3-FB70A9" alt="Livewire 3" />
  <img src="https://img.shields.io/badge/MySQL-5.7%2B-4479A1" alt="MySQL" />
  <img src="https://img.shields.io/badge/license-MIT-green" alt="License" />
</p>

## About

**G7DataPoint Web** is the server side of the G7DataPoint platform. It is a Laravel 10 application that serves:

- 🌐 **The public website / SPA** — marketing landing page plus the pre-built React front-end (served from `public/`, no Node build needed on the server)
- 📱 **The mobile app API** — token-authenticated JSON endpoints under `/api/*` (130+ routes) covering auth, wallets, purchases and webhooks
- 🛠️ **The admin panel** — a **Filament** dashboard for managing plans, pricing, transactions, users, permissions and API providers
- 💸 **Transaction engine** — airtime, data, cable TV, electricity, exam PINs, recharge/data cards, bulk SMS, transfers, withdrawals, bonuses and affiliate packages
- 🔔 **Notifications** — push (Firebase), transactional email and in-app alerts
- ⏰ **Scheduled jobs** — auto-resolution of pending transactions, queues and batch jobs

### Domain coverage

| Area | Examples |
| --- | --- |
| Purchases | Airtime (VTU), data bundles, cable TV, electricity tokens, exam PINs, recharge cards, data cards, bulk SMS |
| Wallet | Funding via bank transfer, withdrawals, transfers, bonuses, airtime-to-cash |
| Providers | VTpass, Adex and other biller APIs (`app/Models/AdexAPI`, `BillPlan`, `DataPlan`, ...) |
| Admin | Filament resources, roles & permissions (spatie/laravel-permission), Excel exports |
| Integrations | Firebase (push), Sanctum tokens, CORS, webhooks |

## Tech stack

- **Laravel 10** (PHP 8.1+) with **Sanctum** API tokens
- **Filament 3** + **Livewire 3** admin panel (with `filament-shield` permissions)
- **spatie/laravel-permission**, **maatwebsite/excel**, **kreait/laravel-firebase**
- **MySQL** / MariaDB, queues, scheduler, and a job-batch table
- **Vite** + **Tailwind** asset pipeline for Blade views; the React SPA ships pre-built in `public/static/`

## Requirements

| | |
| --- | --- |
| PHP | 8.1+ with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `bcmath` |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Web server | Apache with `mod_rewrite` and `AllowOverride All` (or nginx + PHP-FPM) |
| Composer | 2.x |

## Local setup

```bash
git clone https://github.com/kingsgc/g7datapoint-web.git
cd g7datapoint-web
composer install
npm install          # only needed if you edit Vite/Blade assets
cp .env.example .env
php artisan key:generate
```

Configure `.env` (database, `APP_URL`, `FRONT_END_URL`, API keys), then:

```bash
php artisan migrate --force
php artisan storage:link
npm run build        # optional: rebuild Vite assets
php artisan serve    # http://127.0.0.1:8000
```

> ⚠️ **Never commit `.env`** — it holds your live `APP_KEY` and database credentials. Only `.env.example` (template, no secrets) belongs in git. This repo's `.gitignore` excludes `.env`, `.env.backup`, `.env.production`, `/vendor`, `/node_modules`, `/public/build` and `storage/logs/*.log`.

### Useful artisan commands

```bash
php artisan migrate:status           # migration state
php artisan config:cache              # after any .env change run config:clear too
php artisan optimize:clear            # clear all caches
php artisan transactions:auto-resolve --minutes=20   # what cron runs every 5 min
php artisan queue:work --tries=3      # drain the queue manually
```

## Project structure

```
app/
  Filament/Admin/        # Admin panel resources, pages and widgets
  Http/Controllers/      # API/, Purchase/, APP/ controllers (webhooks, purchases, auth)
  Models/                # DataPlan, BillPlan, Airtime, Cable, Withdrawal, ...
  Services/, Jobs/,      # Business logic, queued work
  Mail/, Imports/, Policies/
routes/
  web.php                # Landing page + SPA fallback
  api.php                # Token-authenticated mobile/app API
resources/views/         # Blade views (admin, components, emails, errors)
public/                  # React SPA build, brand assets, images, .htaccess
database/migrations/     # Schema
deploy/
  HOSTING.md             # Full cPanel / shared-hosting deployment guide
  roles_permissions.sql  # Roles & permissions seed (required for admin login)
storage/                 # Logs, cache, compiled views (runtime only)
```

## Deployment

The complete cPanel / shared-hosting walkthrough — PHP version & extensions, what **not** to upload, `.env` setup, the roles/permissions SQL, cron jobs and post-deploy checks — is in **[deploy/HOSTING.md](deploy/HOSTING.md)**.

Quick version:

```bash
composer install --no-dev --optimize-autoloader --no-scripts
php artisan key:generate        # on the server, fresh key
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Cron (every minute + scheduled queue work):

```cron
* * * * * cd /path/to/app && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## Related repositories

- **[g7datapoint](https://github.com/kingsgc/g7datapoint)** — G7DataPoint mobile app (Expo / React Native)
- **[g7datapoint-web](https://github.com/kingsgc/g7datapoint-web)** — this repository (Laravel backend & admin)

## License

Private — all rights reserved.
