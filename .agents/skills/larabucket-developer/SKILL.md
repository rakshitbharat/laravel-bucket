---
name: larabucket-developer
description: >-
  A specialized skill for developing, maintaining, and debugging the LaraBucket
  Laravel package, standalone server, admin dashboard, release pipeline, and
  shared-hosting deployment. Read this FIRST before touching anything in this repo.
---

# LaraBucket Developer Skill

## Overview

**LaraBucket** (`larabucket/laravel` on Packagist) is a self-hosted object storage system for Laravel applications. It has **two separate but tightly linked components**:

1. **The Package** (`src/`) — A Flysystem V3 adapter + server API that can be installed into any Laravel 10/11 app via Composer. It registers routes, views, migrations, and a custom filesystem disk driver.
2. **The Standalone Server** (`standalone-server/`) — A full Laravel 10 application that ships pre-installed with the package. Designed for zero-dependency deployment on shared hosting (cPanel, Plesk, Hostinger, etc).

**Live demo/production URL**: `https://blue-chamois-725380.hostingersite.com`
**Admin panel**: `https://blue-chamois-725380.hostingersite.com/admin/larabucket`
**Packagist**: `https://packagist.org/packages/larabucket/laravel`
**GitHub**: `https://github.com/rakshitbharat/laravel-bucket`

---

## Repository Structure

```
laravel-bucket/
├── src/                                    # The Laravel package source
│   ├── LaraBucketServiceProvider.php       # Registers DB, routes, views, disk driver, migrations
│   ├── Storage/
│   │   └── LaraBucketAdapter.php           # Flysystem V3 adapter — connects client Laravel app to server API via HTTP
│   ├── Models/
│   │   └── Bucket.php                      # Eloquent model for larabucket_buckets table
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php          # Admin login — returns Bearer token for dashboard session
│   │   │   ├── AdminController.php         # Serves the admin dashboard Blade view
│   │   │   ├── BucketController.php        # Admin CRUD: create/read/update/delete buckets + file browsing
│   │   │   └── StorageController.php       # Client API: upload, exists, download, delete, metadata, copy, servePublicFile
│   │   └── Middleware/
│   │       ├── AuthenticateLaraBucketAdmin.php   # Validates admin Bearer token
│   │       └── AuthenticateLaraBucketClient.php  # Validates X-API-KEY or Bearer secretKey
│   └── Database/
│       └── Migrations/
│           └── 2026_06_24_000000_create_larabucket_buckets_table.php
├── config/
│   └── larabucket.php                      # All config: server.enabled, server.disk, server.url, admin creds
├── routes/
│   └── api.php                             # ALL routes: admin API, client API, admin web view, public file serving
├── resources/
│   └── views/
│       └── dashboard.blade.php             # Premium glassmorphism admin dashboard (Tailwind CDN + Alpine.js + Lucide)
├── tests/
│   ├── Feature/                            # PHPUnit API integration tests
│   ├── Unit/                               # Flysystem adapter unit tests
│   └── test_apis.sh                        # Shell integration test script
├── standalone-server/                      # Full Laravel 10 app — the deployable server
│   ├── .env.example                        # Complete production-ready env template
│   ├── .htaccess                           # Root-level rewrite: all traffic → public/ (shared hosting magic)
│   ├── database/
│   │   └── database.sql                    # Pre-built MySQL schema dump (no migrations needed)
│   ├── routes/web.php                      # Minimal — just a welcome page. Real routes are in package's routes/api.php
│   └── vendor/                             # Pre-installed (included in ZIP)
├── .github/
│   └── workflows/
│       └── release.yml                     # CI: Packagist trigger + build & attach ZIP on tag push
├── Dockerfile                              # PHP 8.2 dev server image
├── docker-compose.yml                      # Runs web server (port 8000) + test-runner
├── start-server.sh                         # Container startup: runs migrations + php artisan serve
└── larabucket-standalone-server.zip        # Pre-built deployable ZIP (committed to repo, ~6.2MB)
```

