<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\StudentEnrollment;
use App\Models\University;
use App\Models\User;
use App\Services\Academic\CampusActivityService;
use App\Services\Library\LibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusServicesTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkflow(string $module): void
    {
        $university = University::query()->firstOrFail();
        $workflow = ApprovalWorkflow::query()->firstOrCreate(
            ['university_id' => $university->id, 'module' => $module],
            ['name' => "Workflow {$module}", 'is_active' => true],
        );
        ApprovalStep::query()->firstOrCreate(
            ['approval_workflow_id' => $workflow->id, 'step_order' => 1],
            ['label' => 'Persetujuan admin', 'role_name' => 'super_admin'],
        );
    }

    public function test_library_borrow_return_with_fine(): void
    {
        $this->seed();
        $university = University::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $actor = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $book = app(LibraryService::class)->addBook($university->id, [
            'title' => 'Clean Architecture', 'author' => 'Robert C. Martin', 'isbn' => '9780134494166', 'copies_total' => 2,
        ]);
        $loan = app(LibraryService::class)->borrow($book, $enrollment, $actor);
        $this->assertSame(1, $book->fresh()->copies_available);

        $returned = app(LibraryService::class)->returnBook($loan, $actor);
        $this->assertSame('returned', $returned->status);
        $this->assertSame(2, $book->fresh()->copies_available);
    }

    public function test_research_org_activity_mbkm_workflow(): void
    {
        $this->seed();
        $this->makeWorkflow('mbkm');
        $university = University::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $research = app(CampusActivityService::class)->createResearch($university->id, [
            'title' => 'Optimasi Penjadwalan Kuliah', 'scheme' => 'internal', 'year' => 2026, 'budget' => '50000000.00',
        ], $admin);
        $ongoing = app(CampusActivityService::class)->setResearchStatus($research, 'ongoing', $admin);
        $this->assertSame('ongoing', $ongoing->status);

        $org = app(CampusActivityService::class)->createOrganization($university->id, ['name' => 'Himpunan Informatika', 'code' => 'HIMATIF']);
        $member = app(CampusActivityService::class)->joinOrganization($org, $enrollment, 'chair');
        $this->assertSame('chair', $member->role);

        $activity = app(CampusActivityService::class)->proposeActivity($org, 'Seminar AI', now()->addWeek()->toDateTimeString(), now()->addWeek()->addHours(3)->toDateTimeString(), $student, 'Aula Utama');
        $decided = app(CampusActivityService::class)->decideActivity($activity, 'approved', $admin);
        $this->assertSame('approved', $decided->status);

        $program = app(CampusActivityService::class)->createMbkmProgram($university->id, ['name' => 'Magang Merdeka Batch 7', 'kind' => 'magang', 'partner' => 'PT Digital', 'quota' => 10]);
        $registration = app(CampusActivityService::class)->registerMbkm($program, $enrollment, $student);
        $this->assertSame('proposed', $registration->status);
        $approved = app(CampusActivityService::class)->decideMbkm($registration, 'approved', $admin, 20);
        $this->assertSame(20, $approved->credits_recognized);
    }
}
