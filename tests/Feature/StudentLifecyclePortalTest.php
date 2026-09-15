<?php

namespace Tests\Feature;

use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\StudentLeaveRequest;
use App\Models\StudentReactivationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLifecyclePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_and_reactivation_are_processed_end_to_end_through_portals(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $baak = User::query()->where('email', 'baak@kampus.test')->firstOrFail();
        $semester = Semester::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();

        $this->actingAs($student)
            ->get(route('portal.lifecycle.index'))
            ->assertOk()
            ->assertSee('Ajukan cuti');

        $this->actingAs($student)
            ->post(route('portal.lifecycle.leave'), [
                'semester_id' => $semester->id,
                'reason' => 'Pemulihan kesehatan berdasarkan rekomendasi dokter keluarga.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $leave = StudentLeaveRequest::query()->firstOrFail();

        $this->actingAs($baak)
            ->get(route('admin.student-lifecycle.index'))
            ->assertOk()
            ->assertSee('Pemulihan kesehatan');

        $this->actingAs($baak)
            ->post(route('admin.student-lifecycle.leaves.approve', $leave))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('leave', $enrollment->fresh()->status);
        $this->assertDatabaseHas('student_status_histories', [
            'student_enrollment_id' => $enrollment->id,
            'from_status' => 'active',
            'to_status' => 'leave',
        ]);

        $this->actingAs($student)
            ->get(route('portal.lifecycle.index'))
            ->assertOk()
            ->assertSee('Ajukan aktif kembali');

        $this->actingAs($student)
            ->post(route('portal.lifecycle.reactivate'), [
                'semester_id' => $semester->id,
                'reason' => 'Kondisi kesehatan telah pulih dan siap mengikuti perkuliahan.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $reactivation = StudentReactivationRequest::query()->firstOrFail();

        $this->actingAs($baak)
            ->post(route('admin.student-lifecycle.reactivations.approve', $reactivation))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('active', $enrollment->fresh()->status);
        $this->assertDatabaseHas('student_status_histories', [
            'student_enrollment_id' => $enrollment->id,
            'from_status' => 'leave',
            'to_status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'student_reactivation.approved',
            'entity_id' => $reactivation->id,
        ]);
    }

    public function test_baak_can_reject_leave_without_changing_student_status(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $baak = User::query()->where('email', 'baak@kampus.test')->firstOrFail();
        $semester = Semester::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();

        $this->actingAs($student)->post(route('portal.lifecycle.leave'), [
            'semester_id' => $semester->id,
            'reason' => 'Permohonan cuti untuk kebutuhan keluarga selama satu semester.',
        ]);
        $leave = StudentLeaveRequest::query()->firstOrFail();

        $this->actingAs($baak)
            ->post(route('admin.student-lifecycle.leaves.reject', $leave), [
                'reason' => 'Dokumen pendukung belum lengkap.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('active', $enrollment->fresh()->status);
        $this->assertDatabaseHas('student_leave_requests', [
            'id' => $leave->id,
            'status' => 'rejected',
            'processed_by' => $baak->id,
            'rejection_reason' => 'Dokumen pendukung belum lengkap.',
        ]);
        $this->assertDatabaseHas('approval_actions', [
            'acted_by' => $baak->id,
            'action' => 'rejected',
        ]);
    }

    public function test_unrelated_staff_cannot_process_student_lifecycle(): void
    {
        $this->seed();
        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();

        $this->actingAs($finance)
            ->get(route('admin.student-lifecycle.index'))
            ->assertForbidden();
    }
}
