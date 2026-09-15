# Internal progress tracker

## COMPLETED

- Laravel 13, Filament 5, Livewire 4, Sanctum, ULID core schema, seed demo, sitemap/robots, blog/RSS, dan IndexNow foundation.
- Core identity organization, employee/lecturer, student profile/enrollment, scoped role pivot, permission gate, dan tested API university isolation untuk academic/finance.
- PMB lifecycle service serta applicant-to-student conversion yang transactional dan idempotent.
- KRS validation/finalization, configurable SKS limit, prasyarat, capacity/schedule/financial validation, dan audit.
- Academic record service, GradeCalculator, KHS/IPS/IPK/transcript portal baseline.
- Decimal Money helper, invoice/payment models, dan PaymentAllocationService.
- Student leave/status/reactivation services beserta history dan tests.
- LMS KRS-scoped student workspace, lecturer module/content/assignment authoring, submission grading lock, attempt limits, audit, serta Quiz/CBT mahasiswa dengan timer persisten, random order, auto-grading, expiry, dan workflow tests.
- Tabler npm dependencies, Vite bundle, shared layouts/components, database-driven branding, light/dark/system theme.
- Tabler auth, forgot/reset password, student shell, lecturer shell, dan regression tests.
- Schedule service transaksional dengan validasi bentrok ruang/dosen/kelas, audit, custom Tabler admin workspace, dan regression tests.
- Attendance lifecycle manual/PIN/QR-token, hashed credential, expiry, KRS eligibility, correction audit, serta Tabler student/lecturer UI.
- GradeWorkflowService: component score, exact 100% weight validation, submit/approve/publish/lock, controlled revision, scale recalculation, audit, dan tests.
- Tabler lecturer grade entry + scheme configuration, scoped BAAK/rektor approval/publish/lock, serta request/approve/reject revisi nilai terkunci dengan audit dan access tests.
- AcademicRecord hanya menghitung published/locked grades, configurable repeat-course policy, printable bilingual transcript, serta persistent immutable transcript snapshot dengan checksum, QR, nomor race-safe, dan public verification.

## IN PROGRESS

- Phase 1: Tabler foundation, custom admin dashboard, RBAC, API/resource university isolation sudah teruji; lanjut scoping domain baru.
- Halaman student/lecturer aktif telah dimigrasikan ke Tabler; attendance workflow kini tersedia, lanjut grade/LMS/KRS approval.
- Custom `/admin` Tabler aktif; Filament dipindah ke `/admin/legacy` sementara untuk mencegah kehilangan feature parity.

## NEXT

1. Terapkan UniversityScope saat menambah PMB/research/assets/documents dan perluas isolation tests per domain.
2. Lanjutkan migrasi custom Tabler admin per domain tanpa memutus resource lama.
3. Lengkapi private file manager, announcement/discussion, lecturer quiz builder/manual essay grading, lalu leave dan workflow portal.
4. Lengkapi final transcript lifecycle melalui graduation, API nilai, dan laporan presensi agregat.
5. Lengkapi PMB portal end-to-end, student finance, dan adapter integrasi berbasis format yang dinamis.
6. Lanjutkan modul MISSING sesuai urutan pada `docs/MODULE_COMPLETION.md`.

## BLOCKERS

- Live verification PDDikti, payment gateway, WhatsApp, SMTP, SSO, Redis production, object storage, dan deployment memerlukan credential/infrastruktur institusi.
- Adapter generik, fake driver, queue, logging, retry, settings, dan tests tetap harus dikerjakan tanpa credential live.

## TEST STATUS

- Auth, password reset, student/lecturer portal, schedule collision, dan attendance lifecycle suites: passing.
- `composer validate`, `migrate:fresh --seed`, Blade cache, 75 tests/298 assertions, scoped Pint, dan Vite production build: PASS (2026-09-15).
- Source-of-truth completion matrix: `docs/MODULE_COMPLETION.md`.