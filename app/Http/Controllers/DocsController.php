<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\StudyProgram;
use App\Models\University;

class DocsController extends Controller
{
    public function index()
    {
        return view('pseo.docs-index', [
            'university' => University::query()->first(),
            'programs' => StudyProgram::with('department.faculty')->get(),
            'courses' => Course::query()->orderBy('code')->get(),
            'accounts' => [
                ['role' => 'Super Administrator', 'email' => 'admin@kampus.test', 'password' => 'password', 'scope' => 'Seluruh modul dan organisasi'],
                ['role' => 'Rektor', 'email' => 'rektor@kampus.test', 'password' => 'password', 'scope' => 'Dashboard strategis universitas'],
                ['role' => 'BAAK', 'email' => 'baak@kampus.test', 'password' => 'password', 'scope' => 'Akademik dan data mahasiswa'],
                ['role' => 'Finance', 'email' => 'finance@kampus.test', 'password' => 'password', 'scope' => 'Tagihan dan pembayaran'],
                ['role' => 'Dosen Wali', 'email' => 'dosen@kampus.test', 'password' => 'password', 'scope' => 'Kelas dan persetujuan KRS'],
                ['role' => 'Mahasiswa', 'email' => 'mahasiswa@kampus.test', 'password' => 'password', 'scope' => 'Portal akademik personal'],
            ],
            'tutorial' => [
                'Setup organisasi' => ['Isi profil universitas', 'Tambahkan kampus', 'Buat fakultas, departemen, dan program studi'],
                'Setup akademik' => ['Buat tahun akademik dan semester aktif', 'Susun kurikulum dan mata kuliah', 'Buat offering, kelas, dosen, dan jadwal'],
                'Siklus mahasiswa' => ['Import atau buat profil mahasiswa', 'Hubungkan enrollment dengan prodi dan kurikulum', 'Validasi status dan dosen wali'],
                'KRS sampai nilai' => ['Mahasiswa memilih kelas', 'Sistem validasi SKS, kapasitas, prasyarat, dan bentrok jadwal', 'Dosen wali menyetujui KRS', 'Dosen membuka presensi dan mengisi nilai'],
                'Keuangan' => ['Buat jenis biaya dan struktur tarif', 'Terbitkan invoice mahasiswa', 'Catat pembayaran sebagai ledger', 'Alokasikan pembayaran ke invoice'],
                'Kontrol & laporan' => ['Pantau audit log', 'Gunakan dashboard per role', 'Export data akademik dan keuangan', 'Jalankan backup terjadwal'],
            ],
        ]);
    }
}
