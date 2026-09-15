<?php

namespace App\Filament\Resources;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\BlogPost;
use App\Models\Building;
use App\Models\ChartOfAccount;
use App\Models\ClassSection;
use App\Models\CommunityService;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Curriculum;
use App\Models\Faculty;
use App\Models\Graduation;
use App\Models\IntegrationEndpoint;
use App\Models\JournalEntry;
use App\Models\Laboratory;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\MbkmProgram;
use App\Models\MbkmRegistration;
use App\Models\Payment;
use App\Models\ResearchProject;
use App\Models\Room;
use App\Models\Scholarship;
use App\Models\ScholarshipAward;
use App\Models\Semester;
use App\Models\StudentActivity;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudentOrganization;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\StudyProgram;
use App\Models\ThesisDefense;
use App\Models\ThesisProposal;
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
            Scholarship::class => $scope->direct($query, $user),
            ScholarshipAward::class => $scope->relation($query, $user, 'enrollment.studyProgram.department.faculty'),
            ThesisProposal::class => $scope->relation($query, $user, 'enrollment.studyProgram.department.faculty'),
            ThesisDefense::class => $scope->relation($query, $user, 'proposal.enrollment.studyProgram.department.faculty'),
            Graduation::class => $scope->relation($query, $user, 'enrollment.studyProgram.department.faculty'),
            LibraryBook::class => $scope->direct($query, $user),
            LibraryLoan::class => $scope->relation($query, $user, 'enrollment.studyProgram.department.faculty'),
            ResearchProject::class => $scope->direct($query, $user),
            CommunityService::class => $scope->direct($query, $user),
            StudentOrganization::class => $scope->direct($query, $user),
            StudentActivity::class => $scope->relation($query, $user, 'organization'),
            MbkmProgram::class => $scope->direct($query, $user),
            MbkmRegistration::class => $scope->relation($query, $user, 'enrollment.studyProgram.department.faculty'),
            LetterTemplate::class => $scope->direct($query, $user),
            LetterRequest::class => $scope->relation($query, $user, 'template'),
            Asset::class => $scope->direct($query, $user),
            AssetLoan::class => $scope->relation($query, $user, 'asset'),
            ChartOfAccount::class => $scope->direct($query, $user),
            JournalEntry::class => $scope->direct($query, $user),
            IntegrationEndpoint::class => $scope->direct($query, $user),
            Building::class => $scope->relation($query, $user, 'campus'),
            Room::class => $scope->relation($query, $user, 'building.campus'),
            Laboratory::class => $scope->relation($query, $user, 'department.faculty'),
            \App\Models\AcademicCalendar::class => $scope->direct($query, $user),
            \App\Models\Holiday::class => $scope->direct($query, $user),
            \App\Models\CourseCategory::class => $scope->direct($query, $user),
            \App\Models\CourseEquivalence::class => $scope->relation($query, $user, 'oldCourse'),
            \App\Models\AcademicRule::class => $scope->direct($query, $user),
            \App\Models\OrganizationalUnit::class => $scope->direct($query, $user),
            \App\Models\Position::class => $scope->direct($query, $user),
            \App\Models\EmploymentContract::class => $scope->relation($query, $user, 'employee'),
            \App\Models\EmployeeAttendance::class => $scope->relation($query, $user, 'employee'),
            \App\Models\EmployeeLeave::class => $scope->relation($query, $user, 'employee'),
            \App\Models\LecturerWorkload::class => $scope->relation($query, $user, 'lecturer.employee'),
            AuditLog::class => $scope->relation($query, $user, 'user.roles', 'role_user.university_id'),
            BlogPost::class => $scope->relation($query, $user, 'author.employee'),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
