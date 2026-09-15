<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\StudentEnrollment;
use App\Models\University;
use App\Models\User;
use App\Services\Assets\AssetService;
use App\Services\Documents\DocumentService;
use App\Services\Finance\AccountingService;
use App\Services\Integrations\OpsFoundationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkflow(string $module): void
    {
        $university = University::query()->firstOrFail();
        $workflow = ApprovalWorkflow::query()->firstOrCreate(
            ['university_id' => $university->id, 'module' => $module],
            ['name' => "Workflow {$module}", 'is_active' => true],
        );
        ApprovalStep::query()->firstOrCreate(
            ['approval_workflow_id' => $workflow->id, 'step_order' => 1],
            ['label' => 'Persetujuan admin', 'role_name' => 'super_admin'],
        );
    }

    public function test_letter_request_issue_with_number(): void
    {
        $this->seed();
        $this->makeWorkflow('letter');
        $university = University::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $template = app(DocumentService::class)->createTemplate($university->id, [
            'name' => 'Surat Keterangan Aktif', 'code' => 'SKA', 'body' => 'Menerangkan {nama} aktif.',
            'variables' => ['nama'],
        ]);
        $letter = app(DocumentService::class)->request($template, $student, $enrollment, 'Beasiswa', ['nama' => 'Alya']);
        $issued = app(DocumentService::class)->issue($letter, $admin);

        $this->assertSame('issued', $issued->status);
        $this->assertNotNull($issued->document_number);
    }

    public function test_asset_loan_return(): void
    {
        $this->seed();
        $university = University::query()->firstOrFail();
        $borrower = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $asset = app(AssetService::class)->register($university->id, ['name' => 'Proyektor Epson', 'code' => 'AST-0001', 'category' => 'equipment']);
        $loan = app(AssetService::class)->loan($asset, $borrower, $admin, now()->addWeek()->toDateTimeString(), 'Kuliah tamu');
        $this->assertSame('borrowed', $loan->status);
        $returned = app(AssetService::class)->returnAsset($loan, $admin);
        $this->assertSame('returned', $returned->status);
        $this->assertSame('available', $asset->fresh()->status);
    }

    public function test_accounting_double_entry_immutable(): void
    {
        $this->seed();
        $university = University::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $cash = app(AccountingService::class)->createAccount($university->id, ['code' => '1101', 'name' => 'Kas', 'type' => 'asset']);
        $revenue = app(AccountingService::class)->createAccount($university->id, ['code' => '4101', 'name' => 'Pendapatan UKT', 'type' => 'revenue']);

        $entry = app(AccountingService::class)->postJournal($university->id, 'Penerimaan UKT', [
            ['account_id' => $cash->id, 'debit' => '7500000.00', 'credit' => '0.00'],
            ['account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '7500000.00'],
        ], $admin);

        $this->assertNotNull($entry->entry_number);
        $this->assertDatabaseHas('audit_logs', ['event' => 'accounting.journal_posted']);

        try {
            $entry->update(['description' => 'Diubah']);
            $this->fail('Jurnal seharusnya immutable.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('tidak dapat diubah', $e->getMessage());
        }
    }

    public function test_integration_fake_driver_notifications_backup_health(): void
    {
        $this->seed();
        $university = University::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $endpoint = app(OpsFoundationService::class)->registerEndpoint($university->id, [
            'name' => 'PDDikti Fake', 'kind' => 'pddikti', 'base_url' => 'fake://pddikti.local',
        ]);
        $log = app(OpsFoundationService::class)->call($endpoint, 'sync_feeder', ['nim' => '2026-IF-00001'], $admin);
        $this->assertTrue($log->success);

        $template = app(OpsFoundationService::class)->upsertTemplate($university->id, 'krs.approved', 'database', 'KRS Disetujui', 'KRS Anda disetujui.');
        $this->assertTrue($template->is_active);
        $pref = app(OpsFoundationService::class)->setPreference($admin, 'krs.approved', 'database', true);
        $this->assertTrue($pref->is_enabled);

        $backup = app(OpsFoundationService::class)->recordBackup('backup-2026-09-15.zip', 'local', 1024);
        $this->assertSame('completed', $backup->status);

        $checks = app(OpsFoundationService::class)->runHealthChecks();
        $this->assertNotEmpty($checks);
        $this->assertDatabaseHas('system_health_checks', ['check_name' => 'database', 'status' => 'pass']);
    }
}
