<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Course;
use App\Models\Payment;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudyPlan;
use App\Models\StudyProgram;
use App\Services\Security\UniversityScope;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminWorkspaceController extends Controller
{
    public function __invoke(Request $request, UniversityScope $scope): View
    {
        $user = $request->user();
        abort_if($user->hasRole(['mahasiswa', 'dosen', 'dosen_wali', 'dosen_pembimbing', 'alumni']), 403);

        $enrollments = $scope->enrollments(StudentEnrollment::query(), $user);
        $programs = $scope->studyPrograms(StudyProgram::query(), $user);
        $courses = $scope->direct(Course::query(), $user);
        $invoices = $scope->invoices(StudentInvoice::query(), $user);
        $payments = $scope->payments(Payment::query(), $user);
        $studyPlans = $scope->studyPlans(StudyPlan::query(), $user);
        $applicants = $scope->direct(Applicant::query(), $user);

        $canAcademic = $user->hasAnyPermission(['students.view', 'study_programs.view', 'courses.view', 'krs.view']);
        $canFinance = $user->hasAnyPermission(['student_invoices.view', 'payments.view']);
        $canAdmission = $user->hasRole(['super_admin', 'rektor', 'pmb']);

        $outstanding = $canFinance ? (string) (clone $invoices)
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as aggregate')
            ->value('aggregate') : '0.00';
        $collected = $canFinance ? (string) (clone $payments)
            ->where('status', 'paid')
            ->selectRaw('COALESCE(SUM(amount), 0) as aggregate')
            ->value('aggregate') : '0.00';

        return view('admin.dashboard', [
            'metrics' => [
                'activeStudents' => $canAcademic ? (clone $enrollments)->where('status', 'active')->count() : null,
                'programs' => $canAcademic ? (clone $programs)->count() : null,
                'courses' => $canAcademic ? (clone $courses)->count() : null,
                'pendingKrs' => $user->hasPermission('krs.view') ? (clone $studyPlans)->whereIn('status', ['submitted', 'advisor_review'])->count() : null,
                'outstanding' => $canFinance ? $this->rupiah($outstanding) : null,
                'collected' => $canFinance ? $this->rupiah($collected) : null,
                'applicants' => $canAdmission ? (clone $applicants)->count() : null,
            ],
            'recentPlans' => $user->hasPermission('krs.view') ? (clone $studyPlans)
                ->with(['enrollment.studentProfile', 'semester'])
                ->latest('submitted_at')
                ->limit(6)
                ->get() : collect(),
            'roleNames' => $user->roles()->pluck('label')->filter()->values(),
        ]);
    }

    private function rupiah(string $amount): string
    {
        return 'Rp '.number_format(intdiv(Money::toMinorUnits($amount), 100), 0, ',', '.');
    }
}
