# Deploying LaraBucket Server to Shared Hosting

LaraBucket is designed to be extremely lightweight and shared-hosting friendly. You can set up a centralized storage server on any low-cost cPanel or shared hosting environment in under 5 minutes without needing SSH access.

---

## 🚀 Step-by-Step Setup Guide

### 1. Download the Ready-To-Use ZIP
Download the package directly using this link: **[Download larabucket-standalone-server.zip](https://github.com/rakshitbharat/laravel-bucket/raw/main/larabucket-standalone-server.zip)**.

This package comes pre-bundled with:
- The full Laravel framework core.
- The compiled `larabucket/laravel` server engine.
- All production dependencies (no need to run `composer install`).
- A clean SQL database schema (`database/database.sql`) ready to import.
- A pre-configured `.htaccess` file for automatic subfolder routing.

---

### 2. Upload to Shared Hosting
1. Log in to your hosting account (e.g., cPanel File Manager) or connect via FTP.
2. Navigate to your website's document root (typically `public_html`, `www`, or `httpdocs`).
3. Upload `larabucket-standalone-server.zip` and extract its contents directly into the document root.

> [!IMPORTANT]
> Ensure all files (including hidden files like `.htaccess` and `.env`) are extracted into the main document root folder.

---

### 3. Database Setup (Import Schema)
Since running terminal migrations is often disabled on shared hosting:
1. Log in to your cPanel or Plesk panel and open **phpMyAdmin** (or your preferred database manager).
2. Create a new database and a database user, then assign the user to the database with all privileges.
3. Select your newly created database in phpMyAdmin, and click the **Import** tab.
4. Choose the **`database/database.sql`** file located inside the extracted server files and click **Go/Import**.
5. All required storage tables are now created and ready—no migration commands are needed!

---

### 4. Configure the Environment (`.env`)
Locate and edit the `.env` file in the root directory. Update your database connection credentials:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Configure the LaraBucket application and admin credentials:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# LaraBucket Server Settings
LARABUCKET_SERVER_ENABLED=true
LARABUCKET_SERVER_DISK=public
LARABUCKET_ADMIN_EMAIL=admin@yourdomain.com
LARABUCKET_ADMIN_PASSWORD=your_super_secure_password_here
LARABUCKET_SERVER_URL=https://yourdomain.com
```

---

### 5. Adjust Folder Permissions (CRITICAL)
For the application to write logs, cache, and save files, Nginx/Apache needs full write permissions. Set write permissions (`chmod -R 775` or `chmod -R 777` depending on your host) for:
- **`storage/`** (and all its subfolders recursive)
- **`bootstrap/cache/`**

> [!IMPORTANT]
> Folder write permission is the backbone of this package. If the web server cannot write to the `storage/` directory, file uploads, file list API requests, and dashboard console operations will fail.

---

### 6. Create the Storage Symlink (CRITICAL)
Laravel serves public files through a symbolic link from `public/storage` to `storage/app/public`. Without this link, files cannot be streamed or downloaded!

Since you do not have SSH access on shared hosting, create this link programmatically:

1. Open the **`routes/web.php`** file inside the file manager.
2. Temporarily register the following route:
   ```php
   Route::get('/symlink', function () {
       \Illuminate\Support\Facades\Artisan::call('storage:link');
       return 'Storage symlink created successfully!';
   });
   ```
3. Open your browser and visit **`https://yourdomain.com/symlink`**.
4. Once you see the success message, delete the route from `routes/web.php` to prevent unauthorized execution.

---

## ⚙️ How the Shared Hosting Redirection Works

Most shared hosts point domain roots to `public_html` and do not allow changing the Apache DocumentRoot directory. To make LaraBucket work seamlessly out of the box, we included a root-level `.htaccess` file:

```apache
# LaraBucket Shared Hosting Rewrite Rules
# This file transparently routes all requests to the public/ directory
# so that you do not need to change the server configuration or point
# your domain specifically to the public/ folder on shared hosting.

<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Do not rewrite if request is already inside the public/ folder
    RewriteCond %{REQUEST_URI} !^/public/
    
    # Rewrite all requests to the public/ folder
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

This transparently routes all public web traffic to Laravel's `/public` folder under the hood, enabling standard routing without exposing core PHP source files.

---

## 🖥️ Accessing the Admin Console

1. Navigate in your browser to:
   `https://yourdomain.com/admin/larabucket`
2. Log in using the email and password you configured in your `.env` file.
3. Create your first storage namespace (bucket) and obtain its API token to connect your client applications!
