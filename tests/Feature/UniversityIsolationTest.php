<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use App\Filament\Resources\StudentProfiles\StudentProfileResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniversityIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_api_never_returns_another_university_data(): void
    {
        $this->seed();
        $foreign = $this->createForeignUniversityData();
        $baak = User::query()->where('email', 'baak@kampus.test')->firstOrFail();

        $this->actingAs($baak, 'sanctum')
            ->getJson('/api/v1/students?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['student_number' => $foreign['student']->student_number]);

        $this->actingAs($baak, 'sanctum')
            ->getJson('/api/v1/courses?per_page=100')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonMissing(['code' => $foreign['course']->code]);
    }

    public function test_finance_api_never_returns_another_university_transactions(): void
    {
        $this->seed();
        $foreign = $this->createForeignUniversityData();
        $localEnrollment = StudentEnrollment::query()->whereHas(
            'studentProfile',
            fn ($query) => $query->where('email', 'mahasiswa@kampus.test')
        )->firstOrFail();

        Payment::query()->create([
            'student_enrollment_id' => $localEnrollment->id,
            'payment_number' => 'PAY/LOCAL/001',
            'amount' => '100000.00',
            'method' => 'transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $foreignPayment = Payment::query()->create([
            'student_enrollment_id' => $foreign['enrollment']->id,
            'payment_number' => 'PAY/FOREIGN/001',
            'amount' => '200000.00',
            'method' => 'transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();
        $localUniversityId = $localEnrollment->studyProgram->department->faculty->university_id;
        $expectedPaymentCount = Payment::query()
            ->whereHas('enrollment.studyProgram.department.faculty', fn ($query) => $query->where('faculties.university_id', $localUniversityId))
            ->count();

        $this->actingAs($finance, 'sanctum')
            ->getJson('/api/v1/invoices?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['invoice_number' => $foreign['invoice']->invoice_number]);

        $this->actingAs($finance, 'sanctum')
            ->getJson('/api/v1/payments?per_page=100')
            ->assertOk()
            ->assertJsonCount($expectedPaymentCount, 'data')
            ->assertJsonFragment(['payment_number' => 'PAY/LOCAL/001'])
            ->assertJsonMissing(['payment_number' => $foreignPayment->payment_number]);
    }

    public function test_filament_resource_queries_are_scoped_to_the_staff_university(): void
    {
        $this->seed();
        $this->createForeignUniversityData();

        $baak = User::query()->where('email', 'baak@kampus.test')->firstOrFail();
        $this->actingAs($baak);
        $this->assertSame(1, StudentProfileResource::getEloquentQuery()->count());
        $this->assertSame(5, CourseResource::getEloquentQuery()->count());

        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();
        $this->actingAs($finance);
        $this->assertSame(1, StudentInvoiceResource::getEloquentQuery()->count());
    }

    private function createForeignUniversityData(): array
    {
        $university = University::query()->create(['name' => 'Universitas Samudra Digital', 'short_name' => 'USD', 'code' => 'USD']);
        $faculty = Faculty::query()->create(['university_id' => $university->id, 'name' => 'Fakultas Sains Data', 'code' => 'FSD']);
        $department = Department::query()->create(['faculty_id' => $faculty->id, 'name' => 'Departemen Analitika', 'code' => 'AN']);
        $program = StudyProgram::query()->create(['department_id' => $department->id, 'name' => 'S1 Sains Data', 'code' => 'SD', 'level' => 'S1']);
        $course = Course::query()->create(['university_id' => $university->id, 'code' => 'SD999', 'name' => 'Data Rahasia Institusi Lain', 'theory_credits' => 3]);
        $user = User::factory()->create(['email' => 'student@foreign.test']);
        $student = StudentProfile::query()->create([
            'user_id' => $user->id,
            'student_number' => 'USD-2026-00001',
            'full_name' => 'Nadira Samudra',
            'email' => $user->email,
        ]);
        $enrollment = StudentEnrollment::query()->create([
            'student_profile_id' => $student->id,
            'study_program_id' => $program->id,
            'cohort' => 2026,
            'status' => 'active',
        ]);
        $invoice = StudentInvoice::query()->create([
            'student_enrollment_id' => $enrollment->id,
            'invoice_number' => 'INV/FOREIGN/001',
            'total_amount' => '5000000.00',
            'paid_amount' => '0.00',
            'status' => 'issued',
        ]);

        return compact('university', 'course', 'student', 'enrollment', 'invoice');
    }
}
