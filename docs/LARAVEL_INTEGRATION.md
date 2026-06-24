# Laravel Integration Guide

LaraBucket integrates seamlessly with Laravel's native `Storage` facade. With the release of our official Packagist package, setup is fully automated.

Choose one of the two methods below to connect your application:

*   **Option A: Package Integration (Recommended)** — Installs the official driver automatically.
*   **Option B: Manual Integration** — Best if you want custom logic or want to avoid package dependencies.

---

## 📦 Option A: Package Integration (Recommended)

This method automatically registers the custom `larabucket` Flysystem driver via the package service provider.

### 1. Install the Package
Run the following composer command in your client Laravel application:
```bash
composer require larabucket/laravel
```

### 2. Register the Disk Configuration
Add the `larabucket` driver configuration to your `config/filesystems.php` file:

```php
// config/filesystems.php

'disks' => [

    // ... other disks (local, public, s3, etc.)

    'larabucket' => [
        'driver'     => 'larabucket',
        'api_url'    => env('LARABUCKET_API_URL'),
        'bucket'     => env('LARABUCKET_BUCKET'),
        'secret'     => env('LARABUCKET_SECRET'),
        'visibility' => 'public', // Default visibility for files
    ],

],
```

### 3. Configure Credentials
Add your storage server details to your `.env` file (these credentials can be generated in the LaraBucket admin dashboard):

```env
# Your self-hosted LaraBucket server API endpoint
LARABUCKET_API_URL=https://storage.yourdomain.com/api

# Credentials for your namespace
LARABUCKET_BUCKET=my-website-assets
LARABUCKET_SECRET=sk_live_generated_secret_key_123

# Set as default disk (optional)
FILESYSTEM_DISK=larabucket
```

---

## 🔌 Option B: Manual Integration (No Package Dependency)

If you prefer to connect your application without installing the entire package, you can create a custom Flysystem adapter and register it manually.

### 1. Create the Custom Adapter
Create a new PHP file in your client application at `app/Storage/LaraBucketAdapter.php`:

```php
<?php

namespace App\Storage;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Config;
use Illuminate\Support\Facades\Http;

class LaraBucketAdapter implements FilesystemAdapter
{
    protected $apiUrl;
    protected $bucket;
    protected $secret;

    public function __construct($apiUrl, $bucket, $secret)
    {
        $this->apiUrl = $apiUrl;
        $this->bucket = $bucket;
        $this->secret = $secret;
    }

    protected function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret,
            'X-API-KEY' => $this->secret, // LaraBucket authentication header
        ])->baseUrl($this->apiUrl);
    }

    public function fileExists(string $path): bool
    {
        return $this->client()->get("/files", ['path' => $path])->successful();
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->client()
             ->attach('file', $contents, basename($path))
             ->post("/buckets/{$this->bucket}/upload", ['path' => dirname($path)]);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        return $this->client()->get("/files/download", ['path' => $path])->body();
    }

    public function readStream(string $path)
    {
        $temp = fopen('php://temp', 'r+');
        fwrite($temp, $this->read($path));
        rewind($temp);
        return $temp;
    }

    public function delete(string $path): void
    {
        $this->client()->delete("/files", ['path' => $path]);
    }

    public function deleteDirectory(string $path): void
    {
        // Handled by the core server APIs
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Handled dynamically on upload
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // LaraBucket uses bucket-level/route visibility
    }

    public function visibility(string $path): \League\Flysystem\FileAttributes
    {
        return new \League\Flysystem\FileAttributes($path, 'public');
    }

    public function mimeType(string $path): \League\Flysystem\FileAttributes
    {
        return new \League\Flysystem\FileAttributes($path, null, null, null, 'application/octet-stream');
    }

    public function lastModified(string $path): \League\Flysystem\FileAttributes
    {
        return new \League\Flysystem\FileAttributes($path);
    }

    public function fileSize(string $path): \League\Flysystem\FileAttributes
    {
        return new \League\Flysystem\FileAttributes($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        // Implement custom copy/delete logic if needed
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        // Implement custom copy logic if needed
    }
}
```

### 2. Register Driver in Service Provider
Register the custom driver inside your `app/Providers/AppServiceProvider.php` file:

```php
// app/Providers/AppServiceProvider.php

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use App\Storage\LaraBucketAdapter;

public function boot(): void
{
    Storage::extend('larabucket', function ($app, $config) {
        $adapter = new LaraBucketAdapter(
            $config['api_url'],
            $config['bucket'],
            $config['secret']
        );
        
        return new \Illuminate\Filesystem\FilesystemAdapter(
            new Filesystem($adapter, $config),
            $adapter,
            $config
        );
    });
}
```

### 3. Add Filesystem Configuration
Configure the disk config in `config/filesystems.php` exactly as described in Option A (Step 2).

---

## ⚡ Usage Example

Once integrated via Option A or Option B, use Laravel's standard `Storage` facade to manage files:

```php
use Illuminate\Support\Facades\Storage;

// 1. Upload a file
Storage::disk('larabucket')->put('avatars/user_1.jpg', $fileContents);

// 2. Check if a file exists
if (Storage::disk('larabucket')->exists('avatars/user_1.jpg')) {
    
    // 3. Generate the public URL
    // Returns: https://storage.yourdomain.com/storage/my-website-assets/avatars/user_1.jpg
    $url = Storage::disk('larabucket')->url('avatars/user_1.jpg');
    
    // 4. Download file contents
    $content = Storage::disk('larabucket')->get('avatars/user_1.jpg');
}

// 5. Delete a file
Storage::disk('larabucket')->delete('avatars/user_1.jpg');
```
