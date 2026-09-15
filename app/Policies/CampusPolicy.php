<?php

namespace App\Policies;

use App\Models\AlumniProfile;
use App\Models\AssetLoan;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Faculty;
use App\Models\Graduation;
use App\Models\IntegrationLog;
use App\Models\JournalLine;
use App\Models\Laboratory;
use App\Models\LetterRequest;
use App\Models\LibraryLoan;
use App\Models\MbkmRegistration;
use App\Models\ResearchMember;
use App\Models\Room;
use App\Models\ScholarshipAward;
use App\Models\StudentActivity;
use App\Models\StudentEnrollment;
use App\Models\StudentOrganizationMember;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\ThesisDefense;
use App\Models\ThesisProposal;
use App\Models\TracerSurvey;
use App\Models\University;
use App\Models\User;

class CampusPolicy
{
    public function allows(User $user, string $ability, mixed $target): ?bool
    {
        $action = match ($ability) {
            'viewAny', 'view' => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete', 'deleteAny' => 'delete',
            'approve', 'submit', 'publish', 'unlock' => $ability,
            default => null,
        };

        if ($action === null || (! is_string($target) && ! is_object($target))) {
            return null;
        }

        $model = is_string($target) ? $target : $target::class;
        $allowed = $user->hasPermission($this->permission($model, $action));

        if (! $allowed || is_string($target)) {
            return $allowed;
        }

        return $this->inScope($user, $target);
    }

    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewAny(User $user, string $model): bool
    {
        return $user->hasPermission($this->permission($model, 'view'));
    }

    public function view(User $user, object $record): bool
    {
        return $user->hasPermission($this->permission($record::class, 'view')) && $this->inScope($user, $record);
    }

    public function create(User $user, string $model): bool
    {
        return $user->hasPermission($this->permission($model, 'create'));
    }

    public function update(User $user, object $record): bool
    {
        return $user->hasPermission($this->permission($record::class, 'update')) && $this->inScope($user, $record);
    }

    public function delete(User $user, object $record): bool
    {
        return $user->hasPermission($this->permission($record::class, 'delete')) && $this->inScope($user, $record);
    }

    public function deleteAny(User $user, string $model): bool
    {
        return $user->hasPermission($this->permission($model, 'delete'));
    }

    private function permission(string $model, string $action): string
    {
        $short = class_basename($model);
        $key = match ($short) {
            'University' => 'universities',
            'StudyProgram' => 'study_programs',
            'AcademicYear' => 'academic_years',
            'CourseOffering' => 'course_offerings',
            'ClassSection' => 'class_sections',
            'StudentEnrollment' => 'student_enrollments',
            'StudentProfile' => 'students',
            'StudentInvoice' => 'student_invoices',
            'StudyPlan' => 'krs',
            default => str($short)->snake()->plural()->toString(),
        };

        return $key.'.'.$action;
    }

    private function inScope(User $user, object $record): bool
    {
        $roles = $user->roles()->get();

        foreach ($roles as $role) {
            $pivot = $role->pivot;
            $studyProgramId = $pivot->study_program_id;
            $facultyId = $pivot->faculty_id;
            $campusId = $pivot->campus_id;
            $universityId = $pivot->university_id;

            if ($record instanceof StudyProgram && $studyProgramId && $record->id !== $studyProgramId) {
                continue;
            }
            if ($record instanceof StudentEnrollment && $studyProgramId && $record->study_program_id !== $studyProgramId) {
                continue;
            }
            if ($record instanceof StudentProfile && $studyProgramId && ! $record->enrollments()->where('study_program_id', $studyProgramId)->exists()) {
                continue;
            }
            if ($record instanceof Faculty && $facultyId && $record->id !== $facultyId) {
                continue;
            }
            if ($record instanceof Campus && $campusId && $record->id !== $campusId) {
                continue;
            }
            if ($record instanceof University && $universityId && $record->id !== $universityId) {
                continue;
            }

            return $this->recordBelongsToUniversity($record, $universityId);
        }

        return false;
    }

    private function recordBelongsToUniversity(object $record, ?string $universityId): bool
    {
        if (! $universityId) {
            return true;
        }
        if ($record instanceof University) {
            return $record->id === $universityId;
        }
        if (in_array('university_id', $record->getFillable(), true) || array_key_exists('university_id', $record->getAttributes())) {
            return $record->university_id === $universityId;
        }
        if ($record instanceof Faculty) {
            return $record->university_id === $universityId;
        }
        if ($record instanceof StudyProgram) {
            return $record->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof StudentEnrollment) {
            return $record->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof StudentProfile) {
            return $record->enrollments()->whereHas('studyProgram.department.faculty', fn ($q) => $q->where('university_id', $universityId))->exists();
        }
        if ($record instanceof Course) {
            return $record->university_id === $universityId;
        }
        if ($record instanceof Curriculum) {
            return $record->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof ScholarshipAward) {
            return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof ThesisProposal) {
            return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof ThesisDefense) {
            return $record->proposal?->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof Graduation) {
            return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof AlumniProfile) {
            return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof TracerSurvey) {
            return $record->alumni?->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof LibraryLoan) {
            return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof ResearchMember) {
            if ($record->student_enrollment_id) {
                return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
            }

            return $record->project?->university_id === $universityId;
        }
        if ($record instanceof StudentOrganizationMember) {
            return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof StudentActivity) {
            return $record->organization?->university_id === $universityId;
        }
        if ($record instanceof MbkmRegistration) {
            return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof LetterRequest) {
            if ($record->student_enrollment_id) {
                return $record->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
            }

            return $record->template?->university_id === $universityId;
        }
        if ($record instanceof AssetLoan) {
            return $record->asset?->university_id === $universityId;
        }
        if ($record instanceof JournalLine) {
            return $record->entry?->university_id === $universityId;
        }
        if ($record instanceof IntegrationLog) {
            return $record->endpoint?->university_id === $universityId;
        }
        if ($record instanceof Building) {
            return $record->campus?->university_id === $universityId;
        }
        if ($record instanceof Room) {
            return $record->building?->campus?->university_id === $universityId;
        }
        if ($record instanceof Laboratory) {
            return $record->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof \App\Models\CourseEquivalence) {
            return $record->oldCourse?->university_id === $universityId;
        }
        if ($record instanceof \App\Models\EmploymentContract
            || $record instanceof \App\Models\EmployeeAttendance
            || $record instanceof \App\Models\EmployeeLeave
            || $record instanceof \App\Models\EducationHistory
            || $record instanceof \App\Models\Certification) {
            return $record->employee?->university_id === $universityId;
        }
        if ($record instanceof \App\Models\LecturerWorkload) {
            return $record->lecturer?->employee?->university_id === $universityId;
        }
        if ($record instanceof \App\Models\WorkloadActivity) {
            return $record->workload?->lecturer?->employee?->university_id === $universityId;
        }
        if ($record instanceof \App\Models\InvoiceDiscount || $record instanceof \App\Models\InvoicePenalty) {
            return $record->invoice?->enrollment?->studyProgram?->department?->faculty?->university_id === $universityId;
        }
        if ($record instanceof \App\Models\ApplicantDocument || $record instanceof \App\Models\ApplicantPayment) {
            return $record->applicant?->university_id === $universityId;
        }

        return true;
    }
}
