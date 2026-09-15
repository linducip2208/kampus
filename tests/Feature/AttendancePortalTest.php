<?php

namespace Tests\Feature;

use App\Models\LectureMeeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_open_tabler_attendance_page(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail())
            ->get(route('portal.attendance.index'))
            ->assertOk()
            ->assertSee('Presensi perkuliahan')
            ->assertSee('Algoritma dan Pemrograman');
    }

    public function test_lecturer_can_open_attendance_workspace_and_start_pin_session(): void
    {
        $this->seed();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $meeting = LectureMeeting::query()->firstOrFail();
        $meeting->attendanceSessions()->delete();

        $this->actingAs($lecturer)
            ->get(route('lecturer.attendance.index'))
            ->assertOk()
            ->assertSee('Presensi kelas');

        $this->post(route('lecturer.attendance.open', $meeting), [
            'method' => 'pin',
            'duration' => 20,
            'pin' => '2468',
        ])->assertRedirect()->assertSessionHas('attendance_credential', '2468');

        $this->assertDatabaseHas('attendance_sessions', [
            'lecture_meeting_id' => $meeting->id,
            'method' => 'pin',
            'status' => 'open',
        ]);
    }

    public function test_student_cannot_open_lecturer_attendance_workspace(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail())
            ->get(route('lecturer.attendance.index'))
            ->assertForbidden();
    }
}
