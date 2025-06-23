<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => bcrypt('password'),

        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@gmail.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at'],
                    'token',
                ]
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
         $user = User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@gmail.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }


    public function test_login_rate_limiting_after_tree_attempts()
    {

        $email = 'user@gmail.com';
        $ip = '127.0.0.1';
        $key = "login_attempts:$email:$ip";

        // clear my previous rate limits
        RateLimiter::clear($key);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'wrong-password'
            ])->assertStatus(401);

        }

        // 6th attempt should be blocked
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'wrong-password'
        ]);

        // set the minute
        $seconds = RateLimiter::availableIn($key);
            $minute = ceil($seconds/60);

        $response->assertStatus(429)
            ->assertJson([
                'status' => false,
                'message' => 'Please try again in minutes.',
                'message' => 'Too many login attempts',
                'data' => ['message' => "Please try again in $minute minutes."],

            ]);

    }
}
