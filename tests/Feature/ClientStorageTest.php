<?php

namespace LaraBucket\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LaraBucket\Models\Bucket;
use LaraBucket\Tests\TestCase;

class ClientStorageTest extends TestCase
{
    use RefreshDatabase;

    protected Bucket $bucket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bucket = Bucket::create([
            'name' => 'Client App Bucket',
            'slug' => 'client-bucket',
            'secret_key' => 'sk_client_secret_key_123',
            'storage_limit_mb' => 2, // 2MB
        ]);

        Storage::disk('testing_disk')->makeDirectory('client-bucket');
    }

    protected function getClientHeaders(): array
    {
        return [
            'X-API-KEY' => 'sk_client_secret_key_123',
        ];
    }

    /** @test */
    public function client_can_upload_a_file()
    {
        $file = UploadedFile::fake()->create('profile.jpg', 500); // 500KB

        $response = $this->withHeaders($this->getClientHeaders())
            ->postJson("/api/buckets/{$this->bucket->slug}/upload", [
                'file' => $file,
                'path' => 'avatars',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'url', 'path'])
            ->assertJsonPath('success', true);

        Storage::disk('testing_disk')->assertExists('client-bucket/avatars/profile.jpg');
        $this->assertEquals(512000, $this->bucket->fresh()->size_used); // 500KB in bytes
    }

    /** @test */
    public function client_cannot_upload_if_storage_limit_is_exceeded()
    {
        // Limit is 2MB. We upload 2.5MB file.
        $file = UploadedFile::fake()->create('huge.zip', 2600); // 2600KB (~2.5MB)

        $response = $this->withHeaders($this->getClientHeaders())
            ->postJson("/api/buckets/{$this->bucket->slug}/upload", [
                'file' => $file,
                'path' => '/',
            ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Bucket storage limit exceeded']);

        Storage::disk('testing_disk')->assertMissing('client-bucket/huge.zip');
    }

    /** @test */
    public function client_can_check_if_file_exists()
    {
        // 1. Check file missing
        $response = $this->withHeaders($this->getClientHeaders())
            ->head('/api/files?path=missing.txt');
        $response->assertStatus(404);

        // 2. Put file and check again
        Storage::disk('testing_disk')->put('client-bucket/exists.txt', 'some content');
        
        $response = $this->withHeaders($this->getClientHeaders())
            ->head('/api/files?path=exists.txt');
        $response->assertStatus(200);
    }

    /** @test */
    public function client_can_download_file()
    {
        Storage::disk('testing_disk')->put('client-bucket/docs/manual.pdf', 'pdf binary content');

        $response = $this->withHeaders($this->getClientHeaders())
            ->get('/api/files/download?path=docs/manual.pdf');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertSee('pdf binary content');
    }

    /** @test */
    public function client_can_delete_file()
    {
        Storage::disk('testing_disk')->put('client-bucket/avatars/old.png', 'image content');
        $this->bucket->update(['size_used' => 1000]);

        $response = $this->withHeaders($this->getClientHeaders())
            ->deleteJson('/api/files?path=avatars/old.png');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        Storage::disk('testing_disk')->assertMissing('client-bucket/avatars/old.png');
        $this->assertEquals(1000 - 13, $this->bucket->fresh()->size_used); // 13 is size of 'image content'
    }

    /** @test */
    public function client_can_get_file_metadata()
    {
        Storage::disk('testing_disk')->put('client-bucket/photo.jpg', 'fake-jpg-content');

        $response = $this->withHeaders($this->getClientHeaders())
            ->getJson('/api/files/metadata?path=photo.jpg');

        $response->assertStatus(200)
            ->assertJsonStructure(['size', 'mime_type', 'last_modified'])
            ->assertJsonPath('size', 16)
            ->assertJsonPath('mime_type', 'image/jpeg');
    }

    /** @test */
    public function client_can_copy_file()
    {
        Storage::disk('testing_disk')->put('client-bucket/original.txt', 'copy source data');
        $this->bucket->update(['size_used' => 16]);

        $response = $this->withHeaders($this->getClientHeaders())
            ->postJson('/api/files/copy', [
                'source' => 'original.txt',
                'destination' => 'copied.txt',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        Storage::disk('testing_disk')->assertExists('client-bucket/copied.txt');
        $this->assertEquals('copy source data', Storage::disk('testing_disk')->get('client-bucket/copied.txt'));
        $this->assertEquals(32, $this->bucket->fresh()->size_used); // 16 + 16
    }
}