---

## The Bucket Model (`src/Models/Bucket.php`)

Table: `larabucket_buckets`

| Column | Type | Notes |
|---|---|---|
| `name` | string | Human-readable bucket name |
| `slug` | string | URL-safe identifier used in API paths |
| `secret_key` | string | Auto-generated `sk_live_` + 32 random chars on create |
| `owner_email` | string | Reference owner email (display only) |
| `storage_limit_mb` | integer | Quota in MB |
| `size_used` | integer | Bytes used (tracked on upload/delete) |
| `is_active` | boolean | Soft-disable bucket |

The `slug` is used as the **API bucket identifier** in all client requests (e.g. `POST /api/buckets/{slug}/upload`).
The `secret_key` is the **API token** clients use to authenticate (passed as `X-API-KEY` or `Authorization: Bearer`).

---

## API Routes (all defined in `routes/api.php`)

### Admin API — Bearer token from `/api/auth/login`
```
POST   /api/auth/login                     # { email, password } → { token }
GET    /api/buckets                        # List all buckets
POST   /api/buckets                        # Create bucket
PUT    /api/buckets/{id}                   # Update bucket
DELETE /api/buckets/{id}                   # Delete bucket
GET    /api/buckets/{bucketId}/files       # List files in bucket (optional ?path= for subfolder)
POST   /api/buckets/{bucketId}/folders     # Create folder
DELETE /api/files/{fileId}                 # Delete file by ID
```

### Client API — `X-API-KEY: sk_live_...` or `Authorization: Bearer sk_live_...`
```
GET/HEAD /api/files                        # Check file exists (?path=bucket/file.ext)
POST     /api/buckets/{slug}/upload        # Upload file (multipart: file, path)
GET      /api/files/download               # Download raw file (?path=bucket/file.ext)
DELETE   /api/files                        # Delete file (?path=bucket/file.ext)
GET      /api/files/metadata               # Get file metadata (?path=bucket/file.ext)
POST     /api/files/copy                   # Copy file ({ source, destination })
```

### Web / Public
```
GET  /admin/larabucket                     # Admin dashboard SPA (Blade view)
GET  /storage/{path}                       # Public file serving (any path, served with correct MIME)
```

---

## Config (`config/larabucket.php`)

```php
'server' => [
    'enabled'        => env('LARABUCKET_SERVER_ENABLED', true),
    'disk'           => env('LARABUCKET_SERVER_DISK', 'local'),        // Use 'public' for shared hosting
    'route_prefix'   => env('LARABUCKET_SERVER_ROUTE_PREFIX', 'api'),
    'middleware'      => ['api'],
    'admin_email'    => env('LARABUCKET_ADMIN_EMAIL', 'super@admin.com'),
    'admin_password' => env('LARABUCKET_ADMIN_PASSWORD', 'password'),
    // CRITICAL: This value builds the public URL in API upload responses
    'url'            => env('LARABUCKET_SERVER_URL', env('APP_URL', 'http://localhost')),
],
```

**CRITICAL**: `server.url` is used in `StorageController::upload()` line 107:
```php
$url = config('larabucket.server.url', config('app.url')) . '/storage/' . $fullPath;
```
If `LARABUCKET_SERVER_URL` is wrong (e.g. still `localhost:8000`), the API returns wrong URLs even though files upload and serve correctly.

---

## The Flysystem Adapter (`src/Storage/LaraBucketAdapter.php`)

Used in **client** Laravel apps (not the server itself). Configured in `config/filesystems.php`:

```php
'disks' => [
    'larabucket' => [
        'driver' => 'larabucket',
        'api_url' => env('LARABUCKET_API_URL'),   // e.g. https://storage.yourdomain.com
        'bucket'  => env('LARABUCKET_BUCKET'),     // the bucket slug
        'secret'  => env('LARABUCKET_SECRET_KEY'), // sk_live_... token
    ],
],
```

