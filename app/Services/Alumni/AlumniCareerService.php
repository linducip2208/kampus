<?php

namespace App\Services\Alumni;

use App\Models\AlumniProfile;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\TracerOption;
use App\Models\TracerQuestion;
use App\Models\TracerResponse;
use App\Models\TracerSection;
use App\Models\TracerSurvey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AlumniCareerService
{
    public function createSection(string $universityId, string $title, int $order = 1): TracerSection
    {
        return TracerSection::query()->create([
            'university_id' => $universityId, 'title' => trim($title), 'sort_order' => $order, 'is_active' => true,
        ]);
    }

    public function addQuestion(TracerSection $section, string $prompt, string $type = 'text', bool $required = false, array $options = []): TracerQuestion
    {
        return DB::transaction(function () use ($section, $prompt, $type, $required, $options) {
            if (! in_array($type, ['text', 'choice', 'scale'], true)) {
                throw ValidationException::withMessages(['type' => 'Tipe pertanyaan tidak valid.']);
            }
            $question = TracerQuestion::query()->create([
                'tracer_section_id' => $section->id,
                'prompt' => trim($prompt),
                'type' => $type,
                'sort_order' => $section->questions()->count() + 1,
                'is_required' => $required,
            ]);
            foreach (array_values($options) as $index => $option) {
                TracerOption::query()->create([
                    'tracer_question_id' => $question->id, 'label' => $option, 'value' => $option, 'sort_order' => $index + 1,
                ]);
            }

            return $question->fresh('options');
        });
    }

    public function answerSurvey(TracerSurvey $survey, array $answers, User $actor): TracerSurvey
    {
        return DB::transaction(function () use ($survey, $answers, $actor) {
            foreach ($answers as $questionId => $answer) {
                $question = TracerQuestion::query()->findOrFail($questionId);
                TracerResponse::query()->updateOrCreate(
                    ['tracer_survey_id' => $survey->id, 'tracer_question_id' => $question->id],
                    ['answer' => (string) $answer],
                );
            }
            AuditLog::query()->create([
                'user_id' => $actor->id, 'event' => 'alumni.survey_answered', 'module' => 'alumni',
                'entity_type' => TracerSurvey::class, 'entity_id' => $survey->id,
                'new_values' => ['answers' => count($answers)],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $survey->fresh('responses');
        });
    }

    /**
     * @return array{responses: int, employment_rate: float, by_status: array<string, int>}
     */
    public function analytics(string $universityId, int $year): array
    {
        $surveys = TracerSurvey::query()->where('graduation_year', $year)
            ->whereHas('alumni.enrollment.studyProgram.department.faculty', fn ($query) => $query->where('university_id', $universityId))
            ->get();
        $byStatus = $surveys->groupBy('employment_status')->map->count()->all();
        $employed = ($byStatus['employed'] ?? 0) + ($byStatus['entrepreneur'] ?? 0);

        return [
            'responses' => $surveys->count(),
            'employment_rate' => $surveys->isNotEmpty() ? round($employed / $surveys->count() * 100, 2) : 0.0,
            'by_status' => $byStatus,
        ];
    }

    public function createCompany(string $universityId, array $data): Company
    {
        return Company::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'industry' => $data['industry'] ?? null,
            'website' => $data['website'] ?? null,
            'is_partner' => (bool) ($data['is_partner'] ?? false),
        ]);
    }

    public function postVacancy(Company $company, array $data): JobVacancy
    {
        return JobVacancy::query()->create([
            'company_id' => $company->id,
            'title' => trim($data['title']),
            'description' => $data['description'] ?? null,
            'closes_on' => $data['closes_on'] ?? null,
            'status' => 'open',
        ]);
    }

    public function apply(JobVacancy $vacancy, AlumniProfile $alumni): JobApplication
    {
        if ($vacancy->status !== 'open') {
            throw ValidationException::withMessages(['vacancy' => 'Lowongan sudah ditutup.']);
        }

        return JobApplication::query()->firstOrCreate(
            ['job_vacancy_id' => $vacancy->id, 'alumni_profile_id' => $alumni->id],
            ['status' => 'submitted'],
        );
    }

    public function decideApplication(JobApplication $application, string $status, User $actor): JobApplication
    {
        if (! in_array($status, ['accepted', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => 'Status tidak valid.']);
        }

        return DB::transaction(function () use ($application, $status) {
            $locked = JobApplication::query()->lockForUpdate()->findOrFail($application->id);
            $locked->forceFill(['status' => $status])->save();

            return $locked->fresh();
        });
    }
}
