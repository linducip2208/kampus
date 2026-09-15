<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_and_login_are_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Satu kendali');
        $this->get('/docs')->assertOk()->assertSee('Akun demo');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin');
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_authenticated_student_can_open_portal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/portal')->assertForbidden();
    }

    public function test_seeded_student_can_open_khs_and_transcript(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $this->actingAs($student)
            ->get('/portal/academic-record')
            ->assertOk()
            ->assertSee('KHS & transkrip')
            ->assertSee('IPK kumulatif')
            ->assertSee('Algoritma dan Pemrograman');
    }
}