The adapter sends all requests using Guzzle with:
- `Authorization: Bearer {secret}` header
- `X-Bucket: {bucket}` header

---

## Standalone Server — Production `.env` Template

This is the **canonical** production `.env` (based on the live Hostinger deployment):

```ini
APP_NAME=LaraBucket
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# ── Database (MySQL) ─────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# ── LaraBucket Server ────────────────────────
# LARABUCKET_SERVER_URL must EXACTLY match APP_URL
# This builds the public file URLs returned in API responses
LARABUCKET_SERVER_ENABLED=true
LARABUCKET_SERVER_DISK=public
LARABUCKET_ADMIN_EMAIL=super@admin.com
LARABUCKET_ADMIN_PASSWORD=password
LARABUCKET_SERVER_URL=https://your-domain.com
```

**Default admin credentials**: `super@admin.com` / `password` (always change in production).

---

## Shared Hosting Deployment — Step-by-Step

### 1. Upload & Extract
- Upload `larabucket-standalone-server.zip` to `public_html/` (or subdomain root).
- Extract in place. The root `.htaccess` transparently rewrites all traffic to `public/`.

### 2. Directory Permissions (CRITICAL)
```
storage/              → 0775 (or 0777 on restrictive hosts)
bootstrap/cache/      → 0775
```
**Without this**: file uploads fail, sessions fail, logs fail. Everything breaks silently.

### 3. Import Database
- Open phpMyAdmin → create a new database.
- Import `database/database.sql` (pre-built schema, no migrations needed).
- Fill in `DB_*` values in `.env`.

### 4. Create Storage Symlink (No SSH method)
Add to `standalone-server/routes/web.php` temporarily:
```php
Route::get('/symlink', function () {
    Illuminate\Support\Facades\Artisan::call('storage:link');
    return 'Storage symlink created!';
});
```
Visit `https://your-domain.com/symlink`, then delete the route.

### 5. Configure `.env`
- Rename `.env.example` → `.env`
- Fill all values — especially `APP_URL` and `LARABUCKET_SERVER_URL` must match exactly.

### 6. Generate App Key (No SSH method)
Run locally on dev machine:
```bash
php artisan key:generate
```
Copy the `base64:...` value from local `.env` into the server's `.env`.

### 7. Clear Config Cache (No SSH method)
If you update `.env` and changes don't take effect, the config cache is stale. Delete these files via File Manager:
```
bootstrap/cache/config.php
bootstrap/cache/services.php
bootstrap/cache/packages.php
```
OR add a temporary route:
```php
Route::get('/clear-cache-temp-xyz', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return 'Done. APP_URL is: ' . config('app.url');
});
```
**This is the #1 debugging step when env changes don't apply.**

---

## Known Bugs & Fixes

### Bug: API returns `http://localhost:8000/storage/...` instead of live domain URL
**Cause**: `LARABUCKET_SERVER_URL` (or `APP_URL`) in `.env` is wrong, OR the Laravel config cache (`bootstrap/cache/config.php`) still has the old value.
**Fix**:
1. Verify `.env` has `APP_URL=https://your-domain.com` AND `LARABUCKET_SERVER_URL=https://your-domain.com`.
2. Delete `bootstrap/cache/config.php` (and `services.php`, `packages.php`) via File Manager.
3. Re-test — the upload response JSON `url` field should return the correct domain.

### Bug: `file_put_contents(...storage/framework/sessions/...): Failed to open stream`
**Cause**: `storage/framework/sessions/` directory is missing or not writable.
**Fix**: Set `storage/` and all subdirs to `0775`. Ensure `.gitignore` placeholder files exist inside `storage/framework/sessions/`, `cache/data/`, `views/`, `logs/` so those directories are created when the ZIP is extracted.

### Bug: `Database file at path [.../database.sqlite] does not exist`
**Cause**: `DB_CONNECTION=sqlite` in `.env` — SQLite is NOT supported on shared hosting (no write access to create files).
**Fix**: Always use MySQL on shared hosting. The `.env.example` now defaults to `DB_CONNECTION=mysql`.

