<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\StudyProgram;

class CatalogController extends Controller
{
    public function program(string $id)
    {
        return view('catalog.program', ['program' => StudyProgram::with(['department.faculty', 'curricula.courses'])->findOrFail($id)]);
    }

    public function course(string $id)
    {
        return view('catalog.course', ['course' => Course::with(['curricula.studyProgram', 'prerequisites'])->findOrFail($id)]);
    }

    public function faq()
    {
        return view('catalog.faq', ['faqs' => [
            ['q' => 'Apa itu Campus ERP?', 'a' => 'Campus ERP adalah fondasi manajemen universitas untuk menghubungkan akademik, mahasiswa, dosen, keuangan, dan pelaporan.'],
            ['q' => 'Apakah mendukung multi-kampus?', 'a' => 'Ya. Struktur university, campus, faculty, department, dan study program menyimpan konteks organisasi sejak awal.'],
            ['q' => 'Bagaimana keamanan data?', 'a' => 'Aplikasi menggunakan permission granular, scope organisasi, audit log immutable, validasi backend, dan transaksi database untuk proses kritikal.'],
            ['q' => 'Apakah portal mahasiswa tersedia?', 'a' => 'Portal mahasiswa mencakup dashboard, KRS, KHS, transkrip, jadwal, presensi, dan invoice semester.'],
        ]]);
    }

    public function contact()
    {
        return view('catalog.contact');
    }
}
