<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'user' => ['id', 'name', 'email', 'created_at'],
                         'token'
                     ]
                 ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_registration_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => '',
            'password' => '123',

        ]);
        $response->assertStatus(422);

    }

    public function test_registration_fails_if_email_already_exists()
    {
        User::factory()->create(['email' => 'test@gmail.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'existing@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);

    }
}