### Bug: GitHub release workflow fails with 403
**Cause**: `release.yml` was missing `permissions: contents: write`.
**Fix**: Added to workflow — already fixed in current `release.yml`.

### Bug: Packagist not updating on release
**Cause**: Was using deprecated `musonza/github-action-packagist` action.
**Fix**: Replaced with direct `curl` POST to Packagist API using `PACKAGIST_USERNAME` and `PACKAGIST_API_TOKEN` secrets.

### Bug: Standalone ZIP was 51MB (over GitHub's 50MB warning limit)
**Fix**: Exclude dev-only packages from ZIP:
- `psy/psysh`, `nikic/php-parser` (Tinker debugging deps)
- `laravel/tinker`, `laravel/prompts` (CLI only)
- All vendor `tests/`, `test/`, `docs/`, `doc/`, `CHANGELOG*` dirs
- Result: **6.2MB** — well under limit.

---

## Docker — Local Development

```bash
# Start web server (port 8000) + test runner
docker compose up -d

# View server logs
docker logs larabucket_web_server

# Run artisan commands inside container (use sh, not bash — image has no bash)
docker exec larabucket_web_server sh -c "cd /app/standalone-server && php artisan migrate:status"

# Run composer install inside container (use sh)
docker exec larabucket_web_server sh -c "cd /app/standalone-server && composer install --no-dev --optimize-autoloader --no-interaction"

# Run PHPUnit tests
docker compose run --rm test-runner vendor/bin/phpunit

# Stop everything
docker compose down
```

**Important**: The container uses `sh`, not `bash`. Always use `sh -c` for exec commands.

**Local server**:
- URL: `http://localhost:8000`
- Admin: `http://localhost:8000/admin/larabucket`
- Credentials: `super@admin.com` / `password`

---

## Building the Standalone ZIP

Build locally (after running `composer install --no-dev` in container):

```bash
cd /path/to/laravel-bucket

rm -f larabucket-standalone-server.zip

zip -r larabucket-standalone-server.zip standalone-server \
  -x "standalone-server/.git/*" \
  -x "standalone-server/.env" \
  -x "standalone-server/storage/app/public/*" \
  -x "standalone-server/storage/framework/cache/data/*" \
  -x "standalone-server/storage/framework/sessions/*" \
  -x "standalone-server/storage/framework/views/*" \
  -x "standalone-server/storage/logs/*" \
  -x "standalone-server/vendor/psy/*" \
  -x "standalone-server/vendor/nikic/*" \
  -x "standalone-server/vendor/laravel/tinker/*" \
  -x "standalone-server/vendor/laravel/prompts/*" \
  -x "standalone-server/vendor/*/test/*" \
  -x "standalone-server/vendor/*/tests/*" \
  -x "standalone-server/vendor/*/Tests/*" \
  -x "standalone-server/vendor/*/Test/*" \
  -x "standalone-server/vendor/*/.git/*" \
  -x "standalone-server/vendor/*/docs/*" \
  -x "standalone-server/vendor/*/doc/*" \
  -x "standalone-server/vendor/*/CHANGELOG*" \
  -x "standalone-server/vendor/*/*/CHANGELOG*"

# Re-add .gitignore placeholder files (excluded by the glob above)
zip -r larabucket-standalone-server.zip \
  standalone-server/storage/framework/cache/data/.gitignore \
  standalone-server/storage/framework/sessions/.gitignore \
  standalone-server/storage/framework/views/.gitignore \
  standalone-server/storage/logs/.gitignore
```

**Verify clean ZIP**:
```bash
# Must NOT print anything — .env should not be in ZIP
unzip -l larabucket-standalone-server.zip | grep "standalone-server/\.env$"

# Must show .env.example
unzip -l larabucket-standalone-server.zip | grep ".env.example"

# Check size
ls -lh larabucket-standalone-server.zip   # Should be ~6-7MB
```

---

## Release Process

