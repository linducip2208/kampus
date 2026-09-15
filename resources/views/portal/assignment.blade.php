@extends('portal.layout', ['heading' => 'Detail tugas'])

@section('content')
@if(session('success'))<x-tabler.alert type="success">{{ session('success') }}</x-tabler.alert>@endif
@if($errors->any())<x-tabler.alert type="danger">{{ $errors->first() }}</x-tabler.alert>@endif
<div class="row row-cards">
<div class="col-12 col-lg-7"><div class="card"><div class="card-header"><div><div class="text-secondary small">{{ $assignment->classSection?->offering?->course?->code }}</div><h3 class="card-title">{{ $assignment->title }}</h3></div><div class="card-actions"><x-tabler.status :value="$assignment->status" /></div></div><div class="card-body"><div class="text-secondary mb-2">Instruksi</div><div class="lh-lg">{!! nl2br(e($assignment->instructions ?: 'Tidak ada instruksi tambahan.')) !!}</div><hr><div class="row g-3"><div class="col-sm-4"><div class="text-secondary small">Dibuka</div>{{ $assignment->opens_at?->translatedFormat('d M Y H:i') ?? 'Langsung' }}</div><div class="col-sm-4"><div class="text-secondary small">Tenggat</div>{{ $assignment->due_at?->translatedFormat('d M Y H:i') ?? 'Tidak dibatasi' }}</div><div class="col-sm-4"><div class="text-secondary small">Maksimum percobaan</div>{{ $assignment->max_attempts }}</div></div></div></div></div>
<div class="col-12 col-lg-5"><div class="card"><div class="card-header"><h3 class="card-title">Jawaban Anda</h3></div><div class="card-body">
@if($submission)<div class="alert alert-info"><div class="fw-semibold">Terkirim {{ $submission->submitted_at->diffForHumans() }}</div><div class="small">Percobaan {{ $submission->attempts_count }}/{{ $assignment->max_attempts }} @if($submission->is_late) · Terlambat @endif</div></div>@endif
@if($assignment->status==='open' && (!$submission || ($submission->status!=='graded' && $submission->attempts_count < $assignment->max_attempts)))
<form method="POST" action="{{ route('portal.assignments.submit',$assignment) }}">@csrf<label for="answer-text" class="form-label">Jawaban teks <span class="text-danger">*</span></label><textarea id="answer-text" name="answer_text" class="form-control @error('answer_text') is-invalid @enderror" rows="10" minlength="3" maxlength="20000" required>{{ old('answer_text',$submission?->answer_text) }}</textarea>@error('answer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-hint">Periksa jawaban sebelum mengirim. Pengiriman ulang dihitung sebagai percobaan baru.</div><button class="btn btn-primary w-100 mt-3" onclick="return confirm('Kirim jawaban tugas ini?')"><i class="ti ti-send me-2"></i>{{ $submission ? 'Kirim ulang' : 'Kirim tugas' }}</button></form>
@else
<x-tabler.empty-state title="Pengumpulan tidak tersedia" description="{{ $submission?->status==='graded' ? 'Tugas sudah dinilai.' : 'Assignment belum dibuka, sudah ditutup, atau batas percobaan tercapai.' }}" icon="ti-lock" />
@endif
@if($submission?->status==='graded')<hr><div class="h2 mb-1">{{ $submission->score }} / {{ $assignment->max_score }}</div><div class="text-secondary">{{ $submission->feedback ?: 'Tidak ada umpan balik.' }}</div>@endif
</div></div></div>
</div>
@endsection