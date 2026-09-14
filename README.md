# Campus ERP

Enterprise University Management System / SIAKAD Universitas

Campus ERP adalah modular monolith Laravel yang menghubungkan seluruh lifecycle perguruan tinggi: PMB, registrasi, mahasiswa, akademik, KRS, perkuliahan, presensi, LMS/CBT, nilai, KHS, transkrip, keuangan, MBKM, magang, KKN, skripsi, yudisium, wisuda, alumni, operasi universitas, laporan, approval, audit, integrasi, dan portal mandiri.

Status implementasi: project dikerjakan bertahap. Setiap modul harus memiliki schema, model, service, authorization, UI, workflow, audit, test, dan dokumentasi sebelum diberi status COMPLETE. Integrasi eksternal tidak disebut live tanpa credential dan environment yang valid.

## Bahasa Indonesia

### Nilai produk

Campus ERP menjadi single source of truth universitas, menghilangkan spreadsheet terpisah, menyatukan akademik dan keuangan, menyediakan portal mahasiswa/dosen, mendukung multi-kampus, pelaporan, audit, integrasi PDDikti/Neo Feeder, SaaS readiness, dan white-label branding.

### Semua fitur

- Organisasi dan master: universitas, multi-kampus, fakultas, departemen, program studi, gedung, ruangan, laboratorium, jenjang pendidikan, gelar, tahun akademik, semester, kalender, hari libur, mata kuliah, kelompok/jenis mata kuliah, kurikulum, prasyarat, ekuivalensi, skala nilai, jabatan akademik, status mahasiswa, jalur masuk, bank, metode pembayaran, negara, provinsi, kota, dan kecamatan.
- RBAC dan keamanan: user, role, permission granular, scope universitas/kampus/fakultas/prodi, policy backend, login history, session management, password policy, rate limiting, CSRF/XSS/SQL injection protection, private file, encrypted credentials, audit immutable, dan kesiapan 2FA.
- PMB: applicant, gelombang/jalur, pilihan banyak prodi, biodata, dokumen, pembayaran formulir, verifikasi, ujian/CBT, wawancara, scoring, ranking, passing grade, pengumuman, registrasi ulang, nomor pendaftaran, konversi idempotent ke mahasiswa, NIM, dan akun mahasiswa.
- Mahasiswa: profile dan enrollment terpisah, NIM, cohort, kurikulum, dosen wali, status active/leave/inactive/suspended/dropout/resigned/transferred/graduated/deceased, status history, cuti, reaktivasi, mutasi, pindahan, dan credit transfer.
- Dosen dan pegawai: employee identity, employee profile, lecturer profile, NIDN/NIDK, jabatan, unit kerja, pendidikan, sertifikasi, kontrak, absensi, cuti, teaching load, BKD, penelitian, dan pengabdian.
- Akademik: course, curriculum course, mandatory/elective, SKS teori/praktik, minimum grade, prerequisite, equivalence, course offering, class section, multi-dosen, koordinator, kapasitas, mode offline/online/hybrid, ruang, jadwal, pertemuan, reschedule, dan dosen pengganti.
- KRS: draft, submit, advisor review, approve, revision, reject, finalized, locked; validasi status, periode, prasyarat, duplicate course, maksimum SKS berdasarkan IPS, bentrok ruang/dosen/kelas, kapasitas, academic hold, financial hold, approval history, consultation, catatan, dan print.
- Presensi: lecture meeting, attendance session, presensi mahasiswa/dosen, manual, QR rotating, PIN, RFID/mobile/GPS ready, expiry, device/IP/time/location log, correction, approval, persentase, dan report.
- LMS dan CBT: classroom, module, material text/file/video/URL/document, announcement, assignment, submission, deadline, late submission, feedback, question bank, kategori soal, single/multiple choice, true/false, short answer, essay, quiz, random question/option, timer, attempt limit, auto-grading, dan manual grading.
- Nilai, KHS, IPS, IPK, transkrip: assessment component, attendance/assignment/quiz/UTS/UAS/practical/participation, bobot 100%, lifecycle draft-submitted-approved-published-locked, grade revision, attempted/earned/failed/completed credits, repeat policy latest/best/configurable, KHS semester, transkrip sementara/final, Bahasa Indonesia/English, PDF-friendly output, dan QR verification.
- Keuangan mahasiswa: fee type, fee structure, fee item, bill, invoice, installment, discount, penalty, fine, scholarship award, payment, allocation, partial payment, refund, reconciliation, ledger, decimal money, reversal audit, dan payment gateway abstraction.
- Beasiswa: program, periode, requirement, application, verification, selection, award, disbursement, fixed/percentage/full tuition, serta pengurangan tagihan.
- MBKM, magang, KKN: partner/company, period, application, placement, supervisor/mentor, proposal, activity, logbook, assessment, report, certificate, group/location, dan credit conversion.
- Skripsi: eligibility, topic, proposal, supervisor, guidance history, seminar proposal, submission, similarity check, defense registration, examiner, defense, revision, grade, final approval, repository, status history, dan audit.
- Yudisium dan wisuda: eligibility rule, minimum SKS/IPK, mandatory courses, thesis completion, finance/library/academic clearance, yudisium period, verification, approval, graduation number, diploma number, batch, seat, transcript final, SKPI, certificate, QR verification, dan alumni conversion.
- Alumni dan career center: alumni profile, employment, education, business, tracer survey/section/question/option/response/answer, response rate, employment rate, waiting period, industry, salary range, relevance, company, vacancy, application, dan career event.
- Perpustakaan: library, author, publisher, category, book, copy, barcode/RFID ready, e-library, member, loan, loan item, return, reservation, fine, loan rules, overdue calculation, dan clearance yudisium.
- Penelitian dan LPPM: scheme, proposal, member, reviewer, review, grant/funding, budget, milestone, publication, Journal/Conference/Book/Patent/HKI/Prototype, community service program/proposal/member/funding/activity/output, review workflow, dan completion.
- Kemahasiswaan: organization, UKM, member, activity, achievement, violation, counseling, event, dan activity scholarship.
- HRM kampus: employee, organizational unit, position, employment type, contract, attendance, leave, education history, certification, lecturer profile, NIDN/NIDK, teaching assignment, workload, dan BKD.
- Aset dan procurement: asset category, asset, campus/building/room/unit location, assignment, movement, maintenance, depreciation, vendor, purchase request/item, RFQ, purchase order/item, receiving, vendor invoice/payment, dan approval.
- Accounting: chart of accounts, fiscal period, journal, journal entry/line, ledger, cost center, budget, receivable, payable, cash/bank, configurable mapping, double-entry debit = credit, trial balance, general ledger, income statement, balance sheet, cash flow, dan period closing.
- Dokumen dan surat: centralized file manager, metadata checksum/storage, category, version, attachment, access, approval, template, private authorized download, surat aktif, rekomendasi, penelitian, magang, transcript, legalisasi, custom letter, generate, sign, dan QR validation.
- Approval, notifikasi, audit: generic multi-step workflow untuk KRS, cuti, beasiswa, procurement, finance adjustment, grade revision, thesis, research, yudisium, dan surat; Laravel database/email notification; WhatsApp/SMS/push ready; preference; unread/read; actor, old/new values, IP, user agent.
- Integrasi: PDDikti mapping/sync job/item/log/error/retry/reconciliation, payment gateway, SMTP, WhatsApp, Google, Microsoft, SSO, LDAP/SAML ready, S3, webhook, API keys terenkripsi, connection test, dan Integration Center.
- Import, export, reporting: upload, mapping, validation, preview, process, result, error report; CSV/XLSX/PDF dan queue; laporan akademik, finance, PMB, dosen, wisuda, alumni, LPPM, operasional, dan manajemen.
- Dashboard dan search: dashboard role-based untuk Rektor, Wakil Rektor, Dekan, BAAK, Finance, PMB, Dosen, Mahasiswa; KPI database-backed, trend, retention, dropout, graduation, GPA, collection, outstanding, conversion, rasio dosen, filter scope, dan global search berizin.
- Operasi sistem: settings universitas/branding/academic/finance/PMB/LMS/thesis/notification/integration/security/storage/backup, number sequence race-safe untuk NIM/PMB/invoice/payment/letter/PO/journal/graduation, backup database/file, retention, health check, failed jobs, queue, scheduler, cache, S3/local fallback, dan deployment.
- Portal: portal mahasiswa, dosen, applicant, alumni dengan dashboard, KRS, jadwal, presensi, KHS, transkrip, LMS, assignment, quiz, finance, surat, thesis, graduation, profile, announcement, notification, responsive mobile UI, dan self-service.

