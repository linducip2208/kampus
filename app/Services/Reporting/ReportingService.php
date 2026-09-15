<?php

namespace App\Services\Reporting;

use App\Models\LibraryLoan;
use App\Models\Payment;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Services\Security\UniversityScope;
use Illuminate\Support\Collection;

class ReportingService
{
    public function __construct(private readonly UniversityScope $scope) {}

    public function executiveSummary(User $user): array
    {
        $enrollments = $this->scope->enrollments(StudentEnrollment::query(), $user);
        $invoices = $this->scope->invoices(StudentInvoice::query(), $user);
        $payments = $this->scope->payments(Payment::query(), $user);

        $byStatus = (clone $enrollments)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'enrollments_total' => (clone $enrollments)->count(),
            'enrollments_by_status' => $byStatus,
            'invoices_outstanding' => (clone $invoices)->whereIn('status', ['issued', 'partial'])->count(),
            'invoices_outstanding_amount' => (clone $invoices)->whereIn('status', ['issued', 'partial'])->get()
                ->sum(fn (StudentInvoice $invoice): float => (float) $invoice->outstanding_amount),
            'payments_total' => (clone $payments)->count(),
            'library_active_loans' => LibraryLoan::query()->where('status', 'borrowed')
                ->whereHas('enrollment.studyProgram.department.faculty', function ($query) use ($user) {
                    if (! $user->hasRole('super_admin')) {
                        $ids = $user->roles()->get()->pluck('pivot.university_id')->filter()->unique()->values();
                        $query->whereIn('university_id', $ids);
                    }
                })->count(),
        ];
    }

    public function attendanceAggregate(string $classSectionId): array
    {
        $rows = StudentAttendance::query()
            ->whereHas('session.lectureMeeting', fn ($query) => $query->where('class_section_id', $classSectionId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = $rows->sum();

        return [
            'class_section_id' => $classSectionId,
            'total_records' => $total,
            'by_status' => $rows,
            'present_rate' => $total > 0 ? round((($rows['present'] ?? 0) / $total) * 100, 2) : 0.0,
        ];
    }

    public function exportEnrollmentsCsv(User $user): string
    {
        $rows = $this->scope->enrollments(
            StudentEnrollment::query()->with(['studentProfile', 'studyProgram'])->latest(),
            $user
        )->limit(1000)->get();

        $lines = ['student_number,full_name,study_program,status,cohort'];
        foreach ($rows as $enrollment) {
            $lines[] = implode(',', [
                '"'.str_replace('"', '""', (string) $enrollment->studentProfile?->student_number).'"',
                '"'.str_replace('"', '""', (string) $enrollment->studentProfile?->full_name).'"',
                '"'.str_replace('"', '""', (string) $enrollment->studyProgram?->name).'"',
                $enrollment->status,
                $enrollment->cohort,
            ]);
        }

        return implode("\n", $lines);
    }

    public function exportToCollection(array $summary): Collection
    {
        return collect($summary);
    }
}
