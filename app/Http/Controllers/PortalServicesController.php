<?php

namespace App\Http\Controllers;

use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\MbkmProgram;
use App\Models\MbkmRegistration;
use App\Models\ScholarshipAward;
use App\Models\StudentTransfer;
use App\Models\ThesisProposal;
use App\Services\Academic\CampusActivityService;
use App\Services\Academic\ThesisService;
use App\Services\Documents\DocumentService;
use App\Services\Library\LibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalServicesController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->with(['studyProgram.department.faculty', 'studentProfile'])->latest()->firstOrFail();
        $enrollment->load(['invoices.installments']);

        $awards = ScholarshipAward::query()->where('student_enrollment_id', $enrollment->id)->with('scholarship')->latest()->get();
        $theses = ThesisProposal::query()->where('student_enrollment_id', $enrollment->id)->with('defenses')->latest()->get();
        $loans = LibraryLoan::query()->where('student_enrollment_id', $enrollment->id)->with('book')->latest()->limit(10)->get();
        $mbkm = MbkmRegistration::query()->where('student_enrollment_id', $enrollment->id)->with('program')->latest()->get();
        $letters = LetterRequest::query()->where('student_enrollment_id', $enrollment->id)->with('template')->latest()->limit(10)->get();
        $transfers = StudentTransfer::query()->where('student_enrollment_id', $enrollment->id)->latest()->limit(10)->get();
        $templates = LetterTemplate::query()->where('university_id', $enrollment->studyProgram->department->faculty->university_id)->where('is_active', true)->get();
        $programs = MbkmProgram::query()->where('university_id', $enrollment->studyProgram->department->faculty->university_id)->where('status', 'open')->get();
        $books = LibraryBook::query()->where('university_id', $enrollment->studyProgram->department->faculty->university_id)->where('copies_available', '>', 0)->latest()->limit(10)->get();

        return view('portal.services', compact('enrollment', 'awards', 'theses', 'loans', 'mbkm', 'letters', 'transfers', 'templates', 'programs', 'books'));
    }

    public function storeThesis(Request $request, ThesisService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->latest()->firstOrFail();
        $data = $request->validate(['title' => ['required', 'string', 'min:10', 'max:500'], 'abstract' => ['nullable', 'string', 'max:5000']]);
        $service->submit($enrollment, $data['title'], $data['abstract'] ?? null, $request->user());

        return back()->with('success', 'Proposal tugas akhir diajukan.');
    }

    public function storeLetter(Request $request, DocumentService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->latest()->firstOrFail();
        $data = $request->validate(['letter_template_id' => ['required', 'exists:letter_templates,id'], 'purpose' => ['nullable', 'string', 'max:1000']]);
        $template = LetterTemplate::query()->findOrFail($data['letter_template_id']);
        $service->request($template, $request->user(), $enrollment, $data['purpose'] ?? null);

        return back()->with('success', 'Surat diajukan, menunggu persetujuan.');
    }

    public function registerMbkm(Request $request, CampusActivityService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->latest()->firstOrFail();
        $data = $request->validate(['mbkm_program_id' => ['required', 'exists:mbkm_programs,id']]);
        $service->registerMbkm(MbkmProgram::query()->findOrFail($data['mbkm_program_id']), $enrollment, $request->user());

        return back()->with('success', 'Pendaftaran MBKM dikirim.');
    }

    public function borrowBook(Request $request, LibraryService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->latest()->firstOrFail();
        $data = $request->validate(['library_book_id' => ['required', 'exists:library_books,id']]);
        $service->borrow(LibraryBook::query()->findOrFail($data['library_book_id']), $enrollment, $request->user());

        return back()->with('success', 'Buku berhasil dipinjam.');
    }
}
