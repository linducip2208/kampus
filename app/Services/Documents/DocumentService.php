<?php

namespace App\Services\Documents;

use App\Models\AuditLog;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Approval\ApprovalEngine;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    public function __construct(
        private readonly ApprovalEngine $approval,
        private readonly NumberSequenceService $sequences,
    ) {}

    public function createTemplate(string $universityId, array $data): LetterTemplate
    {
        return LetterTemplate::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'body' => $data['body'],
            'variables' => $data['variables'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function request(LetterTemplate $template, User $requester, ?StudentEnrollment $enrollment = null, ?string $purpose = null, array $payload = []): LetterRequest
    {
        return DB::transaction(function () use ($template, $requester, $enrollment, $purpose, $payload) {
            if (! $template->is_active) {
                throw ValidationException::withMessages(['template' => 'Template surat tidak aktif.']);
            }
            $letter = LetterRequest::query()->create([
                'letter_template_id' => $template->id,
                'student_enrollment_id' => $enrollment?->id,
                'requested_by' => $requester->id,
                'purpose' => $purpose,
                'payload' => $payload ?: null,
                'status' => 'proposed',
            ]);
            $universityId = $enrollment
                ? $enrollment->loadMissing('studyProgram.department.faculty')->studyProgram->department->faculty->university_id
                : $template->university_id;
            $this->approval->request($letter, $universityId, 'letter', $requester);

            return $letter->fresh();
        });
    }

    public function issue(LetterRequest $letter, User $actor): LetterRequest
    {
        return DB::transaction(function () use ($letter, $actor) {
            $locked = LetterRequest::query()->with(['template', 'enrollment.studyProgram.department.faculty'])->lockForUpdate()->findOrFail($letter->id);
            if ($locked->status !== 'proposed') {
                throw ValidationException::withMessages(['letter' => 'Surat sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $result = $this->approval->act($approval, $actor, 'approved');
            if ($result->status !== 'approved') {
                return $locked->fresh();
            }
            $universityId = $locked->enrollment?->studyProgram->department->faculty->university_id ?? $locked->template->university_id;
            $this->sequences->ensure($universityId, 'letter', '{number}/SM/{year}');
            $locked->forceFill([
                'status' => 'issued',
                'document_number' => $this->sequences->next($universityId, 'letter', ['year' => now()->year]),
                'decided_by' => $actor->id,
                'issued_at' => now(),
            ])->save();

            AuditLog::query()->create([
                'user_id' => $actor->id, 'event' => 'letter.issued', 'module' => 'documents',
                'entity_type' => LetterRequest::class, 'entity_id' => $locked->id,
                'new_values' => ['document_number' => $locked->document_number],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $locked->fresh();
        });
    }
}
