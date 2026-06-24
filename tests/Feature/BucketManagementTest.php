<?php

namespace LaraBucket\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LaraBucket\Models\Bucket;
use LaraBucket\Tests\TestCase;

class BucketManagementTest extends TestCase
{
    use RefreshDatabase;

    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminToken = hash_hmac('sha256', 'admin@test.com', 'password123');
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ];
    }

    /** @test */
    public function admin_can_create_a_bucket()
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/buckets', [
                'name' => 'My Test Bucket',
                'ownerEmail' => 'owner@test.com',
                'storageLimitMb' => 500,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'name', 'slug', 'ownerEmail', 'storageLimitMb', 'secretKey', 'createdAt'
            ])
            ->assertJsonPath('name', 'My Test Bucket')
            ->assertJsonPath('slug', 'my-test-bucket')
            ->assertJsonPath('ownerEmail', 'owner@test.com')
            ->assertJsonPath('storageLimitMb', 500);

        $this->assertDatabaseHas('larabucket_buckets', [
            'name' => 'My Test Bucket',
            'slug' => 'my-test-bucket',
            'owner_email' => 'owner@test.com',
            'storage_limit_mb' => 500,
        ]);

        Storage::disk('testing_disk')->assertExists('my-test-bucket');
    }

    /** @test */
    public function bucket_names_and_slugs_must_be_unique()
    {
        Bucket::create([
            'name' => 'Duplicate Bucket',
            'slug' => 'duplicate-bucket',
            'secret_key' => 'sk_123',
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/buckets', [
                'name' => 'Duplicate Bucket',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_list_buckets()
    {
        Bucket::create([
            'name' => 'Bucket 1',
            'slug' => 'bucket-1',
            'secret_key' => 'sk_1',
            'storage_limit_mb' => 100,
        ]);

        Bucket::create([
            'name' => 'Bucket 2',
            'slug' => 'bucket-2',
            'secret_key' => 'sk_2',
            'storage_limit_mb' => 200,
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/buckets');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.name', 'Bucket 1')
            ->assertJsonPath('1.name', 'Bucket 2');
    }

    /** @test */
    public function admin_can_update_a_bucket()
    {
        $bucket = Bucket::create([
            'name' => 'Original Bucket',
            'slug' => 'original-bucket',
            'secret_key' => 'sk_orig',
        ]);

        Storage::disk('testing_disk')->makeDirectory('original-bucket');
        Storage::disk('testing_disk')->put('original-bucket/test.txt', 'hello');

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson("/api/buckets/{$bucket->id}", [
                'name' => 'Updated Bucket',
                'ownerEmail' => 'new@test.com',
                'storageLimitMb' => 1200,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Bucket')
            ->assertJsonPath('slug', 'updated-bucket')
            ->assertJsonPath('ownerEmail', 'new@test.com')
            ->assertJsonPath('storageLimitMb', 1200);

        // Assert directory moved on storage disk
        Storage::disk('testing_disk')->assertMissing('original-bucket');
        Storage::disk('testing_disk')->assertExists('updated-bucket/test.txt');
    }

    /** @test */
    public function admin_can_delete_a_bucket()
    {
        $bucket = Bucket::create([
            'name' => 'Bucket To Delete',
            'slug' => 'delete-bucket',
            'secret_key' => 'sk_delete',
        ]);

        Storage::disk('testing_disk')->makeDirectory('delete-bucket');
        Storage::disk('testing_disk')->put('delete-bucket/file.txt', 'data');

        $response = $this->withHeaders($this->getAuthHeaders())
            ->deleteJson("/api/buckets/{$bucket->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('larabucket_buckets', ['id' => $bucket->id]);
        Storage::disk('testing_disk')->assertMissing('delete-bucket');
    }

    /** @test */
    public function admin_can_list_files_in_bucket()
    {
        $bucket = Bucket::create([
            'name' => 'File Bucket',
            'slug' => 'file-bucket',
            'secret_key' => 'sk_files',
        ]);

        Storage::disk('testing_disk')->makeDirectory('file-bucket/folder1');
        Storage::disk('testing_disk')->put('file-bucket/test1.txt', 'file 1 content');
        Storage::disk('testing_disk')->put('file-bucket/folder1/test2.txt', 'file 2 content');

        // List files in root of bucket
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/buckets/{$bucket->id}/files?path=/");

        $response->assertStatus(200)
            ->assertJsonCount(2) // 1 folder, 1 file
            ->assertJsonFragment(['name' => 'folder1', 'type' => 'folder'])
            ->assertJsonFragment(['name' => 'test1.txt', 'type' => 'file', 'size' => 14]);

        // List files in folder1
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson("/api/buckets/{$bucket->id}/files?path=/folder1");

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'test2.txt', 'type' => 'file', 'size' => 14, 'path' => '/folder1']);
    }

    /** @test */
    public function admin_can_create_folders_and_delete_files_by_id()
    {
        $bucket = Bucket::create([
            'name' => 'Folder Bucket',
            'slug' => 'folder-bucket',
            'secret_key' => 'sk_folders',
            'size_used' => 10,
        ]);

        // 1. Create a folder
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/buckets/{$bucket->id}/folders", [
                'name' => 'New Subfolder',
                'path' => '/',
            ]);

        $response->assertStatus(201);
        Storage::disk('testing_disk')->assertExists('folder-bucket/new-subfolder');

        // 2. Put a file and delete it by base64 ID
        Storage::disk('testing_disk')->put('folder-bucket/new-subfolder/doc.pdf', 'pdf data');
        $fileId = base64_encode('folder-bucket/new-subfolder/doc.pdf');

        $response = $this->withHeaders($this->getAuthHeaders())
            ->deleteJson("/api/files/{$fileId}");

        $response->assertStatus(200);
        Storage::disk('testing_disk')->assertMissing('folder-bucket/new-subfolder/doc.pdf');

        // Bucket size should decrease
        $this->assertEquals(2, $bucket->fresh()->size_used); // 10 initial - 8 size of 'pdf data' = 2
    }
}
