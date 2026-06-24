<?php

namespace LaraBucket\Storage;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToRetrieveMetadata;
use Illuminate\Support\Facades\Http;

class LaraBucketAdapter implements FilesystemAdapter
{
    protected string $apiUrl;
    protected string $bucket;
    protected string $secret;

    public function __construct(string $apiUrl, string $bucket, string $secret)
    {
        $this->apiUrl = $apiUrl;
        $this->bucket = $bucket;
        $this->secret = $secret;
    }

    /**
     * Create base HTTP client.
     */
    protected function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret,
            'X-Bucket' => $this->bucket,
            'Accept' => 'application/json',
        ])->baseUrl(rtrim($this->apiUrl, '/'));
    }

    public function fileExists(string $path): bool
    {
        $response = $this->client()->head('/files', ['path' => $path]);
        return $response->status() === 200;
    }

    public function directoryExists(string $path): bool
    {
        $response = $this->client()->head('/files', [
            'path' => $path,
            'type' => 'directory'
        ]);
        return $response->status() === 200;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $response = $this->client()
            ->attach('file', $contents, basename($path))
            ->post("/buckets/{$this->bucket}/upload", [
                'path' => dirname($path) === '.' ? '/' : dirname($path)
            ]);

        if (!$response->successful()) {
            throw UnableToWriteFile::atLocation($path, $response->body());
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $contentsString = stream_get_contents($contents);
        $this->write($path, $contentsString, $config);
    }

    public function read(string $path): string
    {
        $response = $this->client()->get('/files/download', ['path' => $path]);

        if (!$response->successful()) {
            throw UnableToReadFile::fromLocation($path, $response->body());
        }

        return $response->body();
    }

    public function readStream(string $path)
    {
        $contents = $this->read($path);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);
        return $stream;
    }

    public function delete(string $path): void
    {
        $response = $this->client()->withQueryParameters(['path' => $path])->delete('/files');

        if (!$response->successful()) {
            throw UnableToDeleteFile::atLocation($path, $response->body());
        }
    }

    public function deleteDirectory(string $path): void
    {
        $response = $this->client()->withQueryParameters([
            'path' => $path,
            'type' => 'directory'
        ])->delete('/files');

        if (!$response->successful()) {
            throw UnableToDeleteFile::atLocation($path, $response->body());
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $response = $this->client()->post("/buckets/{$this->bucket}/folders", [
            'name' => basename($path),
            'path' => dirname($path) === '.' ? '/' : dirname($path)
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Unable to create directory at: {$path}. " . $response->body());
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // By default all files on LaraBucket are public.
    }

    protected function getMetadata(string $path): array
    {
        $response = $this->client()->get('/files/metadata', ['path' => $path]);

        if (!$response->successful()) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', $response->body());
        }

        return $response->json();
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, 'public');
    }

    public function mimeType(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        return new FileAttributes(
            $path,
            null,
            null,
            null,
            $meta['mime_type'] ?? 'application/octet-stream'
        );
    }

    public function lastModified(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        return new FileAttributes(
            $path,
            null,
            null,
            $meta['last_modified'] ?? time()
        );
    }

    public function fileSize(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        return new FileAttributes(
            $path,
            $meta['size'] ?? 0
        );
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $response = $this->client()->get("/buckets/{$this->bucket}/files", [
            'path' => $path === '.' ? '/' : $path,
            'deep' => $deep ? 1 : 0
        ]);

        if (!$response->successful()) {
            return [];
        }

        $items = $response->json();
        $result = [];

        foreach ($items as $item) {
            $prefix = ($path === '.' || $path === '/' || $path === '') ? '' : rtrim($path, '/') . '/';
            $itemPath = $prefix . $item['name'];

            if ($item['type'] === 'folder') {
                $result[] = new DirectoryAttributes($itemPath);
            } else {
                $result[] = new FileAttributes(
                    $itemPath,
                    $item['size'] ?? 0,
                    null,
                    isset($item['updatedAt']) ? strtotime($item['updatedAt']) : null,
                    $item['mimeType'] ?? null
                );
            }
        }

        return $result;
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $response = $this->client()->post('/files/copy', [
            'source' => $source,
            'destination' => $destination,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Unable to copy from {$source} to {$destination}. " . $response->body());
        }
    }
}
