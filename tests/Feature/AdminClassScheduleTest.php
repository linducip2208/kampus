<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_staff_can_open_tabler_schedule_workspace(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('email', 'admin@kampus.test')->firstOrFail())
            ->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSee('Jadwal kuliah')
            ->assertSee('Algoritma dan Pemrograman');
    }

    public function test_schedule_form_uses_collision_service(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $section = ClassSection::query()->where('room', 'R. 102')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.schedules.index'))
            ->post(route('admin.schedules.store'), [
                'class_section_id' => $section->id,
                'day_of_week' => 1,
                'starts_at' => '09:00',
                'ends_at' => '11:00',
                'room' => 'R. 101',
            ])
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHasErrors('room');
    }

    public function test_student_cannot_open_schedule_administration(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail())
            ->get(route('admin.schedules.index'))
            ->assertForbidden();
    }
}
