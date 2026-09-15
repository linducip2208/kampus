<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\LibraryBook;
use App\Models\MbkmProgram;
use App\Models\ResearchProject;
use App\Models\Scholarship;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\ThesisProposal;
use App\Models\University;
use App\Models\User;
use App\Policies\CampusPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewDomainIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_domain_apis_never_leak_foreign_university(): void
    {
        $this->seed();
        $foreign = $this->createForeignDomainData();
        $baak = User::query()->where('email', 'baak@kampus.test')->firstOrFail();

        $this->actingAs($baak, 'sanctum')->getJson('/api/v1/scholarships?per_page=100')->assertOk()
            ->assertJsonMissing(['code' => $foreign['scholarship']->code]);
        $this->actingAs($baak, 'sanctum')->getJson('/api/v1/library-books?per_page=100')->assertOk()
            ->assertJsonMissing(['isbn' => $foreign['book']->isbn]);
        $this->actingAs($baak, 'sanctum')->getJson('/api/v1/research?per_page=100')->assertOk()
            ->assertJsonMissing(['title' => $foreign['research']->title]);
        $this->actingAs($baak, 'sanctum')->getJson('/api/v1/mbkm?per_page=100')->assertOk()
            ->assertJsonMissing(['name' => $foreign['mbkm']->name]);
        $this->actingAs($baak, 'sanctum')->getJson('/api/v1/thesis?per_page=100')->assertOk()
            ->assertJsonMissing(['title' => $foreign['thesis']->title]);
    }

    public function test_policy_denies_cross_university_records(): void
    {
        $this->seed();
        $foreign = $this->createForeignDomainData();
        $baak = User::query()->where('email', 'baak@kampus.test')->firstOrFail();
        $policy = app(CampusPolicy::class);

        // Direct university models fall back to generic university_id check (true only for own).
        $this->assertFalse($this->invokeRecordCheck($policy, $baak, $foreign['scholarship']));
        $this->assertFalse($this->invokeRecordCheck($policy, $baak, $foreign['book']));

        // Enrollment-linked models use explicit branches.
        $this->assertFalse($this->invokeRecordCheck($policy, $baak, $foreign['thesis']));
    }

    private function invokeRecordCheck(CampusPolicy $policy, User $user, object $record): bool
    {
        $method = new \ReflectionMethod($policy, 'recordBelongsToUniversity');
        $method->setAccessible(true);
        $universityId = $user->roles()->firstOrFail()->pivot->university_id;

        return $method->invoke($policy, $record, $universityId);
    }

    private function createForeignDomainData(): array
    {
        $university = University::query()->create(['name' => 'Universitas Asing', 'short_name' => 'UA', 'code' => 'UA']);
        $faculty = Faculty::query()->create(['university_id' => $university->id, 'name' => 'Fakultas Asing', 'code' => 'FA']);
        $department = Department::query()->create(['faculty_id' => $faculty->id, 'name' => 'Departemen Asing', 'code' => 'DA']);
        $program = StudyProgram::query()->create(['department_id' => $department->id, 'name' => 'S1 Asing', 'code' => 'AS', 'level' => 'S1']);
        $user = User::factory()->create(['email' => 'asing@foreign.test']);
        $student = StudentProfile::query()->create(['user_id' => $user->id, 'student_number' => 'UA-001', 'full_name' => 'Asing', 'email' => $user->email]);
        $enrollment = StudentEnrollment::query()->create(['student_profile_id' => $student->id, 'study_program_id' => $program->id, 'cohort' => 2026, 'status' => 'active']);

        $scholarship = Scholarship::query()->create(['university_id' => $university->id, 'name' => 'Asing Scholarship', 'code' => 'ASING-001', 'amount' => '1000000.00', 'quota' => 1]);
        $book = LibraryBook::query()->create(['university_id' => $university->id, 'title' => 'Buku Asing', 'isbn' => 'ISBN-ASING-001', 'copies_total' => 1, 'copies_available' => 1]);
        $research = ResearchProject::query()->create(['university_id' => $university->id, 'title' => 'Riset Asing Rahasia', 'year' => 2026]);
        $mbkm = MbkmProgram::query()->create(['university_id' => $university->id, 'name' => 'Program Asing Rahasia']);
        $thesis = ThesisProposal::query()->create(['student_enrollment_id' => $enrollment->id, 'title' => 'Tesis Asing Rahasia', 'status' => 'submitted', 'requested_by' => $user->id]);

        // Local markers so API returns non-empty for own university.
        $localUniversity = University::query()->where('code', 'UCN')->firstOrFail();
        Scholarship::query()->create(['university_id' => $localUniversity->id, 'name' => 'Lokal', 'code' => 'LOKAL-001', 'amount' => '1000000.00']);
        LibraryBook::query()->create(['university_id' => $localUniversity->id, 'title' => 'Buku Lokal', 'isbn' => 'ISBN-LOKAL-001']);
        Course::query()->where('university_id', $localUniversity->id)->firstOrFail();

        return compact('scholarship', 'book', 'research', 'mbkm', 'thesis');
    }
}
