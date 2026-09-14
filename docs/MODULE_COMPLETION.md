# Campus ERP Module Completion

Dokumen ini merekam keadaan source code aktual, bukan target PRD. Status hanya `COMPLETE`, `PARTIAL`, `MISSING`, atau `BLOCKED`.

| Module | Status | Completeness | Database | Model | Service | Policy | Admin UI | Student UI | Lecturer UI | API | Test | Notes |
|---|---|---:|---|---|---|---|---|---|---|---|---|---|
| Foundation, auth, RBAC | PARTIAL | 75% | Ya | Ya | Sebagian | Global policy | Filament legacy | Tabler shell | Tabler shell | Token auth | Ya | Login/reset password bekerja; permission granularity dan session UI belum lengkap. |
| University isolation | PARTIAL | 45% | Scope columns | Ya | Belum terpusat | CampusPolicy | Sebagian | N/A | N/A | Sebagian | Sebagian | Record authorization tersedia; query isolation lintas seluruh domain belum terbukti. |
| Tabler design system | PARTIAL | 45% | N/A | N/A | Branding | N/A | Foundation only | Shell | Shell | N/A | Smoke tests | Paket lokal, layout, komponen, dark/system theme tersedia; page content dan admin masih migrasi. |
| Organization | PARTIAL | 55% | Ya | Ya | Tidak | CampusPolicy | Filament CRUD | Tidak | Tidak | Tidak | Sebagian | University, campus, faculty, department, study program ada; building/lab belum ada. |
| Academic master | PARTIAL | 60% | Ya | Ya | Sebagian | CampusPolicy | Filament CRUD | Read portal | Read portal | Courses | Ya | Kalender, holiday, categories/types/equivalence/rules belum lengkap. |
| Course offering & schedule | PARTIAL | 55% | Ya | Ya | Validasi KRS | CampusPolicy | Filament CRUD | Read | Read | Tidak | Academic tests | Konflik ruang/dosen dan reschedule belum menjadi workflow lengkap. |
| KRS & advisor | PARTIAL | 75% | Ya | Ya | Ya | CampusPolicy | Filament | Ya | Read/advisor | KRS read | Ya | Validasi/finalisasi transaksi ada; history/revision/print dan workspace approval belum penuh. |
| Attendance | PARTIAL | 45% | Ya | Ya | Tidak | Global | Belum lengkap | Summary | Belum lengkap | Tidak | Seed/basic | Session/records ada; rotating token, correction, bulk UI, dan report belum lengkap. |
| Grades, KHS, GPA, transcript | PARTIAL | 65% | Ya | Ya | Ya | Global | Sebagian | Ya | Belum lengkap | Tidak | Ya | Kalkulasi dan portal tersedia; component workflow, lock revision, PDF/QR belum lengkap. |
| PMB | PARTIAL | 70% | Ya | Ya | Ya | Global | Filament applicant | Belum ada | N/A | Tidak | Ya | Lifecycle dan conversion idempotent tersedia; portal PMB/payment/exam UI belum end-to-end. |
| Student finance | PARTIAL | 55% | Ya | Ya | Allocation service | Global | Filament | Invoice read | N/A | Invoice/payment read | Ya | Decimal money dan allocation ada; refunds/installments/reconciliation/gateway belum lengkap. |
| Student lifecycle & leave | PARTIAL | 65% | Ya | Ya | Ya | Global | Sebagian | Belum penuh | Belum penuh | Tidak | Ya | Status history, leave, reactivation service ada; transfer/mutation UI belum ada. |
| LMS & assignment | PARTIAL | 50% | Ya | Ya | Submission service | Global | Assignment CRUD | Belum penuh | Belum penuh | Tidak | Ya | Module/content/assignment tersedia; classroom, announcement, discussion, file policy belum lengkap. |
| Quiz / CBT | PARTIAL | 55% | Ya | Ya | QuizAttemptService | Global | Belum penuh | Belum penuh | Belum penuh | Tidak | Ya | Attempt/timer/scoring core ada; builder dan complete Tabler CBT UI belum ada. |
| Scholarship | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Belum diimplementasikan. |
| MBKM, internship, KKN | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Belum diimplementasikan. |
| Thesis | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Belum diimplementasikan. |
| Yudisium & graduation | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Belum diimplementasikan. |
| Alumni, tracer, career | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Portal shell saja, belum ada domain workflow. |
| Library | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Belum diimplementasikan. |
| Research & community service | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Belum diimplementasikan. |
| Student affairs | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Belum diimplementasikan. |
| HRM & BKD | PARTIAL | 25% | Identity only | Employee/Lecturer | Tidak | Global | Tidak lengkap | N/A | Profile/read | Lecturer read | Seed/basic | Employee identity benar; HR workflow dan BKD belum ada. |
| Assets & procurement | MISSING | 0% | Tidak | Tidak | Tidak | Approval reusable | Tidak | N/A | N/A | Tidak | Tidak | Belum diimplementasikan. |
| Accounting | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | N/A | N/A | Tidak | Tidak | Double-entry foundation belum tersedia. |
| Documents & letters | MISSING | 0% | Tidak | Tidak | Tidak | Approval reusable | Tidak | Tidak | Tidak | Tidak | Tidak | Belum diimplementasikan. |
| Notifications | PARTIAL | 25% | Laravel table | User notification relation | Laravel | Global | Tidak | Tidak | Tidak | Tidak | Tidak | Belum ada notification center/preferences/template workflow. |
| Approval engine | PARTIAL | 65% | Ya | Ya | Ya | Global | Belum lengkap | Dipakai cuti | Dipakai cuti | Tidak | Ya | Generic multi-step core ada; builder dan integrasi domain lain belum lengkap. |
| Audit log | PARTIAL | 70% | Ya | Ya | Event/service usage | View permission | Read-only Filament | N/A | N/A | Tidak | Sebagian | UI read-only tersedia; audit coverage seluruh mutasi belum lengkap. |
| Integrations & PDDikti | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | N/A | N/A | Tidak | Tidak | Menunggu foundation adapter generik; credential eksternal akan tetap BLOCKED untuk live verification. |
| Reporting & export | PARTIAL | 25% | N/A | N/A | Controller dasar | Global | Satu halaman | Ringkasan | Ringkasan | Dashboard | Smoke only | Report center/filter/export queue belum lengkap. |
| Backup & system health | MISSING | 0% | Tidak | Tidak | Tidak | Tidak | Tidak | N/A | N/A | Tidak | Tidak | Belum diimplementasikan. |
| Documentation & public SEO | PARTIAL | 55% | Blog ada | Blog ada | IndexNow | Public | N/A | N/A | N/A | N/A | Portal smoke | Landing/docs/blog/sitemap ada; real screenshots dan sinkronisasi seluruh modul belum selesai. |

## Blockers eksternal

- Verifikasi live PDDikti, payment gateway, WhatsApp, SMTP, SSO, Redis production, object storage, dan deployment menunggu credential/infrastruktur milik institusi.
- Blocker tersebut tidak menghalangi pembuatan adapter generik, fake driver, queue, logging, retry, configuration UI, dan automated tests.

