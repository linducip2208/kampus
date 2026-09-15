<?php

namespace App\Services\Academic;

use App\Models\AcademicDocument;
use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcademicDocumentService
{
    public function __construct(
        private readonly AcademicRecordService $records,
        private readonly NumberSequenceService $sequences,
    ) {}

    public function issueTemporaryTranscript(StudentEnrollment $enrollment, string $locale, User $actor): AcademicDocument
    {
        return DB::transaction(function () use ($enrollment, $locale, $actor) {
            $locale = in_array($locale, ['id', 'en'], true) ? $locale : 'id';
            $enrollment = StudentEnrollment::query()
                ->with(['studentProfile', 'studyProgram.department.faculty'])
                ->lockForUpdate()
                ->findOrFail($enrollment->id);
            $universityId = $enrollment->studyProgram->department->faculty->university_id;
            $record = $this->records->forEnrollment($enrollment);
            $snapshot = $this->snapshot($enrollment, $record);
            $checksum = hash('sha256', $this->encoded($snapshot));

            $existing = AcademicDocument::query()
                ->where('student_enrollment_id', $enrollment->id)
                ->where('type', 'temporary_transcript')
                ->where('locale', $locale)
                ->where('payload_checksum', $checksum)
                ->where('status', 'issued')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $this->sequences->ensure($universityId, 'temporary_transcript', 'TRX/{year}/{number}');
            $document = AcademicDocument::query()->create([
                'university_id' => $universityId,
                'student_enrollment_id' => $enrollment->id,
                'document_number' => $this->sequences->next($universityId, 'temporary_transcript', ['year' => now()->year]),
                'type' => 'temporary_transcript',
                'locale' => $locale,
                'verification_token' => Str::random(64),
                'payload_checksum' => $checksum,
                'snapshot' => $snapshot,
                'status' => 'issued',
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'event' => 'academic_document.issued',
                'module' => 'academic_records',
                'entity_type' => AcademicDocument::class,
                'entity_id' => $document->id,
                'new_values' => [
                    'document_number' => $document->document_number,
                    'type' => $document->type,
                    'payload_checksum' => $checksum,
                ],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $document;
        });
    }

    public function hasValidIntegrity(AcademicDocument $document): bool
    {
        return $document->isValid()
            && hash_equals($document->payload_checksum, hash('sha256', $this->encoded($document->snapshot)));
    }

    private function snapshot(StudentEnrollment $enrollment, array $record): array
    {
        return [
            'student' => [
                'name' => $enrollment->studentProfile->full_name,
                'number' => $enrollment->studentProfile->student_number,
                'study_program' => $enrollment->studyProgram->name,
                'status' => $enrollment->status,
            ],
            'summary' => [
                'attempted_credits' => $record['attempted_credits'],
                'earned_credits' => $record['earned_credits'],
                'failed_credits' => $record['failed_credits'],
                'gpa' => round((float) $record['gpa'], 2),
                'repeat_policy' => $record['repeat_policy'],
            ],
            'courses' => $record['transcript_items']->map(fn ($item) => [
                'code' => $item->classSection?->offering?->course?->code,
                'name' => $item->classSection?->offering?->course?->name,
                'credits' => $item->credits,
                'grade' => $item->grade?->gradeScale?->grade,
                'grade_point' => (string) ($item->grade?->gradeScale?->grade_point ?? '0.00'),
                'semester' => $item->academicPlan?->semester?->name,
                'academic_year' => $item->academicPlan?->semester?->academicYear?->name,
            ])->values()->all(),
        ];
    }

    private function encoded(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
