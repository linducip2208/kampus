<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UniversityScope
{
    public function direct(Builder $query, User $user, string $column = 'university_id'): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $ids = $this->assignments($user)->pluck('university_id')->filter()->unique()->values();

        return $ids->isEmpty() ? $query->whereRaw('1 = 0') : $query->whereIn($column, $ids);
    }

    public function students(Builder $query, User $user): Builder
    {
        return $this->throughEnrollment($query, $user, 'enrollments');
    }

    public function studyPlans(Builder $query, User $user): Builder
    {
        return $this->throughEnrollment($query, $user, 'enrollment');
    }

    public function invoices(Builder $query, User $user): Builder
    {
        return $this->throughEnrollment($query, $user, 'enrollment');
    }

    public function payments(Builder $query, User $user): Builder
    {
        return $this->throughEnrollment($query, $user, 'enrollment');
    }

    public function lecturers(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $ids = $this->assignments($user)->pluck('university_id')->filter()->unique()->values();

        return $ids->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereHas('employee', fn (Builder $employee) => $employee->whereIn('university_id', $ids));
    }

    public function relation(Builder $query, User $user, string $relation, string $column = 'university_id'): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $ids = $this->assignments($user)->pluck('university_id')->filter()->unique()->values();

        return $ids->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereHas($relation, fn (Builder $related) => $related->whereIn($column, $ids));
    }

    public function enrollments(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $assignments = $this->assignments($user);
        if ($assignments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scoped) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $scoped->orWhere(function (Builder $enrollment) use ($assignment): void {
                    $enrollment->whereHas(
                        'studyProgram.department.faculty',
                        fn (Builder $faculty) => $faculty->where('university_id', $assignment->university_id)
                    );
                    if ($assignment->study_program_id) {
                        $enrollment->where('study_program_id', $assignment->study_program_id);
                    } elseif ($assignment->faculty_id) {
                        $enrollment->whereHas(
                            'studyProgram.department',
                            fn (Builder $department) => $department->where('faculty_id', $assignment->faculty_id)
                        );
                    }
                });
            }
        });
    }

    public function studyPrograms(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $assignments = $this->assignments($user);
        if ($assignments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scoped) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $scoped->orWhere(function (Builder $program) use ($assignment): void {
                    $program->whereHas(
                        'department.faculty',
                        fn (Builder $faculty) => $faculty->where('university_id', $assignment->university_id)
                    );
                    if ($assignment->study_program_id) {
                        $program->whereKey($assignment->study_program_id);
                    } elseif ($assignment->faculty_id) {
                        $program->whereHas('department', fn (Builder $department) => $department->where('faculty_id', $assignment->faculty_id));
                    }
                });
            }
        });
    }

    private function throughEnrollment(Builder $query, User $user, string $relation): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $assignments = $this->assignments($user);
        if ($assignments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scoped) use ($assignments, $relation): void {
            foreach ($assignments as $assignment) {
                $scoped->orWhereHas($relation, function (Builder $enrollment) use ($assignment): void {
                    $enrollment->whereHas(
                        'studyProgram.department.faculty',
                        fn (Builder $faculty) => $faculty->where('university_id', $assignment->university_id)
                    );

                    if ($assignment->study_program_id) {
                        $enrollment->where('study_program_id', $assignment->study_program_id);
                    } elseif ($assignment->faculty_id) {
                        $enrollment->whereHas(
                            'studyProgram.department',
                            fn (Builder $department) => $department->where('faculty_id', $assignment->faculty_id)
                        );
                    }
                });
            }
        });
    }

    private function assignments(User $user): Collection
    {
        return $user->roles()
            ->get()
            ->map(fn ($role) => (object) [
                'university_id' => $role->pivot->university_id,
                'campus_id' => $role->pivot->campus_id,
                'faculty_id' => $role->pivot->faculty_id,
                'study_program_id' => $role->pivot->study_program_id,
            ])
            ->filter(fn (object $scope) => $scope->university_id !== null)
            ->values();
    }
}
