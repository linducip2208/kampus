<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_shared_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_administrator_opens_the_tabler_workspace_and_can_reach_legacy_management_during_migration(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard administrasi')
            ->assertSee('Mahasiswa aktif')
            ->assertSee('Piutang berjalan')
            ->assertSee('/admin/legacy/student-profiles', false);

        $this->actingAs($admin)->get('/admin/legacy')->assertOk();
    }

    public function test_dashboard_widgets_are_filtered_by_staff_permissions(): void
    {
        $this->seed();
        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();
        $baak = User::query()->where('email', 'baak@kampus.test')->firstOrFail();

        $this->actingAs($finance)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Pembayaran diterima')
            ->assertSee('Piutang berjalan')
            ->assertDontSee('Mahasiswa aktif');

        $this->actingAs($baak)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Mahasiswa aktif')
            ->assertSee('KRS menunggu')
            ->assertDontSee('Piutang berjalan');
    }

    public function test_student_and_lecturer_cannot_open_staff_workspace(): void
    {
        $this->seed();

        foreach (['mahasiswa@kampus.test', 'dosen@kampus.test'] as $email) {
            $this->actingAs(User::query()->where('email', $email)->firstOrFail())
                ->get('/admin')
                ->assertForbidden();
        }
    }
}
