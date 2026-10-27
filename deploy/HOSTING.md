# G7DataPoint — Hosting Guide

Laravel 10 backend + pre-built React SPA (Webpack bundles, **no Node build on the server**).
Written for standard cPanel / shared hosting running Apache + PHP.

> **Read section 3 before you start.** Three steps in this app are not standard Laravel
> and will silently leave you with a site that loads but has no working admin panel.

---

## 1. Server requirements

| Requirement | Needed |
|---|---|
| PHP | **8.1 or higher** (`composer.json` requires `^8.1`; 8.2 is what this was tested on) |
| Extensions | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `bcmath` (Laravel's own set; `composer.json` declares no `ext-*`, so a missing one surfaces as a fatal at runtime, not at install) |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Apache | `mod_rewrite` enabled, `AllowOverride All` on the app folder |

In cPanel: **Select PHP Version** → set 8.2 → tick the extensions above.

---

## 2. Upload the files

Upload the project folder to something like `/home/USER/g7datapoint`.

**Do NOT upload:**
- `.env` — create it fresh on the server (section 4). This matters: there is **no git
  repository** here, so `.gitignore` gives you zero protection if you drag-drop or FTP the
  whole folder. The local `.env` contains a live `APP_KEY` and database credentials.
- `node_modules/` — not needed, the SPA is already compiled into `public/static/`
- `storage/framework/{cache,sessions,views}/*` and `bootstrap/cache/*.php` — contents only,
  the folders must exist
- `public/build/` — optional; if present it's preferred by the SPA fallback, otherwise
  `public/index.html` is served

**Must be present:** `public/.htaccess` (610 bytes) — Laravel's front-controller rewrite.
Also upload `deploy/roles_permissions.sql` (section 3).

---

## 3. The three steps that are NOT standard

### 3a. Roles and permissions must be imported manually

`database/seeders/DatabaseSeeder.php` is **empty** — `php artisan db:seed` does nothing.
And `RolesAndPermissionsSeeder` only creates the `super_admin` role and attaches it to a
user named `Bekind` (id 16 in the current database). It creates **no permissions** — the
role would exist but be empty.

Meanwhile every Filament policy checks a permission like `view_any_adex::a::p::i`. With an
empty permissions table, **nobody can log into the admin panel** — it loads and then denies
everything.

The permissions exist only as rows in your database, so import them:

```bash
mysql -u DB_USER -p DB_NAME < deploy/roles_permissions.sql
```

That file contains `permissions` (470 rows), `roles` (1) and `role_has_permissions` (445),
with `DROP TABLE IF EXISTS` guards. Verified: it round-trips exactly.

### 3b. You need an admin user

The migration `2026_10_03_1655_update_admin_user` updates a user whose username is
`TechBoy`. On a genuinely empty database that user does not exist, so the migration logs
"No user matched username TechBoy" and **no admin is created**.

Pick one:
- **Import your existing users** (recommended) — dump `user` locally and import it.
  Also import `model_has_roles`, or the imported admins will have no role.
- **Start fresh** — register a user through the app or insert one, then grant the role.
  This app grants permissions **through roles only** (`model_has_permissions` is empty in
  the current database), so assigning `super_admin` is all you need:

  ```sql
  INSERT INTO model_has_roles (role_id, model_id, model_type)
  SELECT (SELECT id FROM roles WHERE name = 'super_admin'), <USER_ID>, 'App\\Models\\User';
  ```

  Confirm it worked:

  ```sql
  SELECT u.id, u.username FROM user u
  JOIN model_has_roles mr ON mr.model_id = u.id
  JOIN roles r ON r.id = mr.role_id
  WHERE r.name = 'super_admin';
  ```

### 3c. Cron is mandatory

`app/Console/Kernel.php` schedules:

```
queue:work                                    every minute
transactions:auto-resolve --minutes=20        every five minutes
```

Without cron, **queued jobs never run** and transactions never auto-resolve. Customers pay
and nothing completes. In cPanel → Cron Jobs, every minute:

```
* * * * * cd /home/USER/g7datapoint && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Use your host's real PHP path (often `/usr/local/bin/php` or `/opt/cpanel/ea-php83/root/usr/bin/php`).

---

## 4. Create `.env` on the server

Do not copy your local one. Start from the template:

```bash
cp .env.example .env
php artisan key:generate          # generates a FRESH key; do not reuse the local one
```

Then edit `.env`. The values that matter most:

```dotenv
APP_NAME="G7DataPoint"
APP_ENV=production          # NOT local — "local" disables the API origin check
APP_DEBUG=false             # never true in production
APP_URL=https://g7datapoint.com

FRONT_END_URL=https://g7datapoint.com
ALLOWED_ORIGINS=            # only if the SPA is on an extra host, comma separated

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=database   # required, the jobs table is created by migration
SESSION_DRIVER=file
CACHE_DRIVER=file
```

**`APP_URL` is the single most important value.** `App\Http\Middleware\FrontendSecure`
builds its whitelist of allowed browser origins from `APP_URL` + `FRONT_END_URL` +
`ALLOWED_ORIGINS`. If those are wrong, every SPA call to `/api/*` returns
**HTTP 403 "Unauthorized. Access denied."** and the dashboard will appear to hang.

Serve the site over **HTTPS only**, and make sure it redirects `http://` → `https://`.
Origins are matched including scheme, so an `http://` page sends an `http://` Origin and
gets 403.

Optional but recommended — rotate the shared client key (see section 7):

```dotenv
FRONTEND_SECURE_KEY=<a long random string>
```

---

## 5. Install dependencies

If your host has Composer:

```bash
composer install --no-dev --optimize-autoloader --no-scripts
```

Then create the autoloader:

```bash
composer dump-autoload --no-dev --optimize
```

If Composer is unavailable, the easiest route is to run `composer install` on your own
machine (with PHP 8.1+) and upload the resulting `vendor/` folder. **Do not** upload your
local `node_modules`; it is not used at runtime.

---

## 6. Permissions, document root, migrate

```bash
chmod -R 775 storage bootstrap/cache
```

In cPanel → **Domains → Document Root**, point it at the **`public/`** subfolder:

```
/home/USER/g7datapoint/public          <-- correct
/home/USER/g7datapoint                 <-- wrong, exposes your whole app
```

Pointing at the project root exposes `.env`, `storage/` and `database/` to the internet.

Then:

```bash
php artisan migrate --force
```

If you're importing an existing database, migrate first and import second so your data
wins; if you're starting fresh, just migrate.

`storage:link` is **not** required — nothing in this app uses `Storage::url()`.

---

## 7. Optimize (optional but recommended)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

⚠️ Once `config:cache` is on, Laravel **stops reading `.env`**. If you change `.env`
afterwards, run `php artisan optimize:clear` (or `config:clear`) or the change is ignored.
All of this app's config reads go through `config()` for exactly this reason.

---

## 8. Verify before you hand it over

```bash
php artisan about
curl -I https://g7datapoint.com/                       # expect 200
curl -I https://g7datapoint.com/auth/login             # expect 200 (SPA shell)
curl -I https://g7datapoint.com/secure/ade-2025/internal/panel   # expect 302 to /login
```

In the browser, signed out:
1. Landing page renders with the G7 logo and the animated hero
2. `/auth/login` loads the SPA (branded splash, then login form)
3. **Log in with an admin account** → the panel opens and the sidebar is populated
4. **API's → ADEX** lists your providers
5. **API's → AychiNdo Data API** shows the singleton credential row

If the panel loads but the sidebar is empty or everything is denied, section 3a did not
happen. If `/api/*` returns 403, section 4's `APP_URL` is wrong.

---

## 9. Known issues to be aware of

These are real and still open. None blocks a successful launch, but know about them:

| Issue | Impact |
|---|---|
| `FrontendSecure::myKeySecure` is a hardcoded constant in source | Anyone with the source can call the API with full access. Set `FRONTEND_SECURE_KEY` to rotate it; old clients keep working until you update them, so do this **after** launch, not before. |
| PINs and most API credentials are stored in plain text | `pin` is compared in plain text throughout the app, so hashing it would break transaction PIN auth. `aychindodata_key`/`aychindodata_secret` are stored but **not yet read by any purchase code** — the admin screen saves them, nothing consumes them yet. |
| `/install` redirects to `mobilesub.com.ng` (`routes/web.php:45,48,50`) | Those three links point at someone else's site. |
| `RechargeCardSend.php:123,135` posts to `mobilesub.ng/api/rechargepin/` | Recharge-card flow depends on a third-party endpoint. |
| `2025_10_29_133216_create_bettings_table` is recorded as run but the file is missing | Harmless now; breaks `migrate:fresh`. |
| `public/_redirects` rewrites `/*` → `/index.html` | Ignored by Apache. **If you move to Netlify or Cloudflare Pages it will shadow every Laravel route**, including `/api/*` and the panel. Don't deploy there as-is. |
| 25 permissions are granted to **no role**: all `user_user` and `virtual::account` CRUD plus `page_RestUserPassword` | Those admin sections are hidden for every user, including super_admin, because their policies check permissions nobody holds. Pre-existing, not caused by the deploy. |
| `manifest.json` PWA icons point at the old logo | Cosmetic — install icon shows the previous mark. |
| Both `adex_api` rows have `balance: NULL` | Credentials have never been successfully tested. Use **ADEX → Test Connection** before launch. |

---

## 10. Quick command reference

```bash
cd /home/USER/g7datapoint

php artisan migrate --force            # apply migrations
php artisan migrate:status            # what's applied
php artisan db:seed --force           # no-op here: DatabaseSeeder is empty
php artisan optimize:clear            # after ANY .env change, if config is cached
php artisan config:clear              # same, but just the config
php artisan cache:clear               # clears the CACHE_DRIVER store (file by default)
php artisan queue:work --tries=3      # run manually to drain the queue now
php artisan transactions:auto-resolve --minutes=20   # what cron runs every 5 min

tail -f storage/logs/laravel.log      # first place to look when something breaks
```