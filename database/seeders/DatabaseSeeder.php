<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\Campus;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Faculty;
use App\Models\FeeType;
use App\Models\GradeScale;
use App\Models\LectureMeeting;
use App\Models\LecturerProfile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\StudyPlanItem;
use App\Models\StudyProgram;
use App\Models\University;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $university = University::create([
            'name' => 'Universitas Cakrawala Nusantara', 'short_name' => 'UCN', 'code' => 'UCN',
            'email' => 'hello@ucn.test', 'phone' => '+62 21 555 0101', 'website' => 'https://kampus.test',
        ]);
        $campus = Campus::create(['university_id' => $university->id, 'name' => 'Kampus Utama Jakarta', 'code' => 'JKT', 'address' => 'Jl. Cakrawala No. 1, Jakarta']);
        $faculty = Faculty::create(['university_id' => $university->id, 'name' => 'Fakultas Teknologi Informasi', 'code' => 'FTI']);
        $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Departemen Informatika', 'code' => 'IF']);
        $program = StudyProgram::create(['department_id' => $department->id, 'name' => 'S1 Teknik Informatika', 'code' => 'IF', 'level' => 'S1', 'degree' => 'S.Kom', 'accreditation' => 'Baik Sekali', 'capacity' => 240]);
        $year = AcademicYear::create(['university_id' => $university->id, 'name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $year->id, 'name' => 'Ganjil 2026/2027', 'code' => '20261', 'term' => 'odd', 'starts_on' => '2026-09-01', 'ends_on' => '2027-02-28', 'is_active' => true]);
        Setting::create(['university_id' => $university->id, 'group' => 'academic', 'key' => 'maximum_credits_by_gpa', 'value' => json_encode([
            ['minimum' => 3.0, 'credits' => 24], ['minimum' => 2.5, 'credits' => 21], ['minimum' => 2.0, 'credits' => 18], ['minimum' => 0.0, 'credits' => 15],
        ], JSON_THROW_ON_ERROR)]);
        Setting::create(['university_id' => $university->id, 'group' => 'academic', 'key' => 'enforce_financial_hold', 'value' => 'false']);
        $curriculum = Curriculum::create(['study_program_id' => $program->id, 'name' => 'Kurikulum OBE 2026', 'year' => 2026, 'minimum_credits' => 144]);

        $courses = collect([
            ['code' => 'IF101', 'name' => 'Algoritma dan Pemrograman', 'theory_credits' => 3, 'practical_credits' => 1, 'recommended_term' => 1],
            ['code' => 'IF102', 'name' => 'Basis Data', 'theory_credits' => 3, 'practical_credits' => 1, 'recommended_term' => 1],
            ['code' => 'IF103', 'name' => 'Matematika Diskrit', 'theory_credits' => 3, 'practical_credits' => 0, 'recommended_term' => 1],
            ['code' => 'IF201', 'name' => 'Rekayasa Perangkat Lunak', 'theory_credits' => 3, 'practical_credits' => 0, 'recommended_term' => 3],
            ['code' => 'IF202', 'name' => 'Interaksi Manusia dan Komputer', 'theory_credits' => 2, 'practical_credits' => 0, 'recommended_term' => 3],
        ])->map(fn (array $course) => Course::create($course + ['university_id' => $university->id]));
        foreach ($courses as $course) {
            DB::table('curriculum_courses')->insert([
                'id' => (string) Str::ulid(), 'curriculum_id' => $curriculum->id, 'course_id' => $course->id,
                'term' => $course->recommended_term, 'is_mandatory' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $roles = collect([
            'super_admin' => 'Super Administrator', 'rektor' => 'Rektor', 'wakil_rektor' => 'Wakil Rektor',
            'dekan' => 'Dekan', 'wakil_dekan' => 'Wakil Dekan', 'ketua_prodi' => 'Ketua Program Studi',
            'baak' => 'BAAK', 'staff_akademik' => 'Staff Akademik', 'pmb' => 'Staff PMB', 'finance' => 'Finance',
            'hr' => 'HR', 'dosen' => 'Dosen', 'dosen_wali' => 'Dosen Wali', 'dosen_pembimbing' => 'Dosen Pembimbing',
            'mahasiswa' => 'Mahasiswa', 'perpustakaan' => 'Staff Perpustakaan', 'lppm' => 'Staff LPPM',
            'kemahasiswaan' => 'Kemahasiswaan', 'operator_pddikti' => 'Operator PDDikti', 'auditor' => 'Auditor', 'alumni' => 'Alumni',
        ])->mapWithKeys(fn ($label, $name) => [$name => Role::create(['name' => $name, 'label' => $label])]);

        $permissionDefinitions = [
            ['name' => 'universities.view', 'label' => 'Lihat universitas', 'module' => 'universities', 'action' => 'view'],
            ['name' => 'universities.create', 'label' => 'Buat universitas', 'module' => 'universities', 'action' => 'create'],
            ['name' => 'universities.update', 'label' => 'Ubah universitas', 'module' => 'universities', 'action' => 'update'],
            ['name' => 'faculties.view', 'label' => 'Lihat fakultas', 'module' => 'faculties', 'action' => 'view'],
            ['name' => 'faculties.create', 'label' => 'Buat fakultas', 'module' => 'faculties', 'action' => 'create'],
            ['name' => 'faculties.update', 'label' => 'Ubah fakultas', 'module' => 'faculties', 'action' => 'update'],
            ['name' => 'study_programs.view', 'label' => 'Lihat program studi', 'module' => 'study_programs', 'action' => 'view'],
            ['name' => 'study_programs.create', 'label' => 'Buat program studi', 'module' => 'study_programs', 'action' => 'create'],
            ['name' => 'study_programs.update', 'label' => 'Ubah program studi', 'module' => 'study_programs', 'action' => 'update'],
            ['name' => 'academic_years.view', 'label' => 'Lihat tahun akademik', 'module' => 'academic_years', 'action' => 'view'],
            ['name' => 'academic_years.create', 'label' => 'Buat tahun akademik', 'module' => 'academic_years', 'action' => 'create'],
            ['name' => 'academic_years.update', 'label' => 'Ubah tahun akademik', 'module' => 'academic_years', 'action' => 'update'],
            ['name' => 'semesters.view', 'label' => 'Lihat semester', 'module' => 'semesters', 'action' => 'view'],
            ['name' => 'semesters.create', 'label' => 'Buat semester', 'module' => 'semesters', 'action' => 'create'],
            ['name' => 'semesters.update', 'label' => 'Ubah semester', 'module' => 'semesters', 'action' => 'update'],
            ['name' => 'courses.view', 'label' => 'Lihat mata kuliah', 'module' => 'courses', 'action' => 'view'],
            ['name' => 'courses.create', 'label' => 'Buat mata kuliah', 'module' => 'courses', 'action' => 'create'],
            ['name' => 'courses.update', 'label' => 'Ubah mata kuliah', 'module' => 'courses', 'action' => 'update'],
            ['name' => 'curricula.view', 'label' => 'Lihat kurikulum', 'module' => 'curricula', 'action' => 'view'],
            ['name' => 'curricula.create', 'label' => 'Buat kurikulum', 'module' => 'curricula', 'action' => 'create'],
            ['name' => 'curricula.update', 'label' => 'Ubah kurikulum', 'module' => 'curricula', 'action' => 'update'],
            ['name' => 'course_offerings.view', 'label' => 'Lihat penawaran kuliah', 'module' => 'course_offerings', 'action' => 'view'],
            ['name' => 'course_offerings.create', 'label' => 'Buat penawaran kuliah', 'module' => 'course_offerings', 'action' => 'create'],
            ['name' => 'course_offerings.update', 'label' => 'Ubah penawaran kuliah', 'module' => 'course_offerings', 'action' => 'update'],
            ['name' => 'class_sections.view', 'label' => 'Lihat kelas', 'module' => 'class_sections', 'action' => 'view'],
            ['name' => 'class_sections.create', 'label' => 'Buat kelas', 'module' => 'class_sections', 'action' => 'create'],
            ['name' => 'class_sections.update', 'label' => 'Ubah kelas', 'module' => 'class_sections', 'action' => 'update'],
            ['name' => 'students.view', 'label' => 'Lihat mahasiswa', 'module' => 'students', 'action' => 'view'],
            ['name' => 'students.create', 'label' => 'Buat mahasiswa', 'module' => 'students', 'action' => 'create'],
            ['name' => 'students.update', 'label' => 'Ubah mahasiswa', 'module' => 'students', 'action' => 'update'],
            ['name' => 'student_enrollments.view', 'label' => 'Lihat enrollment', 'module' => 'student_enrollments', 'action' => 'view'],
            ['name' => 'student_enrollments.create', 'label' => 'Buat enrollment', 'module' => 'student_enrollments', 'action' => 'create'],
            ['name' => 'student_enrollments.update', 'label' => 'Ubah enrollment', 'module' => 'student_enrollments', 'action' => 'update'],
            ['name' => 'krs.view', 'label' => 'Lihat KRS', 'module' => 'krs', 'action' => 'view'],
            ['name' => 'krs.create', 'label' => 'Buat KRS', 'module' => 'krs', 'action' => 'create'],
            ['name' => 'krs.update', 'label' => 'Ubah KRS', 'module' => 'krs', 'action' => 'update'],
            ['name' => 'krs.approve', 'label' => 'Approve KRS', 'module' => 'krs', 'action' => 'approve'],
            ['name' => 'krs.submit', 'label' => 'Submit KRS', 'module' => 'krs', 'action' => 'submit'],
            ['name' => 'student_invoices.view', 'label' => 'Lihat tagihan', 'module' => 'student_invoices', 'action' => 'view'],
            ['name' => 'student_invoices.create', 'label' => 'Buat tagihan', 'module' => 'student_invoices', 'action' => 'create'],
            ['name' => 'payments.view', 'label' => 'Lihat pembayaran', 'module' => 'payments', 'action' => 'view'],
            ['name' => 'payments.create', 'label' => 'Buat pembayaran', 'module' => 'payments', 'action' => 'create'],
            ['name' => 'payments.verify', 'label' => 'Verifikasi pembayaran', 'module' => 'payments', 'action' => 'verify'],
            ['name' => 'employees.view', 'label' => 'Lihat pegawai dan dosen', 'module' => 'employees', 'action' => 'view'],
            ['name' => 'audit_logs.view', 'label' => 'Lihat audit log', 'module' => 'audit_logs', 'action' => 'view'],
        ];
        $permissions = collect($permissionDefinitions)->mapWithKeys(fn (array $permission) => [
            $permission['name'] => Permission::create($permission),
        ]);
        $allPermissions = $permissions->pluck('id')->all();
        $rolePermissions = [
            'super_admin' => $allPermissions,
            'rektor' => $permissions->filter(fn ($permission) => in_array($permission->action, ['view', 'approve'], true))->pluck('id')->all(),
            'dekan' => $permissions->filter(fn ($permission) => in_array($permission->module, ['faculties', 'study_programs', 'courses', 'curricula', 'class_sections', 'students', 'student_enrollments', 'krs'], true))->pluck('id')->all(),
            'baak' => $permissions->filter(fn ($permission) => in_array($permission->module, ['academic_years', 'semesters', 'courses', 'curricula', 'course_offerings', 'class_sections', 'students', 'student_enrollments', 'krs'], true))->pluck('id')->all(),
            'finance' => $permissions->filter(fn ($permission) => in_array($permission->module, ['student_invoices', 'payments'], true))->pluck('id')->all(),
            'dosen' => $permissions->filter(fn ($permission) => in_array($permission->module, ['courses', 'curricula', 'course_offerings', 'class_sections', 'students', 'student_enrollments', 'krs'], true) && $permission->action === 'view')->pluck('id')->all(),
            'dosen_wali' => $permissions->filter(fn ($permission) => in_array($permission->module, ['students', 'student_enrollments', 'krs'], true))->pluck('id')->all(),
            'mahasiswa' => $permissions->filter(fn ($permission) => in_array($permission->module, ['courses', 'class_sections', 'krs', 'student_invoices', 'payments'], true) && $permission->action === 'view')->pluck('id')->all(),
            'auditor' => $permissions->filter(fn ($permission) => $permission->action === 'view')->pluck('id')->all(),
        ];
        foreach ($rolePermissions as $roleName => $permissionIds) {
            $roles[$roleName]->permissions()->sync($permissionIds);
        }
        foreach ([
            ['name' => 'Sistem Administrator', 'email' => 'admin@kampus.test', 'role' => 'super_admin'],
            ['name' => 'Dr. Raka Pradipta', 'email' => 'rektor@kampus.test', 'role' => 'rektor'],
            ['name' => 'Nadia Finance', 'email' => 'finance@kampus.test', 'role' => 'finance'],
            ['name' => 'Sari BAAK', 'email' => 'baak@kampus.test', 'role' => 'baak'],
        ] as $account) {
            $user = User::create(['name' => $account['name'], 'email' => $account['email'], 'password' => Hash::make('password')]);
            $user->roles()->attach($roles[$account['role']]->id, ['university_id' => $university->id, 'campus_id' => $campus->id]);
        }

        $employeeUser = User::create(['name' => 'Dr. Bima Santosa', 'email' => 'dosen@kampus.test', 'password' => Hash::make('password')]);
        $employeeUser->roles()->attach($roles['dosen_wali']->id, ['university_id' => $university->id, 'campus_id' => $campus->id, 'faculty_id' => $faculty->id, 'study_program_id' => $program->id]);
        $employee = Employee::create(['user_id' => $employeeUser->id, 'university_id' => $university->id, 'employee_number' => 'EMP-2020-001', 'full_name' => $employeeUser->name, 'joined_on' => '2020-08-01']);
        $lecturer = LecturerProfile::create(['employee_id' => $employee->id, 'nidn' => '0312018001', 'academic_rank' => 'Lektor', 'specialization' => 'Sistem Informasi']);

        $studentUser = User::create(['name' => 'Alya Putri Maheswari', 'email' => 'mahasiswa@kampus.test', 'password' => Hash::make('password')]);
        $studentUser->roles()->attach($roles['mahasiswa']->id, ['university_id' => $university->id, 'campus_id' => $campus->id, 'faculty_id' => $faculty->id, 'study_program_id' => $program->id]);
        $student = StudentProfile::create(['user_id' => $studentUser->id, 'student_number' => '2026-IF-00001', 'full_name' => $studentUser->name, 'email' => $studentUser->email, 'phone' => '081234567890', 'gender' => 'female', 'birth_date' => '2008-03-19', 'birth_place' => 'Jakarta']);
        $enrollment = StudentEnrollment::create(['student_profile_id' => $student->id, 'study_program_id' => $program->id, 'curriculum_id' => $curriculum->id, 'advisor_id' => $lecturer->id, 'cohort' => 2026, 'status' => 'active', 'enrolled_on' => '2026-08-15']);

        $sections = $courses->take(3)->map(function (Course $course, int $index) use ($semester, $lecturer) {
            $offering = CourseOffering::create(['semester_id' => $semester->id, 'course_id' => $course->id, 'status' => 'published']);
            $section = ClassSection::create(['course_offering_id' => $offering->id, 'code' => $course->code.'-A', 'capacity' => 40, 'room' => 'R. '.(101 + $index), 'mode' => 'offline']);
            $section->lecturers()->attach($lecturer->id, ['is_primary' => true]);
            $meeting = LectureMeeting::create(['class_section_id' => $section->id, 'meeting_number' => 1, 'meeting_date' => now()->toDateString(), 'topic' => 'Orientasi dan kontrak kuliah', 'mode' => 'offline', 'status' => 'open']);
            $session = AttendanceSession::create(['lecture_meeting_id' => $meeting->id, 'method' => 'qr', 'expires_at' => now()->addHours(4)]);
            return compact('course', 'section', 'session');
        });
        $plan = StudyPlan::create(['student_enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'approved', 'total_credits' => $sections->sum(fn ($item) => $item['course']->credits), 'submitted_at' => now()->subDays(3), 'approved_at' => now()->subDays(2), 'approved_by' => $employeeUser->id, 'advisor_note' => 'KRS telah diverifikasi.']);
        $scale = GradeScale::create(['university_id' => $university->id, 'grade' => 'A', 'minimum_score' => 85, 'maximum_score' => 100, 'grade_point' => 4]);
        foreach ($sections as $item) {
            $planItem = StudyPlanItem::create(['study_plan_id' => $plan->id, 'class_section_id' => $item['section']->id, 'credits' => $item['course']->credits, 'status' => 'approved']);
            StudentGrade::create(['study_plan_item_id' => $planItem->id, 'grade_scale_id' => $scale->id, 'final_score' => 88, 'status' => 'published']);
            StudentAttendance::create(['attendance_session_id' => $item['session']->id, 'student_enrollment_id' => $enrollment->id, 'status' => 'present', 'recorded_at' => now()]);
        }
        $fee = FeeType::create(['university_id' => $university->id, 'name' => 'Uang Kuliah Tunggal', 'code' => 'UKT', 'is_recurring' => true]);
        $invoice = StudentInvoice::create(['student_enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'invoice_number' => 'INV/2026/09/000001', 'total_amount' => 7500000, 'paid_amount' => 4500000, 'status' => 'partial', 'due_on' => now()->addDays(14)]);
        $invoice->items()->create(['fee_type_id' => $fee->id, 'description' => 'UKT Semester Ganjil 2026/2027', 'amount' => 7500000]);

        $category = BlogCategory::create(['name' => 'Tata Kelola Akademik', 'slug' => 'tata-kelola-akademik', 'description' => 'Praktik mengelola data dan proses akademik.']);
        foreach ([
            ['title' => 'Mengapa KRS perlu diperlakukan sebagai workflow, bukan pivot table', 'slug' => 'krs-sebagai-workflow', 'excerpt' => 'KRS membawa aturan prasyarat, kapasitas, bentrok jadwal, approval, dan histori keputusan.', 'content' => 'KRS adalah keputusan akademik yang memengaruhi beban studi, jadwal, tagihan, dan rekam jejak mahasiswa. Karena itu, sistem perlu menyimpan status draft, submitted, approved, revision, hingga finalized. Validasi juga harus berjalan sebelum permintaan masuk ke dosen wali.'],
            ['title' => 'Fondasi single source of truth untuk universitas multi-kampus', 'slug' => 'single-source-of-truth-universitas', 'excerpt' => 'Struktur university, campus, faculty, department, dan study program menjaga konteks data.', 'content' => 'Data universitas menjadi berguna ketika setiap record punya konteks organisasi. Scope tersebut memungkinkan rektor melihat seluruh institusi, sementara ketua program studi hanya melihat data yang menjadi tanggung jawabnya.'],
            ['title' => 'Payment ledger membuat tagihan mahasiswa bisa diaudit', 'slug' => 'payment-ledger-tagihan-mahasiswa', 'excerpt' => 'Status paid tidak cukup; sistem harus menyimpan alokasi setiap pembayaran ke invoice.', 'content' => 'Pembayaran yang baik tidak sekadar mengubah kolom status. Payment dicatat sebagai transaksi, lalu dialokasikan ke satu atau beberapa invoice. Dengan pola ini, rekonsiliasi dan audit menjadi mungkin.'],
            ['title' => 'Cara menyiapkan semester baru tanpa spreadsheet terpisah', 'slug' => 'menyiapkan-semester-baru', 'excerpt' => 'Urutan setup akademik yang terukur dari tahun ajaran sampai kelas.', 'content' => 'Mulai dari tahun akademik, semester, kurikulum, mata kuliah, offering, kelas, dosen, ruang, dan jadwal. Urutan ini membuat pilihan KRS mahasiswa memiliki sumber data yang konsisten.'],
        ] as $post) {
            BlogPost::create($post + ['category_id' => $category->id, 'author_id' => $employeeUser->id, 'published_at' => now()->subDays(random_int(1, 20)), 'is_published' => true, 'meta_title' => $post['title'], 'meta_description' => $post['excerpt']]);
        }
    }
}
