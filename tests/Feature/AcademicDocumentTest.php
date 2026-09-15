<?php

namespace Tests\Feature;

use App\Models\AcademicDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_issue_idempotent_verified_transcript_snapshot(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $first = $this->actingAs($student)->post(route('portal.academic-documents.issue'), ['locale' => 'id'])
            ->assertRedirect()->assertSessionHas('success');
        $document = AcademicDocument::query()->firstOrFail();
        $first->assertRedirect(route('portal.academic-documents.show', $document));

        $this->post(route('portal.academic-documents.issue'), ['locale' => 'id'])->assertRedirect(route('portal.academic-documents.show', $document));
        $this->assertDatabaseCount('academic_documents', 1);
        $this->assertDatabaseHas('audit_logs', ['event' => 'academic_document.issued', 'entity_id' => $document->id]);

        $this->get(route('portal.academic-documents.show', $document))
            ->assertOk()->assertSee($document->document_number)->assertSee('Pindai untuk verifikasi publik');

        $this->get(route('academic-documents.verify', $document->verification_token))
            ->assertOk()->assertSee('Dokumen valid')->assertSee($document->document_number);
    }

    public function test_verification_detects_tampered_snapshot_and_unknown_token(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $this->actingAs($student)->post(route('portal.academic-documents.issue'), ['locale' => 'en']);
        $document = AcademicDocument::query()->firstOrFail();
        $snapshot = $document->snapshot;
        data_set($snapshot, 'summary.gpa', 0.01);
        $document->forceFill(['snapshot' => $snapshot])->save();

        $this->get(route('academic-documents.verify', $document->verification_token))
            ->assertOk()->assertSee('Dokumen tidak valid');
        $this->get(route('academic-documents.verify', str_repeat('x', 64)))->assertNotFound();
    }

    public function test_non_student_cannot_issue_or_open_student_document(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $this->actingAs($student)->post(route('portal.academic-documents.issue'), ['locale' => 'id']);
        $document = AcademicDocument::query()->firstOrFail();

        $this->actingAs($admin)->post(route('portal.academic-documents.issue'), ['locale' => 'id'])->assertForbidden();
        $this->get(route('portal.academic-documents.show', $document))->assertForbidden();
    }
}