### Alur end-to-end

Applicant mendaftar sampai menjadi mahasiswa; admin menyiapkan organisasi, semester, kurikulum, kelas, dosen, ruang, jadwal, dan pertemuan; mahasiswa KRS dan mendapat approval dosen wali; presensi dan nilai menghasilkan KHS, IPS, IPK, transkrip; finance menerbitkan invoice dan payment allocation; mahasiswa menyelesaikan skripsi, yudisium, wisuda, lalu menjadi alumni. Perubahan kritis menggunakan transaksi, locking, approval, event, notification, dan audit.

## English

### Product value

Campus ERP is a university single source of truth that removes fragmented spreadsheets, connects academics and student finance, supports multi-campus organizations, provides student and lecturer self-service, enables higher-education reporting, records sensitive changes, and prepares the institution for PDDikti integration, SaaS, and white-label deployments.

### Full feature coverage

- Organization and master data: university, campuses, faculties, departments, study programs, buildings, rooms, laboratories, education levels, degrees, academic years, semesters, calendars, holidays, courses, course groups/types, curricula, prerequisites, equivalences, grade scales, academic positions, student statuses, admission paths, banks, payment methods, and administrative regions.
- Identity, RBAC, and security: users, roles, granular permissions, university/campus/faculty/program scopes, backend policies, login history, secure sessions, password policy, rate limiting, CSRF/XSS/SQL protection, private files, encrypted credentials, immutable audit, and 2FA readiness.
- Admissions and student lifecycle: applicants, admission paths, multiple program choices, documents, payments, verification, exams, interviews, scoring, ranking, announcements, re-registration, idempotent conversion, NIM, accounts, enrollment history, leave, reactivation, transfers, dropout, resignation, graduation, and credit transfer.
- Academic core: employees, lecturers, NIDN/NIDK, curricula, courses, prerequisites, equivalences, offerings, sections, lecturers, coordinators, capacity, rooms, schedules, meetings, rescheduling, substitutions, study plans, advisor review, attendance, grades, KHS, IPS, GPA, transcripts, and QR verification.
- Learning and assessment: classrooms, modules, materials, announcements, assignments, submissions, deadlines, late work, question banks, CBT, quizzes, randomization, timer, attempts, automatic objective grading, manual essay grading, assessment components, grade locking, and revision approval.
- Finance and scholarships: fees, fee structures, bills, invoices, installments, discounts, penalties, fines, payments, allocations, partial payments, refunds, reconciliation, decimal ledger, scholarship applications/awards/disbursements, and configurable payment gateway providers.
- Student programs and final project: MBKM, internships, KKN, partners, placements, supervisors, logbooks, assessments, credit conversion, thesis topics/proposals/guidance/seminars/defense/examiners/revisions/repository, yudisium eligibility, graduation documents, alumni, tracer study, and career center.
- Campus services and ERP: library and clearance, research/LPPM, student affairs, HRM, teaching workload/BKD, assets, procurement, vendors, RFQ, purchase orders, receiving, double-entry accounting, documents, letters, approvals, notifications, backups, health, imports, exports, reports, APIs, PDDikti foundation, Integration Center, and responsive portals.
- Dashboard and operations: role-aware executive/BAAK/finance/admissions/lecturer/student dashboards, real database metrics, filters, search, queues, scheduler, cache, private storage, audit, and deployment controls.

