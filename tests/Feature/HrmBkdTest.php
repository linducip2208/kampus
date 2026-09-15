<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LecturerProfile;
use App\Models\Semester;
use App\Models\University;
use App\Models\User;
use App\Services\Hrm\BkdService;
use App\Services\Hrm\HrmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrmBkdTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_attendance_leave_lifecycle(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $university = University::query()->firstOrFail();
        $employee = Employee::query()->firstOrFail();

        $unit = app(HrmService::class)->createUnit($university->id, ['name' => 'Fakultas Teknik', 'code' => 'FT-HRM']);
        $position = app(HrmService::class)->createPosition($university->id, ['name' => 'Lektor', 'code' => 'LEK', 'unit_id' => $unit->id]);

        $contract = app(HrmService::class)->createContract($employee, [
            'position_id' => $position->id, 'type' => 'tetap', 'starts_on' => '2026-01-01', 'salary' => '12000000.00',
        ], $admin);
        $this->assertStringStartsWith('SPK/', $contract->contract_number);

        $attendance = app(HrmService::class)->recordAttendance($employee, '2026-09-15', '08:00', '17:00', 'present', null, $admin);
        $this->assertSame('present', $attendance->status);

        $leave = app(HrmService::class)->requestLeave($employee, [
            'kind' => 'annual', 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-03', 'reason' => 'Liburan keluarga.',
        ], $admin);
        $decided = app(HrmService::class)->decideLeave($leave, 'approved', $admin);
        $this->assertSame('approved', $decided->status);

        app(HrmService::class)->addEducation($employee, ['level' => 'S3', 'institution' => 'ITB', 'major' => 'Informatika', 'graduation_year' => 2020]);
        app(HrmService::class)->addCertification($employee, ['name' => 'AWS Architect', 'issuer' => 'Amazon', 'issued_on' => '2024-01-01']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'hrm.contract_created']);

        try {
            app(HrmService::class)->requestLeave($employee, [
                'kind' => 'annual', 'starts_on' => '2026-10-02', 'ends_on' => '2026-10-04', 'reason' => 'Tumpang tindih.',
            ], $admin);
            $this->fail('Cuti bertabrakan seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('leave', $e->errors());
        }
    }

    public function test_bkd_submit_approve_with_summary(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $lecturerUser = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $lecturer = LecturerProfile::query()->firstOrFail();
        $semester = Semester::query()->firstOrFail();

        $workload = app(BkdService::class)->openWorkload($lecturer, $semester->id);
        app(BkdService::class)->addActivity($workload, ['category' => 'teaching', 'title' => 'Algoritma 4 SKS', 'sks' => '4.00'], $lecturerUser);
        app(BkdService::class)->addActivity($workload, ['category' => 'research', 'title' => 'Penelitian ML', 'sks' => '4.00'], $lecturerUser);
        app(BkdService::class)->addActivity($workload, ['category' => 'service', 'title' => 'Pengmas desa', 'sks' => '2.00'], $lecturerUser);
        app(BkdService::class)->addActivity($workload, ['category' => 'supporting', 'title' => 'Panitia wisuda', 'sks' => '2.00'], $lecturerUser);

        $summary = app(BkdService::class)->summary($workload);
        $this->assertSame('12.00', $summary['total_sks']);
        $this->assertTrue($summary['fulfilled']);

        $submitted = app(BkdService::class)->submit($workload, $lecturerUser);
        $this->assertSame('submitted', $submitted->status);

        $approved = app(BkdService::class)->approve($submitted, $admin);
        $this->assertSame('approved', $approved->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'bkd.approved']);
    }

    public function test_hrm_and_bkd_pages_render(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.hrm.index'))->assertOk();
        $this->actingAs($lecturer)->get(route('lecturer.bkd.index'))->assertOk();
    }
}
