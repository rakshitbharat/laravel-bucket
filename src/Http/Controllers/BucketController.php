<?php

namespace LaraBucket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaraBucket\Models\Bucket;

class BucketController extends Controller
{
    /**
     * Resolve active disk.
     */
    protected function getDisk()
    {
        $diskName = config('larabucket.server.disk', 'local');
        return Storage::disk($diskName);
    }

    /**
     * Get safe localized path inside bucket slug folder.
     */
    protected function getSafePath(Bucket $bucket, ?string $path): string
    {
        $path = $path ?? '';
        $path = str_replace('..', '', $path);
        $path = ltrim($path, '/');
        
        return empty($path) ? $bucket->slug : $bucket->slug . '/' . $path;
    }

    /**
     * Find a bucket by ID or slug.
     */
    protected function findBucket($id): Bucket
    {
        $bucket = is_numeric($id) 
            ? Bucket::find($id) 
            : Bucket::where('slug', $id)->first();

        if (!$bucket) {
            abort(404, 'Bucket not found');
        }

        return $bucket;
    }

    /**
     * Map bucket model to API response format.
     */
    protected function formatBucket(Bucket $bucket): array
    {
        return [
            'id' => (string) $bucket->id,
            'name' => $bucket->name,
            'slug' => $bucket->slug,
            'ownerEmail' => $bucket->owner_email,
            'storageLimitMb' => (int) $bucket->storage_limit_mb,
            'storageUsedMb' => round($bucket->size_used / (1024 * 1024), 2), // bytes to MB
            'secretKey' => $bucket->secret_key,
            'isActive' => (bool) $bucket->is_active,
            'createdAt' => $bucket->created_at ? $bucket->created_at->toIso8601String() : date('c'),
        ];
    }

    /**
     * List all buckets.
     */
    public function index()
    {
        $buckets = Bucket::all();
        return response()->json($buckets->map(fn($b) => $this->formatBucket($b)));
    }

