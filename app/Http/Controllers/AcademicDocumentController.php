<?php

namespace App\Http\Controllers;

use App\Models\AcademicDocument;
use App\Services\Academic\AcademicDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicDocumentController extends Controller
{
    public function issue(Request $request, AcademicDocumentService $documents): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->latest()->first();
        abort_unless($enrollment, 404);
        $data = $request->validate(['locale' => ['required', 'in:id,en']]);

        $document = $documents->issueTemporaryTranscript($enrollment, $data['locale'], $request->user());

        return redirect()->route('portal.academic-documents.show', $document)
            ->with('success', 'Dokumen akademik terverifikasi berhasil diterbitkan.');
    }

    public function show(Request $request, AcademicDocument $document, AcademicDocumentService $documents): View
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        abort_unless($document->enrollment?->studentProfile?->user_id === $request->user()->id, 404);

        return view('portal.academic-document', [
            'document' => $document->load(['university', 'enrollment.studentProfile', 'enrollment.studyProgram']),
            'integrityValid' => $documents->hasValidIntegrity($document),
        ]);
    }

    public function verify(string $token, AcademicDocumentService $documents): View
    {
        $document = AcademicDocument::query()
            ->with(['university', 'enrollment.studentProfile', 'enrollment.studyProgram'])
            ->where('verification_token', $token)
            ->firstOrFail();

        return view('public.academic-document-verification', [
            'document' => $document,
            'integrityValid' => $documents->hasValidIntegrity($document),
        ]);
    }
}
