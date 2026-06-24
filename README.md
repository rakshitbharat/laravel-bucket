# LaraBucket Laravel Package

[![Run Tests](https://github.com/your-username/laravel-bucket/actions/workflows/tests.yml/badge.svg)](https://github.com/your-username/laravel-bucket/actions/workflows/tests.yml)

**LaraBucket** is a self-hosted object storage management solution designed to provide a centralized, stateless storage engine and S3-compatible experience for Laravel applications. 

This repository contains the unified Laravel package (`larabucket/laravel`) which serves two roles:
1. **The Client Disk Adapter**: A custom Flysystem V3 driver (`larabucket`) that integrates seamlessly with Laravel's native `Storage` facade, allowing you to use LaraBucket as a drop-in replacement for AWS S3.
2. **The Self-Hosted Storage Server**: Registers the database migrations, authentication, and REST API endpoints to turn any standard Laravel application into a storage hosting provider (perfect for shared hosting).

---

## 🚀 Key Features

- **Decoupled Stateless Storage**: Decouple file storage from application servers. Client servers can be destroyed/rebooted without data loss.
- **Shared Hosting Friendly**: Runs on standard PHP & SQLite/MySQL (no root or complex S3 infrastructure required).
- **Security & Path Sanitization**: Native authentication via API tokens (`X-API-KEY`) and full protection against directory traversal attacks (`../`).
- **Storage Limit Tracking**: Tracks and enforces storage quotas per bucket in real-time.
- **GitHub CI/CD & Auto-Publishing**: Pre-configured GitHub Actions to test across PHP/Laravel matrices and auto-publish to Packagist.

---

## 📦 Installation & Setup

### Role A: Client Integration (Connect your App to LaraBucket)

1. **Install the package** via Composer:
   ```bash
   composer require larabucket/laravel
   ```

2. **Configure Filesystem**:
   Add the `larabucket` disk configuration to your `config/filesystems.php`:
   ```php
   'disks' => [
       // ...
       'larabucket' => [
           'driver'  => 'larabucket',
           'api_url' => env('LARABUCKET_API_URL'),
           'bucket'  => env('LARABUCKET_BUCKET'),
           'secret'  => env('LARABUCKET_SECRET'),
       ],
   ],
   ```

3. **Configure Environment variables**:
   Add the following to your client `.env`:
   ```dotenv
   # Point to your LaraBucket server endpoint
   LARABUCKET_API_URL=https://your-larabucket-server.com/api
   
   # Credentials generated in the admin panel for your specific bucket
   LARABUCKET_BUCKET=my-website-assets
   LARABUCKET_SECRET=sk_live_generated_secret_key
   
   # Set as the default disk (Optional)
   FILESYSTEM_DISK=larabucket
   ```

4. **Usage**:
   Use standard Laravel `Storage` facade methods. It behaves exactly like standard local or S3 drivers:
   ```php
   use Illuminate\Support\Facades\Storage;
   
   // Upload file
   Storage::disk('larabucket')->put('avatars/user_1.jpg', $fileContents);
   
   // Check if file exists
   if (Storage::disk('larabucket')->exists('avatars/user_1.jpg')) {
       // Get public URL
       $url = Storage::disk('larabucket')->url('avatars/user_1.jpg');
   }
   
   // Delete file
   Storage::disk('larabucket')->delete('avatars/user_1.jpg');
   ```

---

### Role B: Server Setup (Self-Host LaraBucket API)

1. **Install the package** on the server Laravel project:
   ```bash
   composer require larabucket/laravel
   ```

2. **Configure Server Environment**:
   Add the following variables to your host `.env`:
   ```dotenv
   # Enable LaraBucket server API endpoints
   LARABUCKET_SERVER_ENABLED=true
   
   # Choose which disk LaraBucket stores files on (e.g. public or local)
   LARABUCKET_SERVER_DISK=public
   
   # Admin Dashboard credentials
   LARABUCKET_ADMIN_EMAIL=super@admin.com
   LARABUCKET_ADMIN_PASSWORD=my_secure_admin_password
   
   # URL of this storage server
   LARABUCKET_SERVER_URL=https://your-larabucket-server.com
   ```

3. **Publish Configuration and Migrations**:
   ```bash
   php artisan vendor:publish --tag=larabucket-config
   php artisan vendor:publish --tag=larabucket-migrations
   ```

4. **Run Database Migrations**:
   ```bash
   php artisan migrate
   ```

5. **Expose Public Files**:
   Ensure you run the standard Laravel storage link command to expose the uploaded public files:
   ```bash
   php artisan storage:link
   ```

---

## 🛠 Local Package Testing

You can run the entire test suite locally in an isolated environment using the included Docker configuration.

### Prerequisites
- Docker & Docker Compose running on your system.

### Executing Tests
1. Build the testing container:
   ```bash
   docker-compose build
   ```
2. Install package composer dependencies:
   ```bash
   docker-compose run --rm test-runner composer install --no-security-blocking
   ```
3. Run the tests:
   ```bash
   docker-compose run --rm test-runner vendor/bin/phpunit
   ```

---

## 🚀 Automated DevOps & CI/CD Setup

This package is pre-configured with **GitHub Actions** workflows located in `.github/workflows/`:

### 1. Automated Testing on Pull Requests (`tests.yml`)
Runs PHPUnit tests on every PR or push to `main`/`master` across a matrix of:
- **PHP Versions**: `8.1`, `8.2`, `8.3`
- **Laravel Versions**: `10.*`, `11.*`

### 2. Auto-Publishing to Packagist (`release.yml`)
Automatically updates Packagist when you push a new version tag (e.g., `v1.0.0`) or publish a release on GitHub.

**Setup Instructions**:
1. Register your package repository on [Packagist.org](https://packagist.org/).
2. Retrieve your Packagist **API Token** from your Packagist profile page.
3. In your GitHub repository, navigate to **Settings** -> **Secrets and variables** -> **Actions** -> **New repository secret**.
4. Create the following secrets:
   - `PACKAGIST_USERNAME`: Your Packagist account username.
   - `PACKAGIST_API_TOKEN`: Your Packagist API token.