### Automated (via GitHub Actions on tag push)
```bash
git tag -a v1.x.x -m "Release message"
git push origin v1.x.x
```
The workflow in `.github/workflows/release.yml` will:
1. Trigger Packagist update via `curl` API call.
2. Run `composer install --no-dev` in `standalone-server/`.
3. Run `php artisan vendor:publish` to publish package assets.
4. Build the optimized ZIP with all exclusions.
5. Attach ZIP to the GitHub Release via `softprops/action-gh-release@v1`.

**Required GitHub Secrets**:
- `PACKAGIST_USERNAME` — Packagist account username
- `PACKAGIST_API_TOKEN` — Packagist API token
- `GITHUB_TOKEN` — Auto-provided by Actions

### Manual (local build + commit)
```bash
# 1. Build ZIP locally (see above)
# 2. Commit everything
git add larabucket-standalone-server.zip standalone-server/.env.example standalone-server/README.md
git commit -m "release: vX.X.X description"
git push origin main

# 3. Tag
git tag -a vX.X.X -m "Release notes"
git push origin vX.X.X
```

---

## Testing Live API (Python script)

A verification script lives at:
`/Users/rakshitbharat/.gemini/antigravity/brain/18341c3c-5715-492c-afb4-25bec0a3ea00/scratch/test_upload.py`

It:
1. Creates a 1x1 pixel PNG in memory.
2. POSTs it to the live server `POST /api/buckets/{slug}/upload`.
3. Verifies HTTP 200 and that the `url` field in JSON response uses the live domain (not localhost).
4. GETs the public URL and verifies HTTP 200 and correct Content-Type.

**Live test bucket** (Hostinger demo deployment):
- Slug: `dsv`
- API Token: `sk_live_<token>` — find the real token in the Admin Dashboard at `/admin/larabucket` under the bucket's API Token column, or ask the project owner.
- Host: `https://blue-chamois-725380.hostingersite.com`

---

## Admin Dashboard Design System

The dashboard (`resources/views/dashboard.blade.php`) uses:
- **Tailwind CSS CDN** (not compiled — loaded via CDN script)
- **Alpine.js** for reactive state (bucket list, file browser, modals)
- **Lucide Icons** (CDN)
- **Background**: `#05050a` base with absolute glowing radial ambient blur circles
- **Glassmorphism**: `backdrop-filter: blur` panels with `glass-panel`, `glass-card`, `glass-input` utility classes
- **Toast alerts**: Top-right, CSS animated decay progress bar (linear width decay over 5s)
- **Storage chart**: Floating SVG vertical bars with hover tooltips
- **Micro-animations**: `hover:scale-[1.01]` on buttons, double-click to enter folders

---

## Critical Git Rules

1. The `standalone-server/.env` file is **NEVER committed** (excluded by `.gitignore` and ZIP exclusion rule).
2. `larabucket-standalone-server.zip` IS committed to `main` — it's the download artifact for users.
3. When doing a squash/history clean: `git commit --amend --no-edit && git push -f origin main`.
4. Never store API tokens or personal credentials in version control.
5. The `standalone-server/` has its own `.gitattributes` with `export-ignore` for `.github/`, `CHANGELOG.md`, `.styleci.yml`.

---

## PHP Version Compatibility

- **Package** (`larabucket/laravel`): Requires PHP `^8.1`, supports Laravel 10 and 11.
- **Standalone Server**: Requires PHP `^8.1`, runs Laravel 10.
- **Docker image**: Uses PHP `8.2`.
- **Local machine (macOS)**: May have PHP 7.4 — always use Docker or specify `php8.2` for standalone server commands. Never run `composer install` locally with system PHP 7.4 for the standalone server.

---

## .htaccess — Root Rewrite Trick

The file at `standalone-server/.htaccess` is the key to shared-hosting compatibility:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

This transparently routes all requests into `public/` so users don't need to change their hosting control panel's document root. This is what makes the server work on cPanel/Plesk/Hostinger out-of-the-box.
