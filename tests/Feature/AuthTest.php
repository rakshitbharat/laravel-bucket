<?php

namespace LaraBucket\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraBucket\Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_login_with_correct_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token'
            ])
            ->assertJsonPath('user.email', 'admin@test.com');
    }

    /** @test */
    public function admin_cannot_login_with_incorrect_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function login_requires_valid_inputs()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
