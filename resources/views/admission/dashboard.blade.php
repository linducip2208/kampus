@extends('layouts.tabler.admission', ['title' => 'Dashboard Pendaftar'])

@section('content')
<div class="page-header d-print-none"><div class="container-xl"><div class="row align-items-center"><div class="col"><div class="page-pretitle">{{ $applicant->registration_number }}</div><h1 class="page-title">Halo, {{ $applicant->name }}</h1></div><div class="col-auto"><x-tabler.status :value="$applicant->status" /></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif

<div class="row g-3">
    <div class="col-lg-6">
        <form method="POST" action="{{ route('admission.biodata') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Biodata</h3></div><div class="card-body"><div class="row g-2"><div class="col-6"><label class="form-label">Tempat lahir</label><input name="birth_place" class="form-control" value="{{ $applicant->birth_place }}"></div><div class="col-6"><label class="form-label">Tanggal lahir</label><input type="date" name="birth_date" class="form-control" value="{{ $applicant->birth_date?->toDateString() }}"></div><div class="col-6"><label class="form-label">NIK</label><input name="national_id" class="form-control" value="{{ $applicant->national_id }}"></div><div class="col-6"><label class="form-label">NISN</label><input name="nisn" class="form-control" value="{{ $applicant->nisn }}"></div><div class="col-12"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2">{{ $applicant->address }}</textarea></div><div class="col-6"><label class="form-label">Asal sekolah</label><input name="previous_school" class="form-control" value="{{ $applicant->previous_school }}"></div><div class="col-6"><label class="form-label">Nilai sekolah</label><input type="number" step="0.01" min="0" max="100" name="school_score" class="form-control" value="{{ $applicant->school_score }}"></div></div></div><div class="card-footer"><button class="btn btn-primary w-100">Simpan biodata</button></div></form>
    </div>
    <div class="col-lg-6">
        <form method="POST" action="{{ route('admission.choices') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">Pilihan program (maks 2)</h3></div><div class="card-body">@foreach($programs as $program)<label class="form-check"><input type="checkbox" name="study_program_ids[]" value="{{ $program->id }}" class="form-check-input" {{ $applicant->programChoices->contains('study_program_id', $program->id) ? 'checked' : '' }}><span class="form-check-label">{{ $program->name }} <span class="text-secondary">— {{ $program->department?->name }}</span></span></label>@endforeach</div><div class="card-footer"><button class="btn btn-primary w-100">Simpan pilihan</button></div></form>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title">Dokumen</h3></div><div class="card-body">@forelse($applicant->documents as $document)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $document->label }}</span><x-tabler.status :value="$document->status" /></div>@empty<div class="text-secondary small">Belum ada dokumen.</div>@endforelse
        <form method="POST" action="{{ route('admission.documents') }}" class="row g-2 mt-2">@csrf<div class="col-4"><input name="kind" class="form-control" placeholder="Jenis" required maxlength="50"></div><div class="col-4"><input name="label" class="form-control" placeholder="Label" required maxlength="255"></div><div class="col-4"><input name="file_path" class="form-control" placeholder="Path berkas" required maxlength="500"></div><div class="col-12"><button class="btn btn-outline-primary w-100">Unggah dokumen</button></div></form></div>
        <div class="card-footer d-flex gap-2"><form method="POST" action="{{ route('admission.submit') }}">@csrf<button class="btn btn-success" {{ $applicant->status !== 'draft' ? 'disabled' : '' }}>Ajukan berkas</button></form></div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title">Pembayaran & hasil</h3></div><div class="card-body">@forelse($applicant->payments as $payment)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $payment->reference_number }}</span><x-tabler.status :value="$payment->status" /></div>@empty<div class="text-secondary small">Belum ada pembayaran.</div>@endforelse
        @if($applicant->status === 'payment_pending')<form method="POST" action="{{ route('admission.payment') }}" class="row g-2 mt-2">@csrf<div class="col-4"><input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="Nominal" required></div><div class="col-4"><input name="method" class="form-control" placeholder="transfer" maxlength="50"></div><div class="col-4"><input name="proof_path" class="form-control" placeholder="Bukti" maxlength="500"></div><div class="col-12"><button class="btn btn-outline-primary w-100">Kirim bukti bayar</button></div></form>@endif
        @if($applicant->exams->isNotEmpty())<div class="mt-2"><strong>Ujian:</strong> {{ $applicant->exams->first()->title }} — {{ $applicant->exams->first()->score ?? 'belum dinilai' }}</div>@endif
        @if($applicant->status === 'passed')<form method="POST" action="{{ route('admission.re-registration') }}" class="mt-2">@csrf<button class="btn btn-success w-100">Ajukan daftar ulang</button></form>@endif
        @if($applicant->status === 'student_created')<x-tabler.alert type="success">NIM Anda: {{ $applicant->convertedStudent?->student_number }}. Silakan masuk dengan akun mahasiswa.</x-tabler.alert>@endif
        </div></div>
    </div>
</div>
</div></div>
@endsection
