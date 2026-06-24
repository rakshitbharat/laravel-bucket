<?php

namespace LaraBucket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use LaraBucket\Models\Bucket;

class StorageController extends Controller
{
    /**
     * Resolve the active storage disk.
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
     * Get bucket from request attributes.
     */
    protected function getRequestBucket(Request $request): Bucket
    {
        return $request->attributes->get('larabucket_bucket');
    }

    /**
     * Check if a file or directory exists.
     */
    public function exists(Request $request)
    {
        $bucket = $this->getRequestBucket($request);
        $path = $request->input('path', '/');
        $type = $request->input('type', 'file');
        
        $safePath = $this->getSafePath($bucket, $path);
        $disk = $this->getDisk();

        if ($type === 'directory') {
            return $disk->directoryExists($safePath) ? response('', 200) : response('', 404);
        }

        return $disk->exists($safePath) && !$disk->directoryExists($safePath)
            ? response('', 200)
            : response('', 404);
    }

    /**
     * Upload/write a file.
     */
    public function upload(Request $request)
    {
        $bucket = $this->getRequestBucket($request);
        
        $request->validate([
            'file' => 'required|file',
            'path' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $dirPath = $request->input('path', '/');
        $filename = $file->getClientOriginalName();
        
        $safeDir = $this->getSafePath($bucket, $dirPath);
        $fullPath = rtrim($safeDir, '/') . '/' . $filename;
        $disk = $this->getDisk();

        $fileSize = $file->getSize();

        // Check size limit
        $oldSize = $disk->exists($fullPath) ? $disk->size($fullPath) : 0;
        $newTotalUsed = ($bucket->size_used - $oldSize) + $fileSize;
        $limitInBytes = $bucket->storage_limit_mb * 1024 * 1024;

        if ($newTotalUsed > $limitInBytes) {
            return response()->json(['message' => 'Bucket storage limit exceeded'], 400);
        }

        // Store file
        $disk->putFileAs($safeDir, $file, $filename);

        // Adjust usage stats
        $difference = $fileSize - $oldSize;
        if ($difference !== 0) {
            if ($difference > 0) {
                $bucket->increment('size_used', $difference);
            } else {
                $bucket->decrement('size_used', abs($difference));
            }
        }

        // Generate public URL if using public disk or local custom route
        $url = config('larabucket.server.url', config('app.url')) . '/storage/' . $fullPath;

        return response()->json([
            'success' => true,
            'url' => $url,
            'path' => $fullPath,
        ]);
    }

    /**
     * Download/read a file.
     */
    public function download(Request $request)
    {
        $bucket = $this->getRequestBucket($request);
        $path = $request->input('path');

        if (empty($path)) {
            return response()->json(['message' => 'Path parameter is required'], 400);
        }

        $safePath = $this->getSafePath($bucket, $path);
        $disk = $this->getDisk();

        if (!$disk->exists($safePath) || $disk->directoryExists($safePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $contents = $disk->get($safePath);
        $mime = $disk->mimeType($safePath) ?: 'application/octet-stream';

        return response($contents, 200)->header('Content-Type', $mime);
    }

    /**
     * Delete a file or directory.
     */
    public function delete(Request $request)
    {
        $bucket = $this->getRequestBucket($request);
        $path = $request->input('path');
        $type = $request->input('type', 'file');

        if (empty($path)) {
            return response()->json(['message' => 'Path parameter is required'], 400);
        }

        $safePath = $this->getSafePath($bucket, $path);
        
        // Prevent deleting root of the bucket slugs by accident
        if ($safePath === $bucket->slug) {
            return response()->json(['message' => 'Cannot delete bucket root'], 400);
        }

        $disk = $this->getDisk();

        if ($type === 'directory') {
            if (!$disk->directoryExists($safePath)) {
                return response()->json(['message' => 'Directory not found'], 404);
            }

            // Calculate size of all deleted files
            $sizeDeleted = 0;
            $files = $disk->allFiles($safePath);
            foreach ($files as $file) {
                $sizeDeleted += $disk->size($file);
            }

            $disk->deleteDirectory($safePath);

            if ($sizeDeleted > 0) {
                $bucket->decrement('size_used', $sizeDeleted);
            }

            return response()->json(['success' => true]);
        }

        if (!$disk->exists($safePath) || $disk->directoryExists($safePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $size = $disk->size($safePath);
        $disk->delete($safePath);

        $bucket->decrement('size_used', $size);

        return response()->json(['success' => true]);
    }

    /**
     * Get file metadata.
     */
    public function metadata(Request $request)
    {
        $bucket = $this->getRequestBucket($request);
        $path = $request->input('path');

        if (empty($path)) {
            return response()->json(['message' => 'Path parameter is required'], 400);
        }

        $safePath = $this->getSafePath($bucket, $path);
        $disk = $this->getDisk();

        if (!$disk->exists($safePath) || $disk->directoryExists($safePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->json([
            'size' => $disk->size($safePath),
            'mime_type' => $disk->mimeType($safePath) ?: 'application/octet-stream',
            'last_modified' => $disk->lastModified($safePath),
        ]);
    }

    /**
     * Copy a file.
     */
    public function copy(Request $request)
    {
        $bucket = $this->getRequestBucket($request);
        
        $request->validate([
            'source' => 'required|string',
            'destination' => 'required|string',
        ]);

        $source = $request->input('source');
        $destination = $request->input('destination');

        $safeSource = $this->getSafePath($bucket, $source);
        $safeDest = $this->getSafePath($bucket, $destination);
        $disk = $this->getDisk();

        if (!$disk->exists($safeSource) || $disk->directoryExists($safeSource)) {
            return response()->json(['message' => 'Source file not found'], 404);
        }

        // Check limit
        $sourceSize = $disk->size($safeSource);
        $oldDestSize = $disk->exists($safeDest) ? $disk->size($safeDest) : 0;
        $newTotalUsed = ($bucket->size_used - $oldDestSize) + $sourceSize;
        $limitInBytes = $bucket->storage_limit_mb * 1024 * 1024;

        if ($newTotalUsed > $limitInBytes) {
            return response()->json(['message' => 'Bucket storage limit exceeded'], 400);
        }

        $disk->copy($safeSource, $safeDest);

        $difference = $sourceSize - $oldDestSize;
        if ($difference !== 0) {
            if ($difference > 0) {
                $bucket->increment('size_used', $difference);
            } else {
                $bucket->decrement('size_used', abs($difference));
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Serve a file publicly.
     */
    public function servePublicFile(string $path)
    {
        $path = str_replace('..', '', $path);
        $disk = $this->getDisk();

        if (!$disk->exists($path) || $disk->directoryExists($path)) {
            abort(404, 'File not found');
        }

        $contents = $disk->get($path);
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';

        return response($contents, 200)->header('Content-Type', $mime);
    }
}

