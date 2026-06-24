> [!NOTE]
> This document represents the initial conceptual design of LaraBucket. For the final production implementation, integration details, and deployment guides, please refer to the official [README.md](../README.md), [SHARED_HOSTING.md](SHARED_HOSTING.md), and [LARAVEL_INTEGRATION.md](LARAVEL_INTEGRATION.md).

---

# Project Documentation: Private Object Storage Microservice

## 1. Project Overview
**Name:** LaraBucket (Internal Name)
**Type:** Self-Hosted Object Storage API
**Goal:** To decouple file storage from application servers using a centralized, stateless architecture on standard Shared Hosting.

**Architecture:**
*   **The Host (Storage Server):** A Laravel application that acts as the "Bucket Manager." It stores files, authenticates requests via API Keys, and provides a GUI for management.
*   **The Clients (Your Apps):** Separate Laravel applications that connect to the Host via HTTP to upload/delete files.

**Key Features:**
*   **Stateless Clients:** Client servers can be destroyed/rebooted without data loss.
*   **Shared Hosting Friendly:** Runs on standard PHP/MySQL (No Root/Docker required).
*   **Isolated:** Each client app has its own dedicated storage folder and API Key.
*   **GUI:** Admin panel to manage buckets and view files.

---

## 2. The Host Application (Storage Server)

### 2.1. Database Schema
We need a table to manage the "Buckets" (the different websites using this storage).

**Migration:** `create_buckets_table.php`
```php
Schema::create('buckets', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();        // e.g., "ecommerce-main"
    $table->string('slug')->unique();        // Used for folder name: "ecommerce-main"
    $table->string('secret_key')->unique();  // API Key for authentication
    $table->unsignedBigInteger('size_used')->default(0); // Track usage (bytes)
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 2.2. The API Logic
This controller handles the incoming file traffic.

**File:** `app/Http/Controllers/Api/StorageController.php`

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bucket;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageController extends Controller
{
    private function authenticate($request)
    {
        $key = $request->header('X-API-KEY');
        $bucket = Bucket::where('secret_key', $key)->where('is_active', true)->first();
        if (!$bucket) abort(401, 'Invalid Storage Key');
        return $bucket;
    }

    public function upload(Request $request)
    {
        $bucket = $this->authenticate($request);

        $request->validate([
            'file' => 'required|file|max:20480', // Max 20MB
            'folder' => 'nullable|string'
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'default');
        
        // Sanitize folder path
        $folder = str_replace('..', '', $folder);

        // Path: public/{bucket_slug}/{folder}/{filename}
        $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $storePath = "public/{$bucket->slug}/{$folder}";
        
        $path = $file->storeAs($storePath, $filename);

        // Update Usage Stats
        $bucket->increment('size_used', $file->getSize());

        return response()->json([
            'success' => true,
            'url' => asset(str_replace('public', 'storage', $path)),
            'path' => $path // Save this path to delete later
        ]);
    }

    public function delete(Request $request)
    {
        $bucket = $this->authenticate($request);
        $path = $request->input('path'); 

        // Security: Ensure path belongs to this bucket
        if (!Str::startsWith($path, "public/{$bucket->slug}/")) {
            abort(403, 'Unauthorized Access');
        }

        if (Storage::exists($path)) {
            Storage::delete($path);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'File not found'], 404);
    }
}
```

### 2.3. The GUI (Management Panel)
*Use **FilamentPHP** to create the dashboard.*

1.  **Super Admin View:**
    *   Resource: `BucketResource`
    *   Actions: Create new Bucket, Regenerate Key, View Total Usage.
2.  **Bucket View (File Manager):**
    *   A custom page showing a grid of images inside `storage/app/public/{slug}`.
    *   Allows admins to manually upload/delete files via the browser.

---

## 3. The Client Integration (Your Websites)

Do **not** use the `s3` driver. Use this custom service class in your applications.

### 3.1. Environment Configuration
Add this to the `.env` of your client websites.

```dotenv
# .env
REMOTE_STORAGE_ENDPOINT=https://storage.yourdomain.com/api
REMOTE_STORAGE_KEY=sk_your_secret_key_here
```

### 3.2. The Service Class (SDK)
Create this file in your client app: `app/Services/RemoteStorage.php`.

```php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class RemoteStorage
{
    public static function upload(UploadedFile $file, $folder = 'uploads')
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => env('REMOTE_STORAGE_KEY')
            ])->attach(
                'file', file_get_contents($file), $file->getClientOriginalName()
            )->post(env('REMOTE_STORAGE_ENDPOINT') . '/upload', [
                'folder' => $folder
            ]);

            return $response->json(); 
            // Returns: ['success' => true, 'url' => '...', 'path' => '...']
        } catch (\Exception $e) {
            // Log error
            return null;
        }
    }

    public static function delete($path)
    {
        return Http::withHeaders([
            'X-API-KEY' => env('REMOTE_STORAGE_KEY')
        ])->delete(env('REMOTE_STORAGE_ENDPOINT') . '/delete', [
            'path' => $path
        ])->successful();
    }
}
```

### 3.3. How Developers Use It

**Uploading an Avatar:**
```php
use App\Services\RemoteStorage;

public function updateAvatar(Request $request)
{
    if ($request->hasFile('avatar')) {
        // 1. Upload
        $result = RemoteStorage::upload($request->file('avatar'), 'users');

        if ($result && $result['success']) {
            // 2. Save to DB
            $user->avatar_url = $result['url'];   // Public URL for <img> tags
            $user->avatar_path = $result['path']; // Internal path for deleting later
            $user->save();
        }
    }
}
```

---

## 4. Deployment Checklist (Shared Hosting)

1.  **Symlink:**
    On the **Host Server**, you must run this command to expose the files to the public internet:
    ```bash
    php artisan storage:link
    ```
    *If you don't have SSH access, use a Route::get closure to execute `Artisan::call('storage:link');` once.*

2.  **PHP Limits:**
    Since we are using HTTP uploads, create a `.user.ini` or modify `.htaccess` on the **Host Server** to ensure:
    ```ini
    upload_max_filesize = 50M
    post_max_size = 50M
    memory_limit = 128M
    ```

3.  **Security:**
    *   Ensure the `storage/` folder lists directory indexing as **OFF** (usually default in Laravel).
    *   The API Key should be high entropy (random string of 32+ characters).

## 5. Summary of Workflow

1.  **Admin** creates a "Bucket" in the Host Dashboard -> Gets `sk_12345`.
2.  **Developer** puts `sk_12345` into Client App `.env`.
3.  **User** uploads a file on Client App.
4.  Client App sends file via HTTP -> Host App.
5.  Host App saves file to `storage/public/bucket_name/file.jpg`.
6.  Host App returns URL `https://storage.com/storage/bucket_name/file.jpg`.
7.  Client App saves URL in database.
