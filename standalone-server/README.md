# 📦 LaraBucket Standalone Storage Server Setup Guide

LaraBucket Standalone Server is a ready-to-use, self-hosted file storage console and API endpoint designed to run on any standard web hosting, including shared hosting plans (cPanel, Plesk, etc.).

---

## 🚀 Quick Setup on Shared Hosting

Follow these step-by-step instructions to get your LaraBucket storage server running in minutes:

### 1. Upload and Extract
1. Log into your hosting control panel (e.g., cPanel File Manager).
2. Upload the **`larabucket-standalone-server.zip`** archive to your preferred directory:
   - To host it on your main domain (e.g., `https://storage.yourdomain.com`), upload it directly to the root folder of that domain (e.g., `public_html` or a subdomain folder).
3. Extract the ZIP file in that folder.

> [!NOTE]
> The root `.htaccess` file is pre-configured to transparently route all incoming web traffic into the `public/` folder. You do **not** need to change your domain pointing settings or move the public files manually.

---

### 2. Configure Directory Permissions (CRITICAL)
For the server to write logs and upload files, set correct write permissions on the following folders:

1. **`storage/`** (and all its subdirectories) -> **`0775`** (or `0777` depending on your host)
2. **`bootstrap/cache/`** -> **`0775`**

> [!IMPORTANT]
> Directory permissions are the lifeblood of this package. If `storage/` is not writable by the web server (Apache/Nginx user), all file uploads, listing, and storage console features will fail immediately.

---

### 3. Database Setup (Import Schema)
Since migrations can be difficult to run on shared hosting without terminal access, we provide a pre-built SQL file:

1. Log into phpMyAdmin or your hosting database manager and create a new database.
2. Select your newly created database and click the **Import** tab.
3. Choose the **`database/database.sql`** file located inside the extracted server files and click **Go/Import**.
4. The database is now ready to use—no migration commands are needed!

---

### 4. Create the Storage Symlink (CRITICAL)
Laravel serves public files through a symbolic link from `public/storage` to `storage/app/public`. Without this link, files cannot be streamed or downloaded!

*   **If you have SSH access**:
    Run this command in the project root:
    ```bash
    php artisan storage:link
    ```
*   **If you do NOT have SSH access (Shared Hosting)**:
    Open the **`routes/web.php`** file in your file manager and temporarily add the following route:
    ```php
    Route::get('/symlink', function () {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        return 'Storage symlink created successfully!';
    });
    ```
    Then, open your browser and visit **`https://storage.yourdomain.com/symlink`**. Once you see the success message, delete the route from `routes/web.php` to prevent unauthorized access.

---

### 5. Environment Configuration (`.env`)
1. In the file manager, rename `.env.example` to **`.env`**.
2. Open the **`.env`** file and fill in your values. Here is a **complete, production-ready example**:

```ini
APP_NAME=LaraBucket
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# ── Database (MySQL) ──────────────────────────
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

# ── LaraBucket Server ─────────────────────────
# LARABUCKET_SERVER_URL must match APP_URL exactly.
# This value is used to build the public file URLs returned by the API.
LARABUCKET_SERVER_ENABLED=true
LARABUCKET_SERVER_DISK=public
LARABUCKET_ADMIN_EMAIL=super@admin.com
LARABUCKET_ADMIN_PASSWORD=password
LARABUCKET_SERVER_URL=https://your-domain.com
```

> [!IMPORTANT]
> **`APP_URL` and `LARABUCKET_SERVER_URL` must be identical** and must use your real domain (including `https://`). If they differ or still say `localhost`, the file URLs returned by the API will be wrong.

---

### 6. Generate Application Key
Laravel requires an application encryption key.
- **If you have SSH Access**: Run:
  ```bash
  php artisan key:generate
  ```
- **If you do NOT have SSH Access**:
  Open your browser and visit `https://storage.yourdomain.com` (which defaults to a welcome screen). If you get an encryption key error page, you can generate a key locally (e.g., run `php artisan key:generate` on your machine) and copy the generated `base64:...` string from your local `.env` directly into your hosting `.env` file's `APP_KEY` line.

---

## 🔒 Accessing the Administration Console

LaraBucket includes a premium management console dashboard out-of-the-box. For a comprehensive walkthrough of all console capabilities, please refer to the [LaraBucket User Guide](../docs/USER_GUIDE.md).

1. Navigate to: **`https://storage.yourdomain.com/admin/larabucket`**
2. Log in using the email and password you configured in your `.env` file (`LARABUCKET_ADMIN_EMAIL` and `LARABUCKET_ADMIN_PASSWORD`).
3. Inside the dashboard, you can:
   - Create storage **Namespaces** (buckets).
   - View storage allocation statistics and graphs.
   - Manage/browse uploaded files, copy public links, or delete items.

---

## 🔌 Connecting your Laravel Client Apps

To configure a client Laravel application to use this storage server:

1. Install the LaraBucket client driver in your client project:
   ```bash
   composer require larabucket/laravel
   ```
2. Open your client application's `config/filesystems.php` file and add the `larabucket` disk:
   ```php
   'disks' => [
       // ...
       'larabucket' => [
           'driver' => 'larabucket',
           'api_url' => env('LARABUCKET_API_URL'),
           'bucket'  => env('LARABUCKET_BUCKET'),
           'secret'  => env('LARABUCKET_SECRET_KEY'),
       ],
   ],
   ```
3. Set these environment variables in your client application's `.env`:
   ```ini
   FILESYSTEM_DISK=larabucket
   LARABUCKET_API_URL=https://storage.yourdomain.com/api
   LARABUCKET_BUCKET=your-namespace-name
   LARABUCKET_SECRET_KEY=your-namespace-secret-key
   ```
