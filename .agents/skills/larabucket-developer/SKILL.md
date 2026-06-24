---
name: larabucket-developer
description: >-
  A specialized skill for developing, maintaining, and debugging the LaraBucket Laravel package, server database, routes, and admin dashboard.
---

# LaraBucket Developer Skill

## Overview
This skill provides comprehensive context, architectural specifications, testing instructions, and UI guidelines for the **LaraBucket** Laravel package. Future AI agents must refer to this skill to understand the project structure, design guidelines, server environments, and database schemas.

## Project Structure
The repository is structured as a self-contained Laravel package (`larabucket/laravel`) serving as both a Flysystem-compatible client adapter and a database-backed server API.

```
laravel-bucket/
├── src/
│   ├── LaraBucketServiceProvider.php  # Registers database, routes, views, and custom Flysystem driver
│   ├── Storage/
│   │   └── LaraBucketAdapter.php      # Flysystem V3 filesystem adapter connecting client to host API
│   ├── Models/
│   │   └── Bucket.php                 # Eloquent model representing storage namespaces
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php     # Admin panel credentials authentication
│   │   │   ├── BucketController.php   # Admin CRUD endpoints for buckets and file browsing
│   │   │   └── StorageController.php  # Client file upload, metadata, copy, delete, and public downloading
│   │   └── Middleware/
│   │       ├── AuthenticateLaraBucketAdmin.php   # Verifies admin dashboard token
│   │       └── AuthenticateLaraBucketClient.php  # Verifies client API credentials (X-API-KEY)
│   └── Database/
│       └── Migrations/
│           └── 2026_06_24_000000_create_larabucket_buckets_table.php
├── config/
│   └── larabucket.php                 # Exposes server config, disk definition, and admin credentials
├── routes/
│   └── api.php                        # Unified route definition (Admin Dashboard, Client API, and Public Storage)
├── resources/
│   └── views/
│       └── dashboard.blade.php        # Premium glassmorphism Tailwind/Alpine.js management console view
├── tests/
│   ├── Feature/                       # API integration test suites
│   ├── Unit/                          # Flysystem adapter test suites
│   └── test_apis.sh                   # Integration test shell script
├── Dockerfile                         # Server build instructions
├── docker-compose.yml                 # Runs isolated tests and serves dashboard
└── start-server.sh                    # Container startup script (migrations, serve)
```

## Local Development Server
The local server runs in an isolated container using SQLite.
- **Port Mapping**: `http://localhost:8000`
- **Admin Panel URL**: `http://localhost:8000/admin/larabucket`
- **Default Credentials**: Email: `super@admin.com` | Password: `password`
- **Database File**: `/app/database.sqlite` (mounted from host)
- **Active Disk**: `public` (mapped to `vendor/orchestra/testbench-core/laravel/storage/app/public`)

### Docker Commands
- **Start Server**: `./start-server.sh` or `docker compose up -d`
- **Stop Server**: `docker compose down`
- **Check Server Logs**: `docker logs larabucket_web_server`
- **Artisan Operations**: Run commands via Testbench inside container, e.g. `docker exec larabucket_web_server vendor/bin/testbench migrate:status`

## API Routing & Public Serving
1. **Public File Access**: Files stored inside LaraBucket are accessible directly at `/storage/{path}` where `path` is the unencoded file path (e.g. `test-bucket/dummy.png`). The request is intercepted by `StorageController@servePublicFile` and served with the correct MIME type.
2. **Client APIs**: Authenticated using the `X-API-KEY` or `Authorization: Bearer <secretKey>` header:
   - `GET /api/files`: Check if file exists.
   - `POST /api/buckets/{slug}/upload`: Upload file (multipart form data).
   - `GET /api/files/download?path={path}`: Read raw file contents.
   - `DELETE /api/files?path={path}`: Delete file.
3. **Admin APIs**: Authenticated using `Authorization: Bearer <adminToken>`:
   - `GET /api/buckets/{id}/files`: List all files/folders under directory path.
   - `POST /api/buckets/{id}/folders`: Create a new folder.

## Test Guidelines
1. **PHPUnit Automated Tests**:
   - Run in container: `docker compose run --rm test-runner vendor/bin/phpunit`
   - Run locally (requires PHP/Composer): `vendor/bin/phpunit`
2. **Shell API Integration Test**:
   - Run: `bash tests/test_apis.sh`
   - Validates admin authentication, bucket creation, client file uploads, files listing, and file serving.

## Admin Dashboard Design System
The dashboard uses dynamic Tailwind CSS, Alpine.js, and Lucide icons.
- **Background**: Absolute glowing radial ambient gradients (`#05050a` base, blur circles).
- **Glassmorphism**: Backdrop blur elements using classes:
  - `glass-panel`: saturated blur with transparent outline.
  - `glass-card`: subtle card with light hover transition.
  - `glass-input`: dark glass background and glowing focus ring.
- **Toasts Alert**: Hovering at the top right of the viewport with a CSS animated decay progress bar (linear width decay over 5s).
- **Chart Layout**: Floating SVG vertical bars with interactive hover data tooltips.
- **Micro-animations**: Smooth hover transitions, scale-up states on buttons (`hover:scale-[1.01]`), and double-click actions on folder elements to enter directories.

## Standalone Server ZIP & Shared Hosting Deployment
1. **Ready-To-Use Server ZIP**: The repository includes a pre-packaged **`larabucket-standalone-server.zip`** at the root. This contains the complete `standalone-server/` application, pre-installed dependencies (no `composer install` required), and an optimized root `.htaccess` for automatic routing.
2. **Database Schema Setup**: The server uses a MySQL-compatible schema dump at **`standalone-server/database/database.sql`** instead of an SQLite file. Users must import this SQL file into their database manager (e.g. phpMyAdmin) and configure `.env` database details. No migrations are needed.
3. **Vital Directory Permissions**: Setting write permissions (`chmod -R 775` or `0777`) on the **`storage/`** and **`bootstrap/cache/`** directories is critical to server health. If the server cannot write to storage, file uploads and file listing endpoints will fail.
4. **Programmatic Storage Link (No SSH)**: Because many shared hosts lack SSH access, the symbolic link (`public/storage` -> `storage/app/public`) must be created programmatically. Instruct users to register a temporary web route inside `routes/web.php` to run `Artisan::call('storage:link')` and visit it:
   ```php
   Route::get('/symlink', function () {
       \Illuminate\Support\Facades\Artisan::call('storage:link');
       return 'Storage symlink created successfully!';
   });
   ```

## Critical Git Rules
- Before any public release, **the `main` branch must contain exactly one clean parentless commit** with no traces of historic intermediate code or sensitive data.
- Any new commits during development must be squashed or amended:
  - `git commit --amend --no-edit`
  - `git push -f origin main`
- Never store API credentials or personal tokens in version control.
