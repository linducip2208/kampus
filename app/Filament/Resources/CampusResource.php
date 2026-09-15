<?php

namespace App\Filament\Resources;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\BlogPost;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Curriculum;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\StudyProgram;
use App\Models\University;
use App\Services\Security\UniversityScope;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

abstract class CampusResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $scope = app(UniversityScope::class);

        return match (static::getModel()) {
            University::class => $scope->direct($query, $user, 'id'),
            AcademicYear::class, Applicant::class, Course::class, Faculty::class => $scope->direct($query, $user),
            StudentProfile::class => $scope->students($query, $user),
            StudentEnrollment::class => $scope->enrollments($query, $user),
            StudyProgram::class => $scope->studyPrograms($query, $user),
            StudyPlan::class => $scope->studyPlans($query, $user),
            StudentInvoice::class => $scope->invoices($query, $user),
            Payment::class => $scope->payments($query, $user),
            Curriculum::class => $scope->relation($query, $user, 'studyProgram.department.faculty'),
            Semester::class => $scope->relation($query, $user, 'academicYear'),
            CourseOffering::class => $scope->relation($query, $user, 'course'),
            ClassSection::class => $scope->relation($query, $user, 'offering.course'),
            Assignment::class => $scope->relation($query, $user, 'classSection.offering.course'),
            AuditLog::class => $scope->relation($query, $user, 'user.roles', 'role_user.university_id'),
            BlogPost::class => $scope->relation($query, $user, 'author.employee'),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