### End-to-end workflow

An applicant becomes an enrolled student through payment, verification, selection, re-registration, and idempotent conversion. Administration configures the academic structure, courses, sections, lecturers, rooms, schedules, and meetings. Students submit validated study plans for advisor approval. Attendance and component grades produce KHS, IPS, GPA, and transcripts. Finance issues invoices and reconciles allocated payments. Thesis completion leads to eligibility, yudisium, graduation, and alumni conversion.

## العربية

### قيمة المنتج

Campus ERP هو مصدر موحد لبيانات الجامعة، يقلل جداول البيانات المنفصلة، يربط الدراسة بالمالية، ويدعم تعدد الحُرُم والكليات والبرامج. يوفر بوابات ذاتية للطالب والمحاضر، تقارير التعليم العالي، تدقيق التغييرات الحساسة، والاستعداد لتكامل PDDikti وSaaS وWhite-label.

### نطاق المزايا الكامل

- الهيكل التنظيمي والبيانات الأساسية: الجامعة، الحُرُم، الكليات، الأقسام، البرامج، المباني، القاعات، المختبرات، مستويات التعليم، الدرجات العلمية، السنوات والفصول الأكاديمية، التقويم والعطل، المقررات، المجموعات والأنواع، المناهج، المتطلبات السابقة، المعادلات، سلالم الدرجات، المناصب الأكاديمية، حالات الطلاب، مسارات القبول، البنوك، طرق الدفع، والمناطق الإدارية.
- الهوية والصلاحيات والأمان: المستخدمون، الأدوار، الصلاحيات الدقيقة، نطاق الجامعة/الحرم/الكلية/البرنامج، سياسات الخادم، سجل الدخول، الجلسات الآمنة، سياسة كلمة المرور، تحديد المعدل، حماية CSRF/XSS/SQL، الملفات الخاصة، تشفير بيانات الاعتماد، سجل تدقيق غير قابل للحذف، والاستعداد للمصادقة الثنائية.
- القبول وحياة الطالب: المتقدمون، المسارات، اختيار عدة برامج، المستندات، الدفع، التحقق، الاختبارات، المقابلات، التقييم والترتيب، إعادة التسجيل، التحويل الآمن دون تكرار، رقم الطالب والحساب، تاريخ التسجيل، الإجازة، إعادة التفعيل، النقل، الفصل، الانسحاب، التخرج، وتحويل الساعات.
- الأكاديميات الأساسية: الموظفون والمحاضرون وNIDN/NIDK، المناهج والمقررات والمتطلبات والمعادلات والعروض والشعب والمحاضرون والسعة والقاعات والجداول واللقاءات وإعادة الجدولة والبدائل وخطط الدراسة وموافقة المرشد والحضور والدرجات وكشف الدرجات وIPS وGPA والسجل والتحقق عبر QR.
- التعلم والتقييم: الفصول والوحدات والمواد والإعلانات والواجبات والتسليم والمواعيد المتأخرة وبنوك الأسئلة وCBT والاختبارات والعشوائية والمؤقت والمحاولات والتصحيح الآلي واليدوي ومكونات التقييم وقفل الدرجة وموافقة التعديل.
- المالية والمنح: الرسوم والهياكل والفواتير والأقساط والخصومات والغرامات والمدفوعات والتخصيص والدفع الجزئي والاسترداد والتسوية والسجل العشري والمنح والطلبات والجوائز والصرف وبوابات دفع قابلة للإعداد.
- البرامج والمشروع النهائي: MBKM والتدريب وKKN والشركاء والتوزيع والمشرفون والسجلات والتقييم وتحويل الساعات، موضوع ومقترح وإرشاد وندوة ومناقشة وممتحنون ومراجعات ومستودع الرسالة، أهلية التخرج، التخرج، الوثائق، الخريجون، التتبع، ومركز الوظائف.
- خدمات الجامعة وERP: المكتبة والتصفية، البحث وLPPM، شؤون الطلاب، HRM، العبء التدريسي وBKD، الأصول والمشتريات والموردون وRFQ وأوامر الشراء والاستلام، المحاسبة بالقيد المزدوج، المستندات والخطابات والموافقات والإشعارات والنسخ الاحتياطي والصحة والاستيراد والتصدير والتقارير وAPI وPDDikti ومركز التكامل والبوابات المتجاوبة.
- لوحات التشغيل: لوحات مخصصة لرئيس الجامعة والعميد وBAAK والمالية والقبول والمحاضر والطالب، مؤشرات حقيقية من قاعدة البيانات، فلاتر، بحث مصرح، queue، scheduler، cache، تخزين خاص، وتدقيق.

