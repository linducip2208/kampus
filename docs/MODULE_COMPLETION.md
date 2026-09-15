# Campus ERP Module Completion

Dokumen ini merekam keadaan source code aktual, bukan target PRD. Status hanya `COMPLETE`, `PARTIAL`, `MISSING`, atau `BLOCKED`.

| Module | Status | Completeness | Database | Model | Service | Policy | Admin UI | Student UI | Lecturer UI | API | Test | Notes |
|---|---|---:|---|---|---|---|---|---|---|---|---|---|
| Foundation, auth, RBAC | PARTIAL | 75% | Ya | Ya | Sebagian | Global policy | Filament legacy | Tabler shell | Tabler shell | Token auth | Ya | Login/reset password bekerja; permission granularity dan session UI belum lengkap. |
| University isolation | PARTIAL | 75% | Scope columns | Ya | Belum terpusat | CampusPolicy | Sebagian | N/A | N/A | Sebagian | Sebagian | Record authorization, API, dan seluruh resource Filament aktif memakai centralized UniversityScope; domain baru wajib didaftarkan dan diuji. |
| Tabler design system | PARTIAL | 70% | N/A | N/A | Branding | N/A | Custom dashboard | Native pages | Native pages | N/A | Smoke tests | Paket lokal, komponen, dark/system theme, admin dashboard, dan seluruh halaman portal aktif sudah Tabler; management page masih migrasi dari Filament. |
| Student portal | PARTIAL | 79% | Core academic/finance/LMS/lifecycle | Ya | Academic record/attendance/learning/lifecycle | Role/KRS/ownership guard | N/A | Tabler dashboard/KRS/KHS/invoice/attendance/LMS/CBT/discussion/cuti/reaktivasi | N/A | Read API | Ya | Halaman aktif sudah native Tabler termasuk cuti, aktif kembali, riwayat status, materi, assignment, CBT, pengumuman, dan forum; transfer/thesis belum tersedia. |
| Lecturer portal | PARTIAL | 85% | Core lecturer/class/LMS | Ya | Workspace/attendance/quiz/communication | Role guard + class ownership | N/A | N/A | Tabler dashboard/schedule/classes/advisees/attendance/LMS/CBT/discussion | Lecturer API | Ya | Presensi, grade, LMS/quiz authoring dan grading, pengumuman, forum, serta lock diskusi tersedia; KRS approval/thesis belum tersedia. |
| Organization | PARTIAL | 55% | Ya | Ya | Tidak | CampusPolicy | Filament CRUD | Tidak | Tidak | Tidak | Sebagian | University, campus, faculty, department, study program ada; building/lab belum ada. |
| Academic master | PARTIAL | 60% | Ya | Ya | Sebagian | CampusPolicy | Filament CRUD | Read portal | Read portal | Courses | Ya | Kalender, holiday, categories/types/equivalence/rules belum lengkap. |
| Course offering & schedule | PARTIAL | 72% | Ya | Ya | Collision service | CampusPolicy/role guard | Tabler schedule + Filament class | Read | Read | Tidak | Ya | Mutasi jadwal transaksional mencegah bentrok ruang, dosen, dan kelas; recurrence/reschedule/substitution lanjutan belum lengkap. |
| KRS & advisor | PARTIAL | 75% | Ya | Ya | Ya | CampusPolicy | Filament | Ya | Read/advisor | KRS read | Ya | Validasi/finalisasi transaksi ada; history/revision/print dan workspace approval belum penuh. |
| Attendance | PARTIAL | 75% | Ya | Ya | AttendanceService | Lecturer/class guard | Tabler lecturer workspace | Tabler self attendance | Tabler manage/correction | Tidak | Ya | Manual/PIN/hashed QR-token, expiry, KRS eligibility, duplicate prevention, correction history, dan audit tersedia; QR rendering/rotation otomatis serta report agregat masih berikutnya. |
| Grades, KHS, GPA, transcript | PARTIAL | 96% | Ya | Ya | GradeCalculator + workflow | Role/service guard | Tabler approval/publish/lock | KHS + bilingual printable transcript | Tabler scheme/component/submit | Tidak | Ya | Hanya published/locked masuk record; configurable BEST_GRADE/LATEST/ALL_ATTEMPTS tersedia. Revisi terkunci dan immutable temporary transcript snapshot dengan nomor, SHA-256 checksum, QR, public verification, ownership, dan tamper tests tersedia; API dan final-graduation transcript lifecycle masih berikutnya. |
| PMB | PARTIAL | 70% | Ya | Ya | Ya | Global | Filament applicant | Belum ada | N/A | Tidak | Ya | Lifecycle dan conversion idempotent tersedia; portal PMB/payment/exam UI belum end-to-end. |
| Student finance | PARTIAL | 65% | Ya | Ya | Allocation + refund services | Global | Filament | Invoice read | N/A | Invoice/payment read | Ya | Decimal money, partial allocation, immutable refund ledger, invoice reconciliation, audit, dan ledger-backed demo tersedia; installments/discount/penalty/reconciliation UI serta gateway generik belum lengkap. |
| Student lifecycle & leave | PARTIAL | 85% | Ya | Ya | Leave + reactivation + status services | Ownership + scoped BAAK | Tabler approval/rejection | Tabler request/history | N/A | Tidak | Ya | Cuti dan aktif kembali end-to-end menggunakan generic approval, transaksi, reject reason, status history, dan audit. Transfer/mutasi serta multi-step workflow builder lanjutan belum lengkap. |
| LMS & assignment | PARTIAL | 88% | Ya | Ya | Submission + authoring + communication services | KRS/class ownership guard | Assignment CRUD | Tabler material/assignment/announcement/discussion | Tabler authoring/grading/announcement/discussion | Tidak | Ya | Published content dibatasi KRS; assignment attempt/graded lock, pengumuman, forum reply, dosen lock/unlock, dan audit tersedia. Lifecycle edit/publish lanjutan serta centralized private attachment policy belum lengkap. |
| Quiz / CBT | PARTIAL | 88% | Ya | Ya | QuizAttemptService + QuizAuthoringService | Role/KRS/class ownership guard | Belum penuh | Tabler attempt/timer/navigation/result | Tabler bank soal/builder/attempt/manual grading | Tidak | Ya | Workflow student dan lecturer usable dengan random order persisten, expiry, attempt limit, objective auto-grading, manual essay grading, feedback, dan audit. Lifecycle edit/publish/close serta API/report statistik belum lengkap. |
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

