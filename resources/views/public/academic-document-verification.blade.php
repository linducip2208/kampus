<!doctype html>
<html lang="{{ $document->locale }}" data-bs-theme="light">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,follow"><title>Verifikasi {{ $document->document_number }} · {{ $document->university->name }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body>
<div class="page page-center"><main class="container container-tight py-5">
<div class="text-center mb-4"><a href="{{ route('home') }}" class="navbar-brand navbar-brand-autodark justify-content-center text-decoration-none"><span class="avatar bg-primary text-white me-2">{{ str($document->university->short_name ?: $document->university->name)->substr(0, 1) }}</span>{{ $document->university->name }}</a></div>
<div class="card card-md"><div class="card-body">
    <div class="text-center mb-4">
        <span class="avatar avatar-xl rounded-circle {{ $integrityValid ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }}"><i class="ti {{ $integrityValid ? 'ti-rosette-discount-check' : 'ti-alert-triangle' }} fs-1"></i></span>
        <h1 class="h2 mt-3 mb-1">{{ $integrityValid ? 'Dokumen valid' : 'Dokumen tidak valid' }}</h1>
        <p class="text-secondary">{{ $integrityValid ? 'Checksum dan status penerbitan berhasil diverifikasi.' : 'Status dicabut atau integritas snapshot tidak sesuai.' }}</p>
    </div>
    <dl class="row mb-0">
        <dt class="col-5 text-secondary">Nomor dokumen</dt><dd class="col-7 font-monospace">{{ $document->document_number }}</dd>
        <dt class="col-5 text-secondary">Jenis</dt><dd class="col-7">Transkrip sementara</dd>
        <dt class="col-5 text-secondary">Nama</dt><dd class="col-7">{{ data_get($document->snapshot, 'student.name') }}</dd>
        <dt class="col-5 text-secondary">NIM</dt><dd class="col-7">{{ data_get($document->snapshot, 'student.number') }}</dd>
        <dt class="col-5 text-secondary">Program studi</dt><dd class="col-7">{{ data_get($document->snapshot, 'student.study_program') }}</dd>
        <dt class="col-5 text-secondary">SKS lulus</dt><dd class="col-7">{{ data_get($document->snapshot, 'summary.earned_credits') }}</dd>
        <dt class="col-5 text-secondary">IPK</dt><dd class="col-7">{{ number_format((float) data_get($document->snapshot, 'summary.gpa'), 2) }}</dd>
        <dt class="col-5 text-secondary">Diterbitkan</dt><dd class="col-7">{{ $document->issued_at->translatedFormat('d F Y H:i') }}</dd>
    </dl>
</div><div class="card-footer text-secondary small"><i class="ti ti-shield-lock me-1"></i>Data ditampilkan dari snapshot dokumen immutable dengan checksum SHA-256.</div></div>
<div class="text-center text-secondary mt-4"><a href="{{ route('home') }}">Kembali ke situs universitas</a></div>
</main></div>
</body></html>