    /**
     * Create a new bucket.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ownerEmail' => 'nullable|email|max:255',
            'storageLimitMb' => 'nullable|integer|min:1',
            'secretKey' => 'nullable|string|max:255',
        ]);

        $slug = Str::slug($request->input('name'));

        // Check uniqueness of name/slug
        if (Bucket::where('slug', $slug)->exists()) {
            return response()->json(['message' => 'A bucket with this name or slug already exists'], 422);
        }

        $bucket = Bucket::create([
            'name' => $request->input('name'),
            'slug' => $slug,
            'owner_email' => $request->input('ownerEmail'),
            'storage_limit_mb' => $request->input('storageLimitMb', 1000),
            'secret_key' => $request->input('secretKey'),
        ]);

        // Create folder inside storage disk for this bucket
        $this->getDisk()->makeDirectory($slug);

        return response()->json($this->formatBucket($bucket), 201);
    }

    /**
     * Update a bucket.
     */
    public function update(Request $request, $id)
    {
        $bucket = $this->findBucket($id);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'ownerEmail' => 'nullable|email|max:255',
            'storageLimitMb' => 'nullable|integer|min:1',
            'secretKey' => 'nullable|string|max:255',
        ]);

        $oldSlug = $bucket->slug;
        $name = $request->input('name');

        if ($name && $name !== $bucket->name) {
            $newSlug = Str::slug($name);
            
            // Check uniqueness if slug changes
            if (Bucket::where('slug', $newSlug)->where('id', '!=', $bucket->id)->exists()) {
                return response()->json(['message' => 'A bucket with this name or slug already exists'], 422);
            }

            $bucket->name = $name;
            $bucket->slug = $newSlug;

            // Move the storage folder on disk
            $disk = $this->getDisk();
            if ($disk->directoryExists($oldSlug)) {
                $disk->move($oldSlug, $newSlug);
            }
        }

        if ($request->has('ownerEmail')) {
            $bucket->owner_email = $request->input('ownerEmail');
        }

        if ($request->has('storageLimitMb')) {
            $bucket->storage_limit_mb = $request->input('storageLimitMb');
        }

        if ($request->has('secretKey')) {
            $bucket->secret_key = $request->input('secretKey');
        }

        $bucket->save();

        return response()->json($this->formatBucket($bucket));
    }

    /**
     * Delete a bucket.
     */
    public function destroy($id)
    {
        $bucket = $this->findBucket($id);

        // Delete physical folder and all files inside
        $disk = $this->getDisk();
        if ($disk->directoryExists($bucket->slug)) {
            $disk->deleteDirectory($bucket->slug);
        }

        $bucket->delete();

        return response()->json(['success' => true]);
    }

    /**
     * List all files and subfolders in a path.
     */
    public function files(Request $request, $bucketId)
    {
        $bucket = $this->findBucket($bucketId);
        $path = $request->input('path', '/');
        
        $safePath = $this->getSafePath($bucket, $path);
        $disk = $this->getDisk();

        if (!$disk->directoryExists($safePath)) {
            return response()->json([]);
        }

        $directories = $disk->directories($safePath);
        $files = $disk->files($safePath);
        $response = [];

        $clientRelativePath = '/' . ltrim(str_replace('..', '', $path), '/');
        $clientRelativePath = rtrim($clientRelativePath, '/');
        if (empty($clientRelativePath)) {
            $clientRelativePath = '/';
        }

        foreach ($directories as $dir) {
            $dirName = basename($dir);
            $fullDirSafe = $bucket->slug . '/' . ltrim($clientRelativePath === '/' ? $dirName : $clientRelativePath . '/' . $dirName, '/');
            $id = base64_encode($fullDirSafe);

            $response[] = [
                'id' => $id,
                'bucketId' => (string) $bucket->id,
                'name' => $dirName,
                'type' => 'folder',
                'size' => 0,
                'path' => $clientRelativePath,
                'updatedAt' => date('c'),
            ];
        }

        foreach ($files as $file) {
            $fileName = basename($file);
            $fullFileSafe = $bucket->slug . '/' . ltrim($clientRelativePath === '/' ? $fileName : $clientRelativePath . '/' . $fileName, '/');
            $id = base64_encode($fullFileSafe);

            $response[] = [
                'id' => $id,
                'bucketId' => (string) $bucket->id,
                'name' => $fileName,
                'type' => 'file',
                'size' => $disk->size($file),
                'mimeType' => $disk->mimeType($file) ?: 'application/octet-stream',
                'path' => $clientRelativePath,
                'updatedAt' => date('c', $disk->lastModified($file)),
            ];
        }

        return response()->json($response);
    }

    /**
     * Create a folder.
     */
    public function makeFolder(Request $request, $bucketId)
    {
        $bucket = $this->findBucket($bucketId);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'nullable|string',
        ]);

        $name = Str::slug($request->input('name'));
        $path = $request->input('path', '/');

        $safePath = $this->getSafePath($bucket, $path);
        $newFolderPath = rtrim($safePath, '/') . '/' . $name;
        
        $disk = $this->getDisk();
        $disk->makeDirectory($newFolderPath);

        return response()->json(['success' => true], 201);
    }

    /**
     * Delete file by fileId.
     */
    public function deleteFile($fileId)
    {
        $fullPath = base64_decode($fileId);
        if (!$fullPath) {
            return response()->json(['message' => 'Invalid file ID'], 400);
        }

        $fullPath = str_replace('..', '', $fullPath);
        $parts = explode('/', ltrim($fullPath, '/'), 2);
        
        $bucketSlug = $parts[0] ?? '';
        
        if (empty($bucketSlug)) {
            return response()->json(['message' => 'Invalid file ID path'], 400);
        }

        $bucket = Bucket::where('slug', $bucketSlug)->first();
        if (!$bucket) {
            return response()->json(['message' => 'Bucket not found'], 404);
        }

        $disk = $this->getDisk();
        
        if (!$disk->exists($fullPath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $size = $disk->size($fullPath);
        $disk->delete($fullPath);

        $bucket->decrement('size_used', $size);

        return response()->json(['success' => true]);
    }
}
