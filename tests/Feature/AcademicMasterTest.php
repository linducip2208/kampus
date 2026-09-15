<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\University;
use App\Models\User;
use App\Services\Academic\AcademicMasterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_holiday_category_equivalence_rules(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $university = University::query()->firstOrFail();

        $event = app(AcademicMasterService::class)->createCalendarEvent($university->id, [
            'title' => 'Pengisian KRS', 'starts_on' => '2026-08-01', 'ends_on' => '2026-08-14',
        ], $admin);
        $this->assertSame('Pengisian KRS', $event->title);

        $holiday = app(AcademicMasterService::class)->createHoliday($university->id, ['name' => 'HUT RI', 'date' => '2026-08-17', 'is_national' => true], $admin);
        $this->assertTrue(app(AcademicMasterService::class)->isHoliday($university->id, '2026-08-17'));
        $this->assertFalse(app(AcademicMasterService::class)->isHoliday($university->id, '2026-08-18'));

        $category = app(AcademicMasterService::class)->createCategory($university->id, ['name' => 'MK Wajib Prodi', 'code' => 'MKW']);
        $course = Course::query()->where('university_id', $university->id)->firstOrFail();
        $classified = app(AcademicMasterService::class)->classifyCourse($course, $category->id, 'wajib', $admin);
        $this->assertSame('wajib', $classified->course_type);

        $other = Course::query()->where('university_id', $university->id)->where('id', '!=', $course->id)->firstOrFail();
        $equivalence = app(AcademicMasterService::class)->addEquivalence($course, $other, 'Kurikulum baru.', $admin);
        $this->assertSame($course->id, $equivalence->old_course_id);

        $rule = app(AcademicMasterService::class)->setRule($university->id, 'min_attendance_percent', 75, 'Syarat ujian.', $admin);
        $this->assertSame(75, $rule->value);

        try {
            app(AcademicMasterService::class)->createHoliday($university->id, ['name' => 'Duplikat', 'date' => '2026-08-17'], $admin);
            $this->fail('Libur duplikat seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date', $e->errors());
        }
    }

    public function test_academic_master_page_renders(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.academic-master.index'))->assertOk();
    }
}
