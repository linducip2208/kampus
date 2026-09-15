@extends('portal.layout', ['heading' => 'Dokumen akademik terverifikasi'])

@section('content')
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
<div class="d-flex justify-content-end gap-2 mb-3 d-print-none">
    <a href="{{ route('portal.academic-record') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-2"></i>Kembali</a>
    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="ti ti-printer me-2"></i>Cetak / Simpan PDF</button>
</div>
<div class="card">
<div class="card-body p-4 p-md-5">
    <header class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-bottom pb-4 mb-4">
        <div><div class="text-uppercase text-secondary small fw-bold">Transkrip akademik sementara</div><h1 class="h2 mb-1">{{ $document->university->name }}</h1><div class="font-monospace">{{ $document->document_number }}</div></div>
        <div class="d-flex align-items-center gap-3"><canvas data-qr-value="{{ route('academic-documents.verify', $document->verification_token) }}" width="116" height="116" aria-label="QR verifikasi dokumen"></canvas><div><x-tabler.status :value="$integrityValid ? 'valid' : 'invalid'" /><div class="small text-secondary mt-2">Pindai untuk verifikasi publik</div></div></div>
    </header>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="text-secondary small">Nama mahasiswa</div><strong>{{ data_get($document->snapshot, 'student.name') }}</strong></div>
        <div class="col-md-4"><div class="text-secondary small">NIM</div><strong>{{ data_get($document->snapshot, 'student.number') }}</strong></div>
        <div class="col-md-4"><div class="text-secondary small">Program studi</div><strong>{{ data_get($document->snapshot, 'student.study_program') }}</strong></div>
    </div>
    <div class="table-responsive"><table class="table table-bordered table-vcenter"><thead><tr><th>No.</th><th>Kode</th><th>Mata kuliah</th><th>Semester</th><th class="text-center">SKS</th><th class="text-center">Nilai</th><th class="text-center">Bobot</th></tr></thead><tbody>
        @forelse(data_get($document->snapshot, 'courses', []) as $course)<tr><td>{{ $loop->iteration }}</td><td class="font-monospace">{{ $course['code'] }}</td><td>{{ $course['name'] }}</td><td>{{ $course['semester'] }} {{ $course['academic_year'] }}</td><td class="text-center">{{ $course['credits'] }}</td><td class="text-center fw-bold">{{ $course['grade'] }}</td><td class="text-center">{{ number_format((float) $course['grade_point'], 2) }}</td></tr>@empty<tr><td colspan="7" class="text-center text-secondary py-4">Belum ada nilai terpublikasi.</td></tr>@endforelse
    </tbody></table></div>
    <div class="row mt-4"><div class="col-md-6"><div class="text-secondary small">Checksum dokumen</div><code class="text-break">{{ $document->payload_checksum }}</code></div><div class="col-md-6 text-md-end mt-3 mt-md-0"><div>SKS lulus: <strong>{{ data_get($document->snapshot, 'summary.earned_credits') }}</strong></div><div>IPK: <strong>{{ number_format((float) data_get($document->snapshot, 'summary.gpa'), 2) }}</strong></div></div></div>
    <footer class="border-top mt-4 pt-3 small text-secondary">Diterbitkan {{ $document->issued_at->translatedFormat('d F Y H:i') }}. Verifikasi: <a href="{{ route('academic-documents.verify', $document->verification_token) }}">{{ route('academic-documents.verify', $document->verification_token) }}</a></footer>
</div>
</div>
@endsection