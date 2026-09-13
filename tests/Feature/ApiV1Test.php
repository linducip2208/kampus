<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_token_can_read_authorized_courses(): void
    {
        $this->seed();
        $response = $this->postJson('/api/v1/auth/token', ['email' => 'admin@kampus.test', 'password' => 'password', 'device_name' => 'phpunit']);
        $response->assertOk()->assertJsonStructure(['token', 'user']);

        $this->withToken($response->json('token'))->getJson('/api/v1/courses')->assertOk()->assertJsonPath('data.0.code', 'IF101');
    }

    public function test_finance_token_is_denied_from_courses_endpoint(): void
    {
        $this->seed();
        $response = $this->postJson('/api/v1/auth/token', ['email' => 'finance@kampus.test', 'password' => 'password', 'device_name' => 'phpunit']);

        $this->withToken($response->json('token'))->getJson('/api/v1/courses')->assertForbidden();
    }
}