### سير العمل الكامل

يتحول المتقدم إلى طالب بعد التسجيل والدفع والتحقق والاختيار وإعادة التسجيل والتحويل الآمن. تجهز الإدارة الهيكل الأكاديمي والمقررات والشعب والمحاضرين والقاعات والجداول واللقاءات. يرسل الطالب خطة دراسته للموافقة. ينتج الحضور والدرجات كشف الدرجات وIPS وGPA والسجل. تصدر المالية الفواتير وتطابق المدفوعات. يؤدي إكمال الرسالة إلى الأهلية ثم اليوديسيوم والتخرج والخريجين.

## Technology stack

- Laravel 13, PHP 8.3 local, PHP 8.4+ recommended for production
- Tabler 1.5 (target UI produksi), Tabler Icons, Blade/Livewire 4; Filament 5 dan Tailwind dipertahankan sementara selama migrasi feature parity
- MySQL 8.4+ production; SQLite for local/testing
- Redis cache and queue, Laravel Scheduler, S3-compatible storage with local fallback
- Sanctum REST API under /api/v1
- Pest/PHPUnit, Laravel Pint, Vite

## Installation

~~~bash
composer install
copy .env.example .env
php artisan key:generate
# Configure database, cache, queue, mail, and storage settings.
php artisan migrate --seed
npm install
npm run build
php artisan serve
~~~

