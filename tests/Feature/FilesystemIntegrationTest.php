<?php

namespace LaraBucket\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use LaraBucket\Tests\TestCase;

class FilesystemIntegrationTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        // Register a larabucket client disk configuration
        $app['config']->set('filesystems.disks.larabucket_client', [
            'driver'  => 'larabucket',
            'api_url' => 'http://larabucket-server.test/api',
            'bucket'  => 'test-bucket',
            'secret'  => 'sk_client_secret',
        ]);
    }

    /** @test */
    public function custom_larabucket_driver_is_registered_correctly()
    {
        $disk = Storage::disk('larabucket_client');
        
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $disk);
        $this->assertInstanceOf(\League\Flysystem\Filesystem::class, $disk->getDriver());
    }

    /** @test */
    public function can_perform_put_operation_using_storage_facade()
    {
        Http::fake([
            'larabucket-server.test/api/buckets/test-bucket/upload' => Http::response(['success' => true], 200)
        ]);

        $result = Storage::disk('larabucket_client')->put('uploads/file.txt', 'file contents');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://larabucket-server.test/api/buckets/test-bucket/upload'
                && $request->header('Authorization')[0] === 'Bearer sk_client_secret'
                && $request->header('X-Bucket')[0] === 'test-bucket'
                && $request->isMultipart()
                && str_contains($request->body(), 'file contents')
                && str_contains($request->body(), 'uploads');
        });
    }

    /** @test */
    public function can_perform_get_operation_using_storage_facade()
    {
        Http::fake([
            'larabucket-server.test/api/files/download*' => Http::response('retrieved content', 200)
        ]);

        $content = Storage::disk('larabucket_client')->get('uploads/file.txt');

        $this->assertEquals('retrieved content', $content);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'http://larabucket-server.test/api/files/download?path=uploads%2Ffile.txt'
                && $request->header('Authorization')[0] === 'Bearer sk_client_secret'
                && $request->header('X-Bucket')[0] === 'test-bucket';
        });
    }

    /** @test */
    public function can_perform_exists_check_using_storage_facade()
    {
        Http::fake([
            'larabucket-server.test/api/files*' => Http::response('', 200)
        ]);

        $exists = Storage::disk('larabucket_client')->exists('uploads/file.txt');

        $this->assertTrue($exists);

        Http::assertSent(function ($request) {
            return $request->method() === 'HEAD'
                && $request->url() === 'http://larabucket-server.test/api/files?path=uploads%2Ffile.txt'
                && $request->header('Authorization')[0] === 'Bearer sk_client_secret'
                && $request->header('X-Bucket')[0] === 'test-bucket';
        });
    }

    /** @test */
    public function can_perform_delete_operation_using_storage_facade()
    {
        Http::fake([
            'larabucket-server.test/api/files*' => Http::response(['success' => true], 200)
        ]);

        $deleted = Storage::disk('larabucket_client')->delete('uploads/file.txt');

        $this->assertTrue($deleted);

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'http://larabucket-server.test/api/files?path=uploads%2Ffile.txt'
                && $request->header('Authorization')[0] === 'Bearer sk_client_secret'
                && $request->header('X-Bucket')[0] === 'test-bucket';
        });
    }
}
