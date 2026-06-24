# Deploying LaraBucket Server to Production

LaraBucket can be deployed to any production virtual private server (VPS), cloud instance (AWS, DigitalOcean, GCP), or standard server environment. It can be run either containerized (via Docker) or directly on a standard PHP web server (Nginx/Apache).

---

## 🐳 Option A: Docker Deployment (Recommended)

Running LaraBucket in Docker ensures that all dependencies (PHP version, SQLite extension, GD image library, etc.) are pre-packaged and run identically in any environment.

### 1. Prerequisite
Ensure that you have **Docker** and **Docker Compose** installed on your server.

### 2. Copy Configuration Files
Copy the `docker-compose.yml`, `Dockerfile`, and `start-server.sh` from the repository root to your server's deployment folder:

```bash
mkdir -p /opt/larabucket
cd /opt/larabucket
# (Copy docker-compose.yml, Dockerfile, and start-server.sh into this folder)
```

### 3. Configure the Environment
Create a **`.env`** file in the same directory:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://storage.yourdomain.com

# LaraBucket Server Settings
LARABUCKET_SERVER_ENABLED=true
LARABUCKET_SERVER_DISK=public
LARABUCKET_ADMIN_EMAIL=super@admin.com
LARABUCKET_ADMIN_PASSWORD=your_secure_production_password
LARABUCKET_SERVER_URL=https://storage.yourdomain.com
```

### 4. Build and Start the Containers
Start the containers in detached (background) mode:

```bash
docker-compose up -d --build
```

The server will initialize the SQLite database, run migrations, and begin serving LaraBucket on port **`8000`**. You can set up a reverse proxy (like Nginx, Traefik, or Caddy) to map your domain `https://storage.yourdomain.com` with SSL to `http://localhost:8000`.

---

## 🖥️ Option B: Standard Laravel Deployment (Without Docker)

If you have a standard PHP environment (Nginx/Apache with PHP 8.1+), you can set up LaraBucket inside any fresh or existing Laravel application.

### 1. Install the Package
Run the installation command inside your Laravel application root:

```bash
composer require larabucket/laravel
```

### 2. Configure Environment
Update your application's `.env` file:

```ini
LARABUCKET_SERVER_ENABLED=true
LARABUCKET_SERVER_DISK=public
LARABUCKET_ADMIN_EMAIL=admin@yourdomain.com
LARABUCKET_ADMIN_PASSWORD=your_secure_password
LARABUCKET_SERVER_URL=https://storage.yourdomain.com
```

### 3. Publish Assets and Migrations
Publish the package configuration and migrations:

```bash
php artisan vendor:publish --tag=larabucket-config
php artisan vendor:publish --tag=larabucket-migrations
```

### 4. Migrate and Expose Storage
Execute the database migrations and symlink the storage folder to make files publicly accessible:

```bash
php artisan migrate
php artisan storage:link
```

---

## 🔒 Accessing the Administration Dashboard

Once deployed, visit your configured server URL in your browser:
```
https://storage.yourdomain.com/admin/larabucket
```
Log in using the `LARABUCKET_ADMIN_EMAIL` and `LARABUCKET_ADMIN_PASSWORD` credentials to create and manage your storage buckets!
