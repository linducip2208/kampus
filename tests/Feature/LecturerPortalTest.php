<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LecturerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_open_real_portal_workspaces(): void
    {
        $this->seed();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();

        $this->actingAs($lecturer)->get('/lecturer')->assertOk()->assertSee('Ringkasan pengajaran');
        $this->actingAs($lecturer)->get('/lecturer/schedule')->assertOk()->assertSee('Jadwal mengajar');
        $this->actingAs($lecturer)->get('/lecturer/classes')->assertOk()->assertSee('Kelas saya');
        $this->actingAs($lecturer)->get('/lecturer/advisees')->assertOk()->assertSee('Mahasiswa bimbingan');
    }

    public function test_user_without_lecturer_profile_cannot_open_lecturer_portal(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $this->actingAs($student)->get('/lecturer')->assertForbidden();
    }
}
