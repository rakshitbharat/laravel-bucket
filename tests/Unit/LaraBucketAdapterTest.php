<?php

namespace LaraBucket\Tests\Unit;

use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use LaraBucket\Storage\LaraBucketAdapter;
use LaraBucket\Tests\TestCase;

class LaraBucketAdapterTest extends TestCase
{
    protected LaraBucketAdapter $adapter;
    protected string $apiUrl = 'http://larabucket-server.test/api';
    protected string $bucket = 'test-bucket';
    protected string $secret = 'sk_secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new LaraBucketAdapter($this->apiUrl, $this->bucket, $this->secret);
    }

    /** @test */
    public function file_exists_sends_correct_head_request()
    {
        Http::fake([
            'larabucket-server.test/api/files*' => Http::response('', 200)
        ]);

        $exists = $this->adapter->fileExists('path/to/file.txt');

        $this->assertTrue($exists);

        Http::assertSent(function ($request) {
            return $request->method() === 'HEAD'
                && $request->url() === 'http://larabucket-server.test/api/files?path=path%2Fto%2Ffile.txt'
                && $request->header('Authorization')[0] === 'Bearer sk_secret'
                && $request->header('X-Bucket')[0] === 'test-bucket';
        });
    }

    /** @test */
    public function file_exists_returns_false_on_404()
    {
        Http::fake([
            'larabucket-server.test/api/files*' => Http::response('', 404)
        ]);

        $exists = $this->adapter->fileExists('missing.txt');

        $this->assertFalse($exists);
    }

    /** @test */
    public function write_sends_correct_post_request()
    {
        Http::fake([
            'larabucket-server.test/api/buckets/test-bucket/upload' => Http::response(['success' => true], 200)
        ]);

        $this->adapter->write('folder/file.txt', 'hello world', new Config());

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://larabucket-server.test/api/buckets/test-bucket/upload'
                && $request->header('Authorization')[0] === 'Bearer sk_secret'
                && $request->isMultipart()
                // Check if paths and contents are passed
                && str_contains($request->body(), 'hello world')
                && str_contains($request->body(), 'folder');
        });
    }

    /** @test */
    public function read_sends_correct_get_request()
    {
        Http::fake([
            'larabucket-server.test/api/files/download*' => Http::response('file contents here', 200)
        ]);

        $contents = $this->adapter->read('path/to/file.txt');

        $this->assertEquals('file contents here', $contents);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'http://larabucket-server.test/api/files/download?path=path%2Fto%2Ffile.txt';
        });
    }

    /** @test */
    public function delete_sends_correct_delete_request()
    {
        Http::fake([
            'larabucket-server.test/api/files*' => Http::response(['success' => true], 200)
        ]);

        $this->adapter->delete('path/to/file.txt');

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'http://larabucket-server.test/api/files?path=path%2Fto%2Ffile.txt';
        });
    }

    /** @test */
    public function list_contents_sends_correct_get_request_and_parses_response()
    {
        Http::fake([
            'larabucket-server.test/api/buckets/test-bucket/files*' => Http::response([
                [
                    'id' => 'f1',
                    'bucketId' => 'b1',
                    'name' => 'subfolder',
                    'type' => 'folder',
                    'size' => 0,
                    'path' => '/',
                    'updatedAt' => '2023-10-15T10:00:00Z'
                ],
                [
                    'id' => 'f2',
                    'bucketId' => 'b1',
                    'name' => 'photo.jpg',
                    'type' => 'file',
                    'size' => 1024,
                    'mimeType' => 'image/jpeg',
                    'path' => '/',
                    'updatedAt' => '2023-10-15T10:00:00Z'
                ]
            ], 200)
        ]);

        $contents = $this->adapter->listContents('photos', false);
        $contents = is_array($contents) ? $contents : iterator_to_array($contents);

        $this->assertCount(2, $contents);
        
        $this->assertInstanceOf(DirectoryAttributes::class, $contents[0]);
        $this->assertEquals('photos/subfolder', $contents[0]->path());

        $this->assertInstanceOf(FileAttributes::class, $contents[1]);
        $this->assertEquals('photos/photo.jpg', $contents[1]->path());
        $this->assertEquals(1024, $contents[1]->fileSize());
        $this->assertEquals('image/jpeg', $contents[1]->mimeType());
        $this->assertEquals(strtotime('2023-10-15T10:00:00Z'), $contents[1]->lastModified());
    }

    /** @test */
    public function file_size_retrieves_correct_metadata()
    {
        Http::fake([
            'larabucket-server.test/api/files/metadata*' => Http::response([
                'size' => 2048,
                'mime_type' => 'text/plain',
                'last_modified' => 1600000000
            ], 200)
        ]);

        $attributes = $this->adapter->fileSize('document.txt');

        $this->assertEquals(2048, $attributes->fileSize());
        $this->assertEquals('document.txt', $attributes->path());
    }
}
