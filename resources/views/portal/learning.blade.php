@extends('portal.layout', ['heading' => 'Pembelajaran'])

@section('content')
<div class="row row-cards">
@forelse($sections as $section)
<div class="col-12">
<div class="card">
<div class="card-header"><div><div class="text-secondary small">{{ $section->offering?->course?->code }} · {{ $section->offering?->semester?->name }}</div><h3 class="card-title">{{ $section->offering?->course?->name }} · {{ $section->code }}</h3></div><div class="card-actions"><span class="badge bg-blue-lt text-blue">{{ ucfirst($section->mode) }}</span></div></div>
<div class="card-body">
<div class="row g-4">
    <section class="col-12 col-lg-6"><h4><i class="ti ti-books me-2 text-primary"></i>Materi kelas</h4>
    @forelse($section->courseModules as $module)<div class="mb-3"><div class="fw-semibold">{{ $module->position }}. {{ $module->title }}</div><div class="list-group list-group-flush mt-1">@forelse($module->contents as $content)<div class="list-group-item px-0"><div class="d-flex gap-2"><i class="ti ti-file-text mt-1 text-secondary"></i><div><div>{{ $content->title }}</div>@if($content->body)<div class="small text-secondary">{{ str($content->body)->limit(180) }}</div>@endif @if($content->external_url)<a href="{{ $content->external_url }}" target="_blank" rel="noopener noreferrer" class="small">Buka materi <i class="ti ti-external-link"></i></a>@endif</div></div></div>@empty<div class="text-secondary small">Belum ada konten terbit.</div>@endforelse</div></div>@empty<x-tabler.empty-state title="Materi belum tersedia" description="Materi yang diterbitkan dosen akan muncul di sini." icon="ti-book-off" />@endforelse
    </section>
    <section class="col-12 col-lg-6"><h4><i class="ti ti-clipboard-text me-2 text-warning"></i>Tugas</h4>
    <div class="list-group list-group-flush">@forelse($section->assignments as $assignment)@php($submission=$assignment->submissions->first())<a href="{{ route('portal.assignments.show',$assignment) }}" class="list-group-item list-group-item-action px-0"><div class="d-flex justify-content-between gap-3"><div><div class="fw-semibold">{{ $assignment->title }}</div><div class="small text-secondary">{{ $assignment->due_at ? 'Tenggat '.$assignment->due_at->translatedFormat('d M Y H:i') : 'Tanpa tenggat' }}</div></div><span class="badge {{ $submission ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow' }}">{{ $submission ? 'Terkirim' : ucfirst($assignment->status) }}</span></div></a>@empty<div class="text-secondary">Belum ada tugas aktif.</div>@endforelse</div>
    <h4 class="mt-4"><i class="ti ti-help-hexagon me-2 text-azure"></i>Quiz / CBT</h4>
    <div class="list-group list-group-flush">@forelse($section->quizzes as $quiz)<a href="{{ route('portal.quizzes.show',$quiz) }}" class="list-group-item list-group-item-action px-0"><div class="d-flex justify-content-between"><div><div class="fw-semibold">{{ $quiz->title }}</div><div class="small text-secondary">{{ $quiz->duration_minutes ? $quiz->duration_minutes.' menit' : 'Tanpa batas durasi' }} · {{ $quiz->attempt_limit }} percobaan</div></div><span class="badge bg-azure-lt text-azure">{{ ucfirst($quiz->status) }}</span></div></a>@empty<div class="text-secondary">Belum ada quiz aktif.</div>@endforelse</div>
    </section>
</div>
</div>
</div>
</div>
@empty
<div class="col-12"><x-tabler.empty-state title="Belum ada kelas pembelajaran" description="Kelas muncul setelah KRS disetujui atau difinalisasi." icon="ti-school-off" /></div>
@endforelse
</div>
@endsection