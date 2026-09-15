<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\LecturerProfile;
use App\Models\Semester;
use App\Services\Academic\ClassScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClassScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClassScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClassScheduleService::class);
        $this->seed();
    }

    public function test_it_rejects_room_collision_in_the_same_semester(): void
    {
        $section = $this->newSection();

        $this->expectValidationError('room', fn () => $this->service->create($section, [
            'day_of_week' => 1,
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'room' => 'R. 101',
        ]));
    }

    public function test_it_rejects_lecturer_collision_in_the_same_semester(): void
    {
        $lecturer = LecturerProfile::query()->firstOrFail();
        $section = $this->newSection('R. 999', $lecturer);

        $this->expectValidationError('lecturer', fn () => $this->service->create($section, [
            'day_of_week' => 1,
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'room' => 'R. 999',
        ]));
    }

    public function test_it_rejects_overlapping_schedules_for_the_same_class(): void
    {
        $section = $this->newSection('R. 999');
        $this->service->create($section, [
            'day_of_week' => 5,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'room' => 'R. 999',
        ]);

        $this->expectValidationError('starts_at', fn () => $this->service->create($section, [
            'day_of_week' => 5,
            'starts_at' => '09:30',
            'ends_at' => '11:00',
            'room' => 'R. 998',
        ]));
    }

    public function test_it_accepts_adjacent_non_overlapping_schedule_and_audits_it(): void
    {
        $section = $this->newSection('R. 101');

        $schedule = $this->service->create($section, [
            'day_of_week' => 1,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'room' => 'R. 101',
        ]);

        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'starts_at' => '10:00:00']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'academic.schedule_created', 'entity_id' => $schedule->id]);
    }

    public function test_it_rejects_invalid_time_range(): void
    {
        $section = $this->newSection('R. 999');

        $this->expectValidationError('ends_at', fn () => $this->service->create($section, [
            'day_of_week' => 6,
            'starts_at' => '10:00',
            'ends_at' => '09:00',
            'room' => 'R. 999',
        ]));
    }

    private function newSection(string $room = 'R. 998', ?LecturerProfile $lecturer = null): ClassSection
    {
        $semester = Semester::query()->firstOrFail();
        $sourceCourse = Course::query()->firstOrFail();
        $course = Course::create([
            'university_id' => $sourceCourse->university_id,
            'code' => 'IF'.random_int(700, 999),
            'name' => 'Topik Komputasi Lanjut',
            'theory_credits' => 2,
            'practical_credits' => 0,
            'recommended_term' => 5,
        ]);
        $offering = CourseOffering::create(['semester_id' => $semester->id, 'course_id' => $course->id, 'status' => 'published']);
        $section = ClassSection::create(['course_offering_id' => $offering->id, 'code' => 'X-'.random_int(100, 999), 'capacity' => 30, 'room' => $room, 'mode' => 'offline']);
        if ($lecturer) {
            $section->lecturers()->attach($lecturer->id, ['is_primary' => true]);
        }

        return $section;
    }

    private function expectValidationError(string $field, callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());

            return;
        }

        $this->fail("Expected validation error for {$field}.");
    }
}
