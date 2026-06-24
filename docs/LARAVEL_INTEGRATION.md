# Laravel Integration Guide

LaraBucket is designed to work seamlessly with Laravel's native `Storage` facade. This means you do not need to rewrite your application code.

## 1. Environment Configuration

In your client Laravel application (the one connecting to LaraBucket), update your `.env` file with the following variables.

Replace the values with those found in the **"Connect"** modal of your specific bucket.

```dotenv
# .env

# Point to your LaraBucket server
LARABUCKET_API_URL=https://your-larabucket-server.com/api

# Credentials for the specific bucket
LARABUCKET_BUCKET=my-website-assets
LARABUCKET_SECRET=sk_live_generated_secret_key

# Set LaraBucket as the default disk (Recommended)
FILESYSTEM_DISK=larabucket
```

## 2. Filesystem Configuration

Add the LaraBucket disk configuration to `config/filesystems.php`.

```php
// config/filesystems.php

'disks' => [

    // ... s3, local, public ...

    'larabucket' => [
        'driver'  => 'larabucket',
        'api_url' => env('LARABUCKET_API_URL'),
        'bucket'  => env('LARABUCKET_BUCKET'),
        'secret'  => env('LARABUCKET_SECRET'),
        'visibility' => 'public',
    ],

],
```

## 3. Registering the Driver

In your `AppServiceProvider.php`, register the custom driver. This uses a custom Flysystem adapter (code provided in Section 5).

```php
// app/Providers/AppServiceProvider.php

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use App\Storage\LaraBucketAdapter;

public function boot()
{
    Storage::extend('larabucket', function ($app, $config) {
        $adapter = new LaraBucketAdapter(
            $config['api_url'],
            $config['bucket'],
            $config['secret']
        );
        
        return new Filesystem($adapter);
    });
}
```

## 4. Usage

Once configured, you can use standard Laravel methods.

### Basic Storage
```php
// Automatically uses LaraBucket if FILESYSTEM_DISK=larabucket
Storage::put('avatars/1.jpg', $fileContents);
```

### Retrieving URLs
```php
// Returns: https://your-larabucket-server.com/storage/my-website-assets/avatars/1.jpg
$url = Storage::url('avatars/1.jpg');
```

---

## 5. Appendix: Sample Adapter Code

Create a file at `app/Storage/LaraBucketAdapter.php`. This acts as the bridge between Laravel and the LaraBucket API.

*(Note: This is a simplified example. You may need to implement `League\Flysystem\FilesystemAdapter` fully depending on your Laravel version.)*

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
            'X-Bucket' => $this->bucket,
        ])->baseUrl($this->apiUrl);
    }

    public function fileExists(string $path): bool
    {
        return $this->client()->head("/files", ['path' => $path])->successful();
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->client()
             ->attach('file', $contents, basename($path))
             ->post("/buckets/{$this->bucket}/upload", ['path' => dirname($path)]);
    }

    public function read(string $path): string
    {
        return $this->client()->get("/files/download", ['path' => $path])->body();
    }

    public function delete(string $path): void
    {
        $this->client()->delete("/files", ['path' => $path]);
    }
    
    // ... Implement other required methods (createDirectory, listContents, etc.) 
    // referencing the API_REFERENCE.md
}
```