For local SQLite, set DB_CONNECTION=sqlite and use database/database.sqlite.

## Demo accounts

All demo passwords are password.

| Role | Email | Workspace |
|---|---|---|
| Super Admin | admin@kampus.test | /admin |
| Rector | rektor@kampus.test | /admin |
| BAAK | baak@kampus.test | /admin |
| Finance | finance@kampus.test | /admin |
| Lecturer / Advisor | dosen@kampus.test | /lecturer |
| Student | mahasiswa@kampus.test | /portal |

Tabler branded login: /login. `/admin` masih memakai Filament sementara custom Tabler admin mencapai feature parity.

## Verification

~~~bash
composer validate
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
./vendor/bin/pint
npm install
npm run build
php artisan seo:indexnow
php artisan backup:database
~~~

Queue worker: php artisan queue:work. Scheduler: php artisan schedule:work or a production cron entry.

## Documentation

- ARCHITECTURE.md — architecture and module boundaries
- PRD.md — product requirements
- ERD.md — core relationships
- DATABASE.md — database conventions
- PERMISSIONS.md — roles and scopes
- MODULES.md — actual module status
- DEVELOPMENT_PLAN.md — phased roadmap
- DEPLOYMENT.md — production deployment
- /docs — public documentation, demo accounts, tutorials, and feature guides
- docs/MODULE_MATRIX.md — delivery matrix
- docs/WORKFLOWS.md — business workflows
- docs/API.md — API contract
- docs/SECURITY.md — security controls
- docs/TABLER_DESIGN_SYSTEM.md — design system
- docs/PDDIKTI.md — PDDikti foundation
- docs/PAYMENT_GATEWAY.md — generic payment configuration
- docs/MODULE_COMPLETION.md — completion percentages

## Public pages and SEO

The public landing page is /. Documentation is /docs; sitemap is /sitemap.xml; robots is /robots.txt; blog and RSS are /blog and /blog/feed.xml. Submit the deployed sitemap path to Google Search Console. IndexNow uses public/indexnow-key.txt and the seo:indexnow command. Internal paths such as /admin and /api must not be indexed.

## Production notes

Use HTTPS, MySQL, Redis, queue workers, scheduler, private object storage, encrypted integration credentials, database/file backups, health checks, and monitoring. Configure one environment per institution. Do not claim live PDDikti or payment connectivity until the institution has supplied and tested credentials. Never force-push main.
