<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\StudyProgram;
use App\Models\University;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            $user = auth()->user();

            return match (true) {
                $user->hasRole('mahasiswa') => redirect()->route('portal.dashboard'),
                $user->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing']) => redirect()->route('lecturer.dashboard'),
                default => redirect('/admin'),
            };
        }

        $cohortCounts = StudentEnrollment::query()
            ->selectRaw('cohort, COUNT(*) as aggregate')
            ->groupBy('cohort')
            ->orderBy('cohort')
            ->limit(10)
            ->pluck('aggregate', 'cohort');
        $maximumCohort = max(1, (int) $cohortCounts->max());
        $outstanding = (string) StudentInvoice::query()
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as aggregate')
            ->value('aggregate');

        return view('marketing', [
            'university' => University::query()->first(),
            'stats' => [
                'students' => StudentProfile::query()->count(),
                'active_students' => StudentEnrollment::query()->where('status', 'active')->count(),
                'programs' => StudyProgram::query()->count(),
                'courses' => Course::query()->count(),
                'audit_logs' => AuditLog::query()->count(),
                'outstanding' => number_format(intdiv(Money::toMinorUnits($outstanding), 100), 0, ',', '.'),
                'pending_krs' => StudyPlan::query()->whereIn('status', ['submitted', 'advisor_review'])->count(),
                'approved_krs_today' => StudyPlan::query()->whereDate('approved_at', today())->count(),
                'student_trend' => $cohortCounts->map(fn ($count, $cohort) => [
                    'cohort' => (string) $cohort,
                    'count' => (int) $count,
                    'height' => max(8, intdiv((int) $count * 100, $maximumCohort)),
                ])->values(),
            ],
        ]);
    }
}
