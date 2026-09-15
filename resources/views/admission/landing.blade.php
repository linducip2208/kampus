@extends('layouts.tabler.admission', ['title' => 'Penerimaan Mahasiswa Baru'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">PMB {{ now()->year }}</div><h1 class="page-title">Pendaftaran {{ $university?->name ?? 'Universitas' }}</h1></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="row g-3">
    <div class="col-lg-7">
        <x-tabler.card title="Alur pendaftaran">
            <ol class="steps steps-vertical">
                @foreach(['Akun & biodata', 'Pilihan program', 'Dokumen', 'Pengajuan berkas', 'Pembayaran & verifikasi', 'Ujian & wawancara', 'Hasil seleksi', 'Daftar ulang', 'NIM & akun mahasiswa'] as $step)
                    <li class="step-item"><div class="step-item-content"><strong>{{ $loop->iteration }}. {{ $step }}</strong></div></li>
                @endforeach
            </ol>
        </x-tabler.card>
        <x-tabler.card title="Jalur tersedia">
            @forelse($paths as $path)
                <div class="d-flex justify-content-between border-bottom py-2"><span><strong>{{ $path->name }}</strong> <span class="text-secondary">({{ $path->code }})</span></span>@if($path->passing_grade)<span class="badge bg-azure-lt">Passing {{ $path->passing_grade }}</span>@endif</div>
            @empty
                <x-tabler.empty-state title="Jalur belum dibuka" description="Pantau halaman ini untuk gelombang berikutnya." icon="ti-calendar-off" />
            @endforelse
        </x-tabler.card>
    </div>
    <div class="col-lg-5">
        <form method="POST" action="{{ route('admission.register') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Buat akun pendaftaran</h3></div><div class="card-body"><label class="form-label">Nama lengkap</label><input name="name" class="form-control" required maxlength="255" value="{{ old('name') }}"><label class="form-label mt-2">Email</label><input type="email" name="email" class="form-control" required maxlength="255" value="{{ old('email') }}"><label class="form-label mt-2">Jalur</label><select name="admission_path_id" class="form-select" required>@foreach($paths as $path)<option value="{{ $path->id }}">{{ $path->name }}</option>@endforeach</select><label class="form-label mt-2">No. HP</label><input name="phone" class="form-control" maxlength="30" value="{{ old('phone') }}"><div class="row g-2 mt-2"><div class="col-6"><label class="form-label">Kata sandi</label><input type="password" name="password" class="form-control" required minlength="8"></div><div class="col-6"><label class="form-label">Konfirmasi</label><input type="password" name="password_confirmation" class="form-control" required minlength="8"></div></div></div><div class="card-footer"><button class="btn btn-primary w-100">Daftar sekarang</button><div class="text-center mt-2"><a href="{{ route('login') }}">Sudah punya akun? Masuk</a></div></div></form>
    </div>
</div>
</div></div>
@endsection
