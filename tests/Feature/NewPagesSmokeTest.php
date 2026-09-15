<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_portal_pages_render(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.academic-lifecycle.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.campus-services.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.ops.index'))->assertOk();
        $this->actingAs($student)->get(route('portal.services.index'))->assertOk();
    }

    public function test_new_apis_respond_ok(): void
    {
        $this->seed();
        $baak = User::query()->where('email', 'baak@kampus.test')->firstOrFail();

        foreach (['/api/v1/scholarships', '/api/v1/thesis', '/api/v1/graduations', '/api/v1/library-books', '/api/v1/research', '/api/v1/mbkm'] as $endpoint) {
            $this->actingAs($baak, 'sanctum')->getJson($endpoint.'?per_page=5')->assertOk();
        }
    }
}
