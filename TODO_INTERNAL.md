# Internal progress tracker

## COMPLETED

- Laravel 13, Filament 5, Livewire 4, Sanctum, ULID core schema, seed demo, sitemap/robots, blog/RSS, dan IndexNow foundation.
- Core identity organization, employee/lecturer, student profile/enrollment, scoped role pivot, permission gate, dan tested API university isolation untuk academic/finance.
- PMB lifecycle service serta applicant-to-student conversion yang transactional dan idempotent.
- KRS validation/finalization, configurable SKS limit, prasyarat, capacity/schedule/financial validation, dan audit.
- Academic record service, GradeCalculator, KHS/IPS/IPK/transcript portal baseline.
- Decimal Money helper, invoice/payment models, dan PaymentAllocationService.
- Student leave/status/reactivation services beserta history dan tests.
- LMS data baseline, assignment submission service, QuizAttemptService, dan workflow tests.
- Tabler npm dependencies, Vite bundle, shared layouts/components, database-driven branding, light/dark/system theme.
- Tabler auth, forgot/reset password, student shell, lecturer shell, dan regression tests.

## IN PROGRESS

- Phase 1: finalisasi Tabler foundation, audit RBAC, dan university query isolation.
- Migrasi konten public/student/lecturer dari utility UI legacy ke komponen Tabler.
- Custom `/admin` Tabler; Filament masih aktif sementara untuk mencegah kehilangan feature parity.

## NEXT

1. Terapkan UniversityScope pada seluruh resource admin, dashboard, PMB, research, assets, dan documents serta perluas isolation tests.
2. Bangun custom Tabler admin dashboard + permission-aware navigation tanpa memutus resource lama.
3. Migrasikan seluruh halaman student dan lecturer ke Tabler native components.
4. Lengkapi schedule collision, attendance lifecycle, grading workflow, grade revision, KHS/transcript PDF/QR.
5. Lengkapi PMB portal end-to-end, student finance, dan adapter integrasi berbasis format yang dinamis.
6. Lanjutkan modul MISSING sesuai urutan pada `docs/MODULE_COMPLETION.md`.

## BLOCKERS

- Live verification PDDikti, payment gateway, WhatsApp, SMTP, SSO, Redis production, object storage, dan deployment memerlukan credential/infrastruktur institusi.
- Adapter generik, fake driver, queue, logging, retry, settings, dan tests tetap harus dikerjakan tanpa credential live.

## TEST STATUS

- Auth, password reset, student portal, dan lecturer portal targeted suite: passing.
- `composer validate`, `migrate:fresh --seed`, Blade cache, 29 tests/113 assertions, scoped Pint, dan Vite production build: PASS (2026-09-15).
- Source-of-truth completion matrix: `docs/MODULE_COMPLETION.md`.