<?php

namespace App\Http\Controllers;

use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\Payment;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'cards' => [
                ['label' => 'Mahasiswa aktif', 'value' => StudentEnrollment::where('status', 'active')->count(), 'tone' => 'blue'],
                ['label' => 'Program studi', 'value' => StudyProgram::count(), 'tone' => 'cyan'],
                ['label' => 'Pembayaran tercatat', 'value' => 'Rp '.number_format((float) Payment::where('status', 'paid')->sum('amount'), 0, ',', '.'), 'tone' => 'amber'],
                ['label' => 'Piutang mahasiswa', 'value' => 'Rp '.number_format((float) StudentInvoice::selectRaw('SUM(total_amount - paid_amount) as outstanding')->value('outstanding'), 0, ',', '.'), 'tone' => 'rose'],
            ],
            'byProgram' => StudyProgram::withCount(['enrollments as active_count' => fn ($q) => $q->where('status', 'active')])->get(),
            'students' => StudentProfile::with('enrollments.studyProgram')->latest()->limit(8)->get(),
        ]);
    }
}